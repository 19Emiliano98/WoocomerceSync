<?php
/**
 * Diagnóstico del error 500
 */

// Capturar TODOS los errores
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Handler de errores personalizado
$errors = [];
set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$errors) {
    $errors[] = [
        'type' => $errno,
        'message' => $errstr,
        'file' => basename($errfile),
        'line' => $errline
    ];
    return true;
});

// Handler de excepciones
set_exception_handler(function($e) {
    header('Content-Type: application/json');
    http_response_code(200); // Forzar 200 para ver el error
    echo json_encode([
        'exception' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    exit;
});

// Handler de shutdown para errores fatales
register_shutdown_function(function() use (&$errors) {
    $lastError = error_get_last();
    if ($lastError && in_array($lastError['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode([
            'fatal_error' => $lastError['message'],
            'file' => basename($lastError['file']),
            'line' => $lastError['line']
        ]);
    }
});

ob_start();
header('Content-Type: application/json');

$resultado = ['paso' => 0];

try {
    // Paso 1: Bootstrap
    $resultado['paso'] = 1;
    require_once __DIR__ . '/../bootstrap.php';
    $resultado['bootstrap'] = 'OK';

    // Paso 2: Auth
    $resultado['paso'] = 2;
    if (!isAuthenticated()) {
        http_response_code(200);
        ob_end_clean();
        echo json_encode(['error' => 'No autenticado', 'errors' => $errors]);
        exit;
    }
    $resultado['auth'] = 'OK';

    // Paso 3: ML Config
    $resultado['paso'] = 3;
    require_once __DIR__ . '/../config/mercadolibre.php';
    $resultado['ml_config'] = 'OK';

    // Paso 4: DB
    $resultado['paso'] = 4;
    $dbService = getSigeConnection();
    $conn = $dbService->getConnection();
    $resultado['db'] = 'OK';

    // Paso 5: Query
    $resultado['paso'] = 5;
    $sku = '9999';
    $stmt = $conn->prepare("SELECT TRIM(ART_IDArticulo) as sku, ART_DesArticulo as nombre FROM sige_art_articulo WHERE TRIM(ART_IDArticulo) = ?");
    $stmt->bind_param("s", $sku);
    $stmt->execute();
    $art = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $resultado['articulo'] = $art ? 'Encontrado: ' . $art['nombre'] : 'No encontrado';

    // Paso 6: Buscar en ML
    $resultado['paso'] = 6;
    $mlResult = buscarImagenesConFallback($sku, 'P47 5.0+EDR', $art['nombre'] ?? '', '');
    $resultado['ml'] = !empty($mlResult['imagenes']) ? count($mlResult['imagenes']) . ' imagenes' : 'Sin imagenes';

    // Final
    $resultado['errors_php'] = $errors;
    $resultado['success'] = true;

    $conn->close();
    ob_end_clean();
    http_response_code(200);
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(200);
    echo json_encode([
        'error' => $e->getMessage(),
        'paso' => $resultado['paso'],
        'errors_php' => $errors
    ], JSON_PRETTY_PRINT);
}
