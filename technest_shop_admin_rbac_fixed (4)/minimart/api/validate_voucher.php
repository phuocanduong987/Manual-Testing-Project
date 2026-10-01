<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$code = trim($_GET['code'] ?? '');
$subtotal = (float)($_GET['subtotal'] ?? 0);

$result = validate_voucher($conn, $code, $subtotal);

echo json_encode([
    'ok' => $result['ok'],
    'message' => $result['message'],
    'discount' => $result['discount'],
    'code' => $result['voucher']['code'] ?? null,
]);
