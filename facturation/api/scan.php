<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$bufferFile = __DIR__ . '/last_scan.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['clear'])) {
    if (file_exists($bufferFile)) {
        unlink($bufferFile);
    }
    echo json_encode([
        'status'  => 'ok',
        'cleared' => true,
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $code  = trim($input['code'] ?? '');

    if ($code === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'No code received'
        ]);
        exit;
    }

    $data = [
        'code'      => $code,
        'timestamp' => time(),
    ];

    file_put_contents($bufferFile, json_encode($data, JSON_UNESCAPED_UNICODE));

    echo json_encode([
        'status' => 'ok',
        'code'   => $code,
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!file_exists($bufferFile)) {
        echo json_encode(['status' => 'empty']);
        exit;
    }

    $data = json_decode(file_get_contents($bufferFile), true);
    if (!is_array($data) || empty($data['code']) || empty($data['timestamp'])) {
        echo json_encode(['status' => 'empty']);
        exit;
    }

    echo json_encode([
        'status'    => 'ok',
        'code'      => $data['code'],
        'timestamp' => $data['timestamp'],
    ]);
    exit;
}

http_response_code(405);
echo json_encode([
    'status'  => 'error',
    'message' => 'Method not allowed',
]);