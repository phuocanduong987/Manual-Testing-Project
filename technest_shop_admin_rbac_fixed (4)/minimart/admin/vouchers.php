<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Voucher ảnh hưởng trực tiếp doanh thu -> chỉ admin.
require_admin('../login.php');
$me = current_user();
$page_title = 'Quản lý voucher';
$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM vouchers WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        audit_log('voucher.delete', 'Xoá voucher #' . $id);
        $flash = 'Đã xoá voucher.';
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("UPDATE vouchers SET active = 1 - active WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        audit_log('voucher.toggle', 'Bật/tắt voucher #' . $id);
        $flash = 'Đã cập nhật trạng thái voucher.';
    } elseif ($action === 'create') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $type = ($_POST['discount_type'] ?? 'percent') === 'amount' ? 'amount' : 'percent';
        $value = (float)($_POST['discount_value'] ?? 0);
        $minOrder = (float)($_POST['min_order'] ?? 0);
        $maxUses = trim($_POST['max_uses'] ?? '');
        $maxUses = $maxUses === '' ? null : (int)$maxUses;
        $expires = trim($_POST['expires_at'] ?? '');
        $expires = $expires === '' ? null : $expires;

        if ($code === '' || $value <= 0) {
            $error = 'Vui lòng nhập mã voucher và giá trị giảm hợp lệ.';
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO vouchers (code, description, discount_type, discount_value, min_order, max_uses, expires_at)
                 VALUES (?,?,?,?,?,?,?)"
            );
            $stmt->bind_param('sssddis', $code, $description, $type, $value, $minOrder, $maxUses, $expires);
            if (!$stmt->execute()) {
                $error = 'Mã voucher đã tồn tại.';
            } else {
                audit_log('voucher.create', 'Tạo voucher ' . $code);
                $flash = 'Đã tạo voucher mới.';
            }
        }
    }
}

$vouchers = $conn->query("SELECT * FROM vouchers ORDER BY id DESC");

require __DIR__ . '/../includes/admin_header.php';
?>
    <?php if ($flash): ?><div class="flash ok"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="flash error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <div class="card">
    <h3>➕ Thêm voucher mới</h3>
    <form method="post">
      <input type="hidden" name="action" value="create">
      <label for="code">Mã voucher</label>
      <input type="text" id="code" name="code" required style="text-transform:uppercase;">

      <label for="description">Mô tả</label>
      <input type="text" id="description" name="description">

      <label for="discount_type">Loại giảm giá</label>
      <select id="discount_type" name="discount_type">
        <option value="percent">Phần trăm (%)</option>
        <option value="amount">Số tiền cố định (đ)</option>
      </select>

      <label for="discount_value">Giá trị giảm</label>
      <input type="number" id="discount_value" name="discount_value" min="0" step="0.01" required>

      <label for="min_order">Đơn tối thiểu (đ)</label>
      <input type="number" id="min_order" name="min_order" min="0" step="1000" value="0">

      <label for="max_uses">Số lượt dùng tối đa (để trống = không giới hạn)</label>
      <input type="number" id="max_uses" name="max_uses" min="1">

      <label for="expires_at">Ngày hết hạn</label>
      <input type="date" id="expires_at" name="expires_at">

      <button type="submit">Tạo voucher</button>
    </form>
  </div>

  <div class="card">
    <h3>Danh sách voucher</h3>
    <div class="table-wrap"><table>
      <tr><th>Mã</th><th>Mô tả</th><th>Giảm</th><th>Đơn tối thiểu</th><th>Đã dùng</th><th>Hết hạn</th><th>Trạng thái</th><th></th></tr>
      <?php while ($v = $vouchers->fetch_assoc()): ?>
        <tr>
          <td><strong><?php echo htmlspecialchars($v['code']); ?></strong></td>
          <td><?php echo htmlspecialchars($v['description'] ?? ''); ?></td>
          <td><?php echo $v['discount_type'] === 'percent' ? (float)$v['discount_value'] . '%' : number_format((float)$v['discount_value'], 0, ',', '.') . 'đ'; ?></td>
          <td><?php echo number_format((float)$v['min_order'], 0, ',', '.'); ?>đ</td>
          <td><?php echo (int)$v['used_count'] . ($v['max_uses'] !== null ? ' / ' . (int)$v['max_uses'] : ''); ?></td>
          <td><?php echo htmlspecialchars($v['expires_at'] ?? '—'); ?></td>
          <td><span class="chip <?php echo $v['active'] ? 'ok' : 'gray'; ?>"><?php echo $v['active'] ? 'Đang bật' : 'Đã tắt'; ?></span></td>
          <td>
            <form method="post" style="display:inline;">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
              <button type="submit" class="btn-sm btn-secondary"><?php echo $v['active'] ? 'Tắt' : 'Bật'; ?></button>
            </form>
            <form method="post" style="display:inline;" onsubmit="return confirm('Xoá voucher này?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
              <button type="submit" class="btn-sm btn-danger">Xoá</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    </table></div>
  </div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
