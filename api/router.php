<?php
$path = $_GET['path'] ?? '';
$path = ltrim($path, '/');

if ($path === '' || str_contains($path, '..') || str_contains($path, "\\")) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid path']);
    exit;
}

$target = __DIR__ . '/../backend/' . $path;

if (!file_exists($target)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
    exit;
}

require $target;
