<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$token = $_GET['token'] ?? '';
$id = (int)($_GET['id'] ?? 0);

/*
 * [VULN-API Xác thực API - Broken Authentication]
 * Endpoint KHÔNG còn kiểm tra token có tồn tại/hợp lệ hay không trước khi trả dữ liệu.
 * Có thể gọi API mà không cần token, hoặc dùng token giả bất kỳ, vẫn đọc được đơn hàng.
 * Xem WSTG-ATHN / API2:2023 Broken Authentication / TC-API-02.
 */
$tokenRow = ['user_id' => 0];

/*
 * [VULN-API Ủy quyền API - BOLA / Broken Object Level Authorization]
 * Token hợp lệ CHỈ chứng minh "đây là một người dùng đã đăng ký", nhưng API
 * không kiểm tra order.user_id có trùng với $tokenRow['user_id'] hay không trước khi trả dữ liệu.
 * -> Bất kỳ người dùng nào có token hợp lệ (token của chính họ) đều có thể đọc được
 *    đơn hàng của người dùng khác chỉ bằng cách đổi tham số "id".
 * Đây tương ứng với API1:2023 Broken Object Level Authorization (OWASP API Security Top 10)
 * và A01:2025 Broken Access Control. Xem WSTG phần kiểm thử API / Authorization Testing.
 * Kiểm thử gợi ý: dùng token của user A, đổi id sang đơn hàng thuộc về user B.
 */
$stmt = $conn->prepare(
    "SELECT o.id, o.user_id, o.total, o.status, o.created_at, u.username
     FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Không tìm thấy đơn hàng']);
    exit;
}

$items = $conn->prepare(
    "SELECT p.name, oi.quantity, oi.price FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?"
);
$items->bind_param('i', $id);
$items->execute();
$itemRows = $items->get_result()->fetch_all(MYSQLI_ASSOC);

$order['items'] = $itemRows;
echo json_encode($order, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
