<?php
/**
 * Replica EXACTA de image-search.php para SKU 9999
 */
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/mercadolibre.php';

function getDbConnection() {
    $dbService = getSigeConnection();
    return $dbService->getConnection();
}

function wcRequest($endpoint, $method = 'GET', $data = null) {
    if (!isset($_SESSION['cliente_config'])) {
        throw new Exception("No hay sesión de cliente activa");
    }
    $config = $_SESSION['cliente_config'];
    if (empty($config['wc_url']) || empty($config['wc_key']) || empty($config['wc_secret'])) {
        throw new Exception("Credenciales de WooCommerce incompletas");
    }
    $url = $config['wc_url'] . $endpoint;
    $url .= (strpos($url, '?') === false ? '?' : '&');
    $url .= 'consumer_key=' . urlencode($config['wc_key']) . '&consumer_secret=' . urlencode($config['wc_secret']);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) throw new Exception("CURL Error: $error");
    if ($httpCode >= 400) throw new Exception("WooCommerce API error: $httpCode");
    return json_decode($response, true);
}

function sanitizeForJson($data) {
    if (is_array($data)) return array_map('sanitizeForJson', $data);
    elseif (is_string($data)) {
        if (!mb_check_encoding($data, 'UTF-8')) {
            $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }
        $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
    }
    return $data;
}

if (!isAuthenticated()) {
    ob_end_clean();
    die(json_encode(['error' => 'No autenticado']));
}

$sku = '9999';

try {
    $conn = getDbConnection();

    $stmt = $conn->prepare("
        SELECT TRIM(a.ART_IDArticulo) as sku, a.ART_DesArticulo as nombre,
            TRIM(a.ART_PartNumber) as part_number, TRIM(a.ART_CodBarraArt) as codigo_barras,
            a.ART_IdML as id_ml, d.adv_pathimagen as imagen_sige
        FROM sige_art_articulo a
        LEFT JOIN sige_adv_artdatvar d ON a.ART_IDArticulo = d.art_idarticulo
        WHERE TRIM(a.ART_IDArticulo) = ?
    ");
    $stmt->bind_param("s", $sku);
    $stmt->execute();
    $articulo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$articulo) {
        ob_end_clean();
        die(json_encode(['error' => 'No encontrado']));
    }

    $response = [
        'success' => true,
        'articulo' => [
            'sku' => $articulo['sku'],
            'nombre' => $articulo['nombre'],
            'part_number' => $articulo['part_number'],
            'codigo_barras' => $articulo['codigo_barras'],
            'id_ml' => $articulo['id_ml']
        ],
        'imagenes' => ['sige' => null, 'mercadolibre' => null]
    ];

    // Imagen SIGE
    if (!empty($articulo['imagen_sige']) && $articulo['imagen_sige'] !== '0') {
        $response['imagenes']['sige'] = ['path' => $articulo['imagen_sige'], 'fuente' => 'SIGE'];
    }

    // Buscar en ML
    if (empty($response['imagenes']['sige'])) {
        $resultadoML = buscarImagenesConFallback(
            $articulo['sku'], $articulo['part_number'], $articulo['nombre'], $articulo['codigo_barras']
        );
        if (!empty($resultadoML['imagenes'])) {
            $response['imagenes']['mercadolibre'] = [
                'producto' => $resultadoML['producto_ml'] ?? null,
                'encontrado_por' => $resultadoML['encontrado_por'],
                'imagenes' => $resultadoML['imagenes'],
                'fuente' => 'Mercado Libre'
            ];
        }
    }

    // WooCommerce
    try {
        $wooProducts = wcRequest('/products?sku=' . urlencode($sku));
        $wooProduct = null;
        if (is_array($wooProducts) && !empty($wooProducts)) {
            foreach ($wooProducts as $p) {
                if (is_array($p) && strcasecmp(trim($p['sku'] ?? ''), trim($sku)) === 0) {
                    $wooProduct = $p;
                    break;
                }
            }
        }
        if ($wooProduct !== null && is_array($wooProduct)) {
            $response['woocommerce'] = [
                'id' => $wooProduct['id'],
                'tiene_imagenes' => !empty($wooProduct['images']),
                'cantidad_imagenes' => count($wooProduct['images'] ?? []),
                'imagenes' => array_map(function($img) {
                    return ['id' => $img['id'] ?? null, 'src' => $img['src'] ?? '', 'name' => $img['name'] ?? ''];
                }, $wooProduct['images'] ?? [])
            ];
        }
    } catch (Exception $e) {
        $response['woocommerce'] = null;
    }

    $conn->close();
    $response = sanitizeForJson($response);
    ob_end_clean();

    $json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    if ($json === false) {
        echo json_encode(['error' => 'JSON encode failed: ' . json_last_error_msg()]);
    } else {
        echo $json;
    }

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['error' => $e->getMessage()]);
}
