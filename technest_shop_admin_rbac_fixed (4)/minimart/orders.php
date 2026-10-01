<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

require_login();
$me = current_user();
$id = (int)($_GET['id'] ?? 0);
$page_title = 'Chi tiết đơn hàng';

/*
 * [VULN-A01 Broken Access Control - IDOR]
 * Truy vấn lấy đơn hàng CHỈ theo $id trên URL, không kiểm tra order.user_id === $me['id'].
 * -> Người dùng đã đăng nhập có thể xem đơn hàng của BẤT KỲ ai bằng cách đổi id trên URL
 *    (ví dụ orders.php?id=1, id=2, id=3...), dù trang liệt kê (my_orders.php) đã lọc đúng.
 * Xem WSTG-ATHZ-04 (Testing for Insecure Direct Object References).
 */
$stmt = $conn->prepare(
    "SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    require __DIR__ . '/includes/header.php';
    echo '<div class="panel">Không tìm thấy đơn hàng.</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$items = $conn->prepare(
    "SELECT oi.quantity, oi.price, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?"
);
$items->bind_param('i', $id);
$items->execute();
$itemRows = $items->get_result();

require __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <h2>Đơn hàng #<?php echo (int)$order['id']; ?></h2>
  <p class="muted">Khách hàng: <?php echo htmlspecialchars($order['username']); ?> · Ngày tạo: <?php echo htmlspecialchars($order['created_at']); ?></p>
  <p>Trạng thái: <span class="badge"><?php echo htmlspecialchars($order['status']); ?></span></p>

  <table>
    <tr><th>Sản phẩm</th><th>Số lượng</th><th>Giá</th></tr>
    <?php while ($it = $itemRows->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($it['name']); ?></td>
        <td><?php echo (int)$it['quantity']; ?></td>
        <td><?php echo number_format((float)$it['price'], 0, ',', '.'); ?>đ</td>
      </tr>
    <?php endwhile; ?>
  </table>
  <?php if (!empty($order['address'])): ?>
    <p class="muted">Địa chỉ giao hàng: <?php echo htmlspecialchars($order['address']); ?></p>
  <?php endif; ?>
  <?php if (!empty($order['phone'])): ?>
    <p class="muted">Số điện thoại: <?php echo htmlspecialchars($order['phone']); ?></p>
  <?php endif; ?>
  <?php if ((float)($order['shipping_fee'] ?? 0) > 0): ?>
    <p class="muted">Phí vận chuyển: <?php echo number_format((float)$order['shipping_fee'], 0, ',', '.'); ?>đ</p>
  <?php endif; ?>
  <?php if (!empty($order['voucher_code'])): ?>
    <p class="muted">Voucher: <?php echo htmlspecialchars($order['voucher_code']); ?> (-<?php echo number_format((float)$order['discount_amount'], 0, ',', '.'); ?>đ)</p>
  <?php endif; ?>
  <p style="margin-top:12px;"><strong>Tổng cộng: <?php echo number_format((float)$order['total'], 0, ',', '.'); ?>đ</strong></p>

  <?php if ($me && (int)$order['user_id'] === (int)$me['id']): ?>
    <div class="card-actions" style="max-width:320px;margin-top:14px;">
      <?php if (order_can_cancel($order['status'])): ?>
        <form method="post" action="order_action.php" onsubmit="return confirm('Bạn chắc chắn muốn huỷ đơn hàng này?');">
          <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
          <input type="hidden" name="action" value="cancel">
          <button type="submit" class="btn btn-secondary">Hủy đơn</button>
        </form>
      <?php endif; ?>
      <?php if (order_can_return($order['status'])): ?>
        <form method="post" action="order_action.php" onsubmit="return confirm('Gửi yêu cầu hoàn hàng cho đơn này?');">
          <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
          <input type="hidden" name="action" value="return">
          <button type="submit" class="btn btn-secondary">Yêu cầu hoàn hàng</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
