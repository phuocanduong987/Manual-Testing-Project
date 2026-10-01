<?php
/**
 * Xử lý hành động của khách trên đơn hàng của CHÍNH họ: huỷ đơn / yêu cầu hoàn hàng.
 * Đây là hành động ghi dữ liệu (không phải trang xem đơn orders.php - nơi cố ý có
 * lỗi IDOR cho bài lab pentest), nên ở đây LUÔN kiểm tra order.user_id = người đang
 * đăng nhập trước khi cho phép thay đổi trạng thái.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

require_login();
$me = current_user();

$orderId = (int)($_POST['order_id'] ?? 0);
$action = $_POST['action'] ?? '';

$stmt = $conn->prepare("SELECT id, user_id, status FROM orders WHERE id = ?");
$stmt->bind_param('i', $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order || (int)$order['user_id'] !== (int)$me['id']) {
    header('Location: my_orders.php');
    exit;
}

// Dùng update_order_status() thay vì UPDATE trực tiếp để tồn kho được cộng lại
// tự động khi đơn chuyển sang "Đã hủy" (xem includes/helpers.php).
if ($action === 'cancel' && order_can_cancel($order['status'])) {
    update_order_status($conn, $orderId, 'Đã hủy');
} elseif ($action === 'return' && order_can_return($order['status'])) {
    update_order_status($conn, $orderId, 'Yêu cầu hoàn hàng');
}

header('Location: orders.php?id=' . $orderId);
exit;
