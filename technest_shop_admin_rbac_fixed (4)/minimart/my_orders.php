<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

require_login();
$me = current_user();
$page_title = 'Đơn hàng của tôi';

// Danh sách này được lọc đúng theo user_id (an toàn ở bước liệt kê).
$stmt = $conn->prepare("SELECT id, total, status, created_at FROM orders WHERE user_id = ? ORDER BY id DESC");
$stmt->bind_param('i', $me['id']);
$stmt->execute();
$orders = $stmt->get_result();

require __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <h2>Đơn hàng của tôi</h2>
  <?php if ($orders->num_rows === 0): ?>
    <p class="muted">Bạn chưa có đơn hàng nào. <a href="index.php">Mua sắm ngay</a>.</p>
  <?php else: ?>
    <table>
      <tr><th>Mã đơn</th><th>Tổng tiền</th><th>Trạng thái</th><th>Ngày tạo</th><th></th></tr>
      <?php while ($o = $orders->fetch_assoc()): ?>
        <tr>
          <td>#<?php echo (int)$o['id']; ?></td>
          <td><?php echo number_format((float)$o['total'], 0, ',', '.'); ?>đ</td>
          <td><span class="badge"><?php echo htmlspecialchars($o['status']); ?></span></td>
          <td><?php echo htmlspecialchars($o['created_at']); ?></td>
          <td>
            <a href="orders.php?id=<?php echo (int)$o['id']; ?>">Xem chi tiết</a>
            <?php if (order_can_cancel($o['status'])): ?>
              · <form method="post" action="order_action.php" style="display:inline;" onsubmit="return confirm('Huỷ đơn này?');">
                  <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
                  <input type="hidden" name="action" value="cancel">
                  <button type="submit" class="link-btn">Hủy đơn</button>
                </form>
            <?php endif; ?>
            <?php if (order_can_return($o['status'])): ?>
              · <form method="post" action="order_action.php" style="display:inline;" onsubmit="return confirm('Gửi yêu cầu hoàn hàng?');">
                  <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
                  <input type="hidden" name="action" value="return">
                  <button type="submit" class="link-btn">Hoàn hàng</button>
                </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
