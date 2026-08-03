<?php
/**
 * Test paso a paso de image-search
 */
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$resultado = [];

// 1. Bootstrap
try {
    require_once __DIR__ . '/../bootstrap.php';
    $resultado['1_bootstrap'] = 'OK';
} catch (Exception $e) {
    $resultado['1_bootstrap'] = 'ERROR: ' . $e->getMessage();
    echo json_encode($resultado, JSON_PRETTY_PRINT);
    exit;
}

// 2. Autenticación
$resultado['2_auth'] = [
    'isAuthenticated' => isAuthenticated(),
    'clienteId' => isAuthenticated() ? getClienteId() : null
];

if (!isAuthenticated()) {
    $resultado['2_auth']['mensaje'] = 'Debes estar logueado';
    echo json_encode($resultado, JSON_PRETTY_PRINT);
    exit;
}

// 3. Sesión
$resultado['3_sesion'] = [
    'tiene_cliente_config' => isset($_SESSION['cliente_config']),
    'keys' => isset($_SESSION['cliente_config']) ? array_keys($_SESSION['cliente_config']) : []
];

// 4. Conexión SIGE
try {
    $dbService = getSigeConnection();
    $conn = $dbService->getConnection();
    $resultado['4_sige_db'] = 'OK - Conectado';
} catch (Exception $e) {
    $resultado['4_sige_db'] = 'ERROR: ' . $e->getMessage();
}

// 5. Cargar mercadolibre.php
try {
    require_once __DIR__ . '/../config/mercadolibre.php';
    $resultado['5_ml_config'] = 'OK';
} catch (Exception $e) {
    $resultado['5_ml_config'] = 'ERROR: ' . $e->getMessage();
}

// 6. Test búsqueda ML
try {
    $searchResult = mlRequest('/products/search', [
        'q' => 'notebook',
        'site_id' => ML_SITE,
        'limit' => 1
    ]);
    $resultado['6_ml_busqueda'] = [
        'http_code' => $searchResult['http_code'],
        'tiene_resultados' => !empty($searchResult['data']['results'])
    ];
} catch (Exception $e) {
    $resultado['6_ml_busqueda'] = 'ERROR: ' . $e->getMessage();
}

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
