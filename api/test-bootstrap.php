<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo json_encode(['paso' => 'antes de bootstrap']);

require_once __DIR__ . '/../bootstrap.php';

echo json_encode(['paso' => 'despues de bootstrap']);
