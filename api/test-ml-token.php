<?php
/**
 * Test directo de credenciales ML - sin dependencias
 */
header('Content-Type: application/json');

$appId = '828139284413193';
$secret = 'Xeru5mcUpEtxFwoDeLjvAh2qsQYspzLP';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.mercadolibre.com/oauth/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'client_credentials',
    'client_id' => $appId,
    'client_secret' => $secret
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/x-www-form-urlencoded'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo json_encode([
    'http_code' => $httpCode,
    'curl_error' => $error,
    'response' => json_decode($response, true),
    'secret_usado' => substr($secret, 0, 5) . '...'
], JSON_PRETTY_PRINT);
