<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

require_permission('orders', '../login.php');
$me = current_user();
$page_title = 'Quản lý đơn hàng';
$flash = '';
$error = '';

$statusList = order_status_list();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    if ($orderId > 0) {
        // update_order_status() cũng tự cộng/trừ lại tồn kho khi trạng thái đi
        // vào/ra khỏi "Đã hủy" / "Đã hoàn hàng" (xem includes/helpers.php).
        $result = update_order_status($conn, $orderId, $newStatus);
        if ($result['ok']) audit_log('order.status', 'Đơn #' . $orderId . ' → ' . $newStatus);
        $flash = $result['ok'] ? 'Đã cập nhật trạng thái đơn #' . $orderId . '.' : '';
        $error = $result['ok'] ? '' : $result['message'];
    }
}

$filterStatus = $_GET['status'] ?? '';
$sql = "SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id";
$types = '';
$params = [];
if ($filterStatus !== '' && in_array($filterStatus, $statusList, true)) {
    $sql .= " WHERE o.status = ?";
    $types = 's';
    $params[] = $filterStatus;
}
$sql .= " ORDER BY o.id DESC";
$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = $stmt->get_result();

// Xuất CSV (chỉ admin) - áp dụng đúng bộ lọc trạng thái đang chọn.
if (($_GET['export'] ?? '') === 'csv' && ui_admin()) {
    audit_log('order.export', 'Xuất CSV đơn hàng' . ($filterStatus !== '' ? ' (' . $filterStatus . ')' : ''));
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="orders_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM để Excel đọc đúng tiếng Việt
    fputcsv($out, ['Mã đơn', 'Khách hàng', 'Tổng tiền', 'Trạng thái', 'Địa chỉ', 'SĐT', 'Voucher', 'Ngày tạo']);
    while ($o = $orders->fetch_assoc()) {
        // Chống CSV injection: ô bắt đầu bằng = + - @ sẽ bị Excel hiểu là công thức.
        $safe = fn($v) => preg_match('/^[=+\-@]/', (string)$v) ? "'" . $v : $v;
        fputcsv($out, [$o['id'], $safe($o['username']), $o['total'], $o['status'], $safe($o['address']), $safe($o['phone']), $o['voucher_code'], $o['created_at']]);
    }
    fclose($out);
    exit;
}

require __DIR__ . '/../includes/admin_header.php';
$statusChip = function (string $st): string {
    $map = ['Đã giao' => 'ok', 'Đã hủy' => 'bad', 'Từ chối hoàn hàng' => 'bad', 'Yêu cầu hoàn hàng' => 'warn',
            'Chờ xác nhận' => 'warn', 'Đã hoàn hàng' => 'gray'];
    return '<span class="chip ' . ($map[$st] ?? '') . '">' . htmlspecialchars($st) . '</span>';
};
?>
  <?php if ($flash): ?><div class="flash ok"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="flash error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <div class="card">
    <div class="card-head">
      <form method="get" class="toolbar" style="margin:0;">
        <label for="status" style="margin:0;">Trạng thái:</label>
        <select name="status" id="status" onchange="this.form.submit()">
          <option value="">Tất cả</option>
          <?php foreach ($statusList as $st): ?>
            <option value="<?php echo htmlspecialchars($st); ?>" <?php echo $filterStatus === $st ? 'selected' : ''; ?>><?php echo htmlspecialchars($st); ?></option>
          <?php endforeach; ?>
        </select>
      </form>
      <?php if (ui_admin()): ?>
        <a class="btn btn-sm btn-secondary" href="orders.php?export=csv<?php echo $filterStatus !== '' ? '&status=' . urlencode($filterStatus) : ''; ?>">⬇ Xuất CSV</a>
      <?php endif; ?>
    </div>
    <div class="table-wrap"><table>
      <tr><th>Mã đơn</th><th>Khách hàng</th><th>Tổng tiền</th><th>Địa chỉ / SĐT</th><th>Trạng thái</th><th>Ngày tạo</th><th>Cập nhật</th></tr>
      <?php while ($o = $orders->fetch_assoc()): ?>
        <tr>
          <td>#<?php echo (int)$o['id']; ?></td>
          <td><?php echo htmlspecialchars($o['username']); ?></td>
          <td><?php echo number_format((float)$o['total'], 0, ',', '.'); ?>đ</td>
          <td class="muted"><?php echo htmlspecialchars($o['address'] ?? '—'); ?><br><?php echo htmlspecialchars($o['phone'] ?? '—'); ?></td>
          <td><?php echo $statusChip($o['status']); ?></td>
          <td class="muted"><?php echo htmlspecialchars($o['created_at']); ?></td>
          <td>
            <form method="post" style="display:flex;gap:6px;">
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
              <select name="status" style="margin:0;">
                <?php foreach ($statusList as $st): ?>
                  <option value="<?php echo htmlspecialchars($st); ?>" <?php echo $o['status'] === $st ? 'selected' : ''; ?>><?php echo htmlspecialchars($st); ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn-sm">Lưu</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    </table></div>
  </div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
