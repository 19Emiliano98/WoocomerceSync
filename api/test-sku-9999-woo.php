<?php
/**
 * Test específico para SKU 9999 - incluyendo WooCommerce
 */
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$resultado = [];
$sku = '9999';

try {
    require_once __DIR__ . '/../bootstrap.php';

    if (!isAuthenticated()) {
        echo json_encode(['error' => 'No autenticado']);
        exit;
    }

    $resultado['1_auth'] = 'OK';

    // Config de WooCommerce desde sesión
    $config = $_SESSION['cliente_config'];
    $resultado['2_wc_config'] = [
        'tiene_url' => !empty($config['wc_url']),
        'tiene_key' => !empty($config['wc_key']),
        'tiene_secret' => !empty($config['wc_secret']),
        'url' => $config['wc_url'] ?? 'NO DEFINIDA'
    ];

    // Intentar buscar en WooCommerce
    $resultado['3_buscando_woo'] = 'Iniciando...';

    $url = $config['wc_url'] . '/products?sku=' . urlencode($sku);
    $url .= '&consumer_key=' . urlencode($config['wc_key']);
    $url .= '&consumer_secret=' . urlencode($config['wc_secret']);

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

    $resultado['4_woo_response'] = [
        'http_code' => $httpCode,
        'curl_error' => $error,
        'response_length' => strlen($response),
        'response_preview' => substr($response, 0, 500)
    ];

    $data = json_decode($response, true);
    $resultado['5_productos_encontrados'] = is_array($data) ? count($data) : 'No es array';

} catch (Exception $e) {
    $resultado['ERROR'] = $e->getMessage();
    $resultado['TRACE'] = $e->getTraceAsString();
}

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
