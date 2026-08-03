<?php
/**
 * Test completo de ML - paso a paso
 */
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$resultado = [];

// 1. Verificar archivo de config
require_once __DIR__ . '/../config/mercadolibre.php';
$resultado['1_config'] = [
    'secret_correcto' => (ML_CLIENT_SECRET === 'Xeru5mcUpEtxFwoDeLjvAh2qsQYspzLP'),
    'token_file_path' => ML_TOKEN_FILE
];

// 2. Verificar token file
$resultado['2_token_file'] = [
    'existe' => file_exists(ML_TOKEN_FILE),
    'contenido' => file_exists(ML_TOKEN_FILE) ? json_decode(file_get_contents(ML_TOKEN_FILE), true) : null
];

// 3. Intentar obtener token
try {
    $token = getMLAccessToken();
    $resultado['3_get_token'] = [
        'ok' => true,
        'token_preview' => substr($token, 0, 20) . '...'
    ];
} catch (Exception $e) {
    $resultado['3_get_token'] = [
        'ok' => false,
        'error' => $e->getMessage()
    ];
}

// 4. Intentar búsqueda de productos
try {
    $searchResult = mlRequest('/products/search', [
        'q' => 'notebook lenovo',
        'site_id' => ML_SITE,
        'limit' => 1
    ]);
    $resultado['4_busqueda'] = [
        'http_code' => $searchResult['http_code'],
        'tiene_resultados' => !empty($searchResult['data']['results']),
        'cantidad' => count($searchResult['data']['results'] ?? [])
    ];
} catch (Exception $e) {
    $resultado['4_busqueda'] = [
        'ok' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
