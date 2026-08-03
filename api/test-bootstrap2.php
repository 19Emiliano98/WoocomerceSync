<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pasos = [];
$pasos[] = 'inicio';

require_once __DIR__ . '/../bootstrap.php';
$pasos[] = 'bootstrap ok';

require_once __DIR__ . '/../config/mercadolibre.php';
$pasos[] = 'mercadolibre ok';

echo json_encode(['pasos' => $pasos, 'ok' => true]);
