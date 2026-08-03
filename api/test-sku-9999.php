<?php
/**
 * Test específico para SKU 9999
 */
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$resultado = [];
$sku = '9999';

try {
    require_once __DIR__ . '/../bootstrap.php';
    require_once __DIR__ . '/../config/mercadolibre.php';

    if (!isAuthenticated()) {
        echo json_encode(['error' => 'No autenticado']);
        exit;
    }

    $resultado['1_inicio'] = 'OK';

    // 2. Conexión DB
    $dbService = getSigeConnection();
    $conn = $dbService->getConnection();
    $resultado['2_db'] = 'Conectado';

    // 3. Buscar artículo
    $stmt = $conn->prepare("
        SELECT
            TRIM(a.ART_IDArticulo) as sku,
            a.ART_DesArticulo as nombre,
            TRIM(a.ART_PartNumber) as part_number,
            TRIM(a.ART_CodBarraArt) as codigo_barras,
            a.ART_IdML as id_ml,
            d.adv_pathimagen as imagen_sige
        FROM sige_art_articulo a
        LEFT JOIN sige_adv_artdatvar d ON a.ART_IDArticulo = d.art_idarticulo
        WHERE TRIM(a.ART_IDArticulo) = ?
    ");
    $stmt->bind_param("s", $sku);
    $stmt->execute();
    $result = $stmt->get_result();
    $articulo = $result->fetch_assoc();
    $stmt->close();

    $resultado['3_articulo'] = $articulo ? [
        'sku' => $articulo['sku'],
        'nombre' => $articulo['nombre'],
        'part_number' => $articulo['part_number'],
        'codigo_barras' => $articulo['codigo_barras']
    ] : 'NO ENCONTRADO';

    if (!$articulo) {
        echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 4. Buscar imágenes en ML
    $resultado['4_buscando_ml'] = 'Iniciando búsqueda...';

    $resultadoML = buscarImagenesConFallback(
        $articulo['sku'],
        $articulo['part_number'],
        $articulo['nombre'],
        $articulo['codigo_barras']
    );

    $resultado['5_resultado_ml'] = [
        'tiene_imagenes' => !empty($resultadoML['imagenes']),
        'cantidad' => count($resultadoML['imagenes'] ?? []),
        'encontrado_por' => $resultadoML['encontrado_por'] ?? null
    ];

    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $resultado['ERROR'] = $e->getMessage();
    $resultado['TRACE'] = $e->getTraceAsString();
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
