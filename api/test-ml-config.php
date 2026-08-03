<?php
/**
 * Test - Ver qué secret tiene mercadolibre.php
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/mercadolibre.php';

echo json_encode([
    'ML_APP_ID' => ML_APP_ID,
    'ML_CLIENT_SECRET' => substr(ML_CLIENT_SECRET, 0, 8) . '...',
    'secret_correcto' => (ML_CLIENT_SECRET === 'Xeru5mcUpEtxFwoDeLjvAh2qsQYspzLP') ? 'SI' : 'NO - TIENE EL VIEJO',
    'ML_TOKEN_FILE' => ML_TOKEN_FILE,
    'token_file_exists' => file_exists(ML_TOKEN_FILE) ? 'SI' : 'NO'
], JSON_PRETTY_PRINT);
