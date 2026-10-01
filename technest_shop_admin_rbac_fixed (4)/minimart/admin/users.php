<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Chỉ admin được quản lý người dùng và phân quyền.
require_admin('../login.php');
$me = current_user();
$page_title = 'Người dùng & phân quyền';
$flash = '';
$error = '';
$generated_password = null;
$id = 0;
$validPerms = array_keys(permission_list());

/** Lấy tài khoản đích; chỉ cho phép thao tác trên tài khoản KHÔNG phải admin. */
function load_target(mysqli $conn, int $id): ?array {
    $s = $conn->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $s->bind_param('i', $id);
    $s->execute();
    return $s->get_result()->fetch_assoc() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $target = load_target($conn, $id);

    if (!$target) {
        $error = 'Không tìm thấy người dùng.';
    } elseif ($id === (int)$me['id']) {
        $error = 'Không thể thực hiện thao tác này trên chính tài khoản đang đăng nhập.';
    } elseif ($target['role'] === 'admin' && $action !== 'demote_admin') {
        $error = 'Không thể thao tác trên tài khoản quản trị viên khác.';
    } elseif ($action === 'demote_admin' && $target['username'] === 'admin') {
        // Tài khoản admin gốc (root) của hệ thống không thể bị hạ quyền, kể cả bởi
        // một admin khác đã chiếm được quyền qua lỗi promote_admin ở trên.
        $error = 'Không thể hạ quyền tài khoản quản trị viên gốc (root) của hệ thống.';
    } elseif ($action === 'lock') {
        $stmt = $conn->prepare("UPDATE users SET is_locked = 1, locked_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $id); $stmt->execute();
        audit_log('user.lock', 'Khoá tài khoản ' . $target['username']);
        $flash = 'Đã khoá tài khoản.';
    } elseif ($action === 'unlock') {
        $stmt = $conn->prepare("UPDATE users SET is_locked = 0, locked_at = NULL WHERE id = ?");
        $stmt->bind_param('i', $id); $stmt->execute();
        audit_log('user.unlock', 'Mở khoá tài khoản ' . $target['username']);
        $flash = 'Đã mở khoá tài khoản.';
    } elseif ($action === 'reset_password') {
        // Giữ md5() để đồng bộ với login.php/register.php (lỗi A07 cố ý của bài lab).
        $new_password = bin2hex(random_bytes(4));
        $hashed = md5($new_password);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param('si', $hashed, $id); $stmt->execute();
        audit_log('user.reset_password', 'Đặt lại mật khẩu ' . $target['username']);
        $generated_password = $new_password;
        $flash = 'Đã đặt lại mật khẩu. Hãy gửi mật khẩu mới bên dưới cho người dùng (chỉ hiển thị 1 lần).';
    } elseif ($action === 'promote' || $action === 'set_perms') {
        // Nâng khách -> cộng tác viên, hoặc sửa quyền của cộng tác viên hiện có.
        $perms = array_values(array_intersect($validPerms, (array)($_POST['perms'] ?? [])));
        $permStr = implode(',', $perms);
        $stmt = $conn->prepare("UPDATE users SET role = 'collaborator', permissions = ? WHERE id = ?");
        $stmt->bind_param('si', $permStr, $id); $stmt->execute();
        audit_log($action === 'promote' ? 'user.promote' : 'user.set_perms',
                  $target['username'] . ' → quyền: ' . ($permStr ?: '(không)'));
        $flash = $action === 'promote'
            ? 'Đã nâng ' . $target['username'] . ' lên cộng tác viên.'
            : 'Đã cập nhật quyền của ' . $target['username'] . '.';
    } elseif ($action === 'demote') {
        $stmt = $conn->prepare("UPDATE users SET role = 'customer', permissions = '' WHERE id = ?");
        $stmt->bind_param('i', $id); $stmt->execute();
        audit_log('user.demote', 'Hạ ' . $target['username'] . ' về khách hàng');
        $flash = 'Đã hạ ' . $target['username'] . ' về khách hàng.';
    } elseif ($action === 'promote_admin') {
        /*
         * [VULN-A01 Broken Access Control] Nâng thẳng một tài khoản bất kỳ (kể cả
         * chính khách hàng vừa tạo) lên role 'admin' — toàn quyền hệ thống.
         * Trang này đáng lẽ chỉ admin thật mới gọi tới được, nhưng vì VULN_ADMIN_BAC = true
         * (xem includes/auth.php), require_admin() không chặn vai trò, nên BẤT KỲ khách hàng
         * nào đã đăng nhập cũng POST được action=promote_admin&id=<id của chính họ> tới đây
         * và tự cấp cho mình quyền admin cao nhất, không cần admin thật phê duyệt.
         * Đây là PoC leo thang dọc (vertical privilege escalation) mạnh nhất của bài lab.
         */
        $stmt = $conn->prepare("UPDATE users SET role = 'admin', permissions = '' WHERE id = ?");
        $stmt->bind_param('i', $id); $stmt->execute();
        audit_log('user.promote_admin', 'Nâng ' . $target['username'] . ' lên QUẢN TRỊ VIÊN (toàn quyền)');
        $flash = 'Đã nâng ' . $target['username'] . ' lên quản trị viên (toàn quyền hệ thống).';
    } elseif ($action === 'demote_admin') {
        // Đối xứng với promote_admin: hạ một admin (không phải chính mình) về khách hàng.
        $stmt = $conn->prepare("UPDATE users SET role = 'customer', permissions = '' WHERE id = ?");
        $stmt->bind_param('i', $id); $stmt->execute();
        audit_log('user.demote_admin', 'Hạ ' . $target['username'] . ' khỏi quản trị viên về khách hàng');
        $flash = 'Đã hạ ' . $target['username'] . ' khỏi quản trị viên.';
    }
}

$reset_target = $generated_password !== null ? load_target($conn, $id) : null;

// Tìm kiếm + lọc vai trò (prepared statement)
$q = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$sql = "SELECT id, username, email, role, permissions, is_locked, created_at FROM users WHERE 1=1";
$types = ''; $params = [];
if ($q !== '') { $sql .= " AND (username LIKE ? OR email LIKE ?)"; $like = '%' . $q . '%'; $types .= 'ss'; $params[] = $like; $params[] = $like; }
if (in_array($roleFilter, ['admin', 'collaborator', 'customer'], true)) { $sql .= " AND role = ?"; $types .= 's'; $params[] = $roleFilter; }
$sql .= " ORDER BY FIELD(role,'admin','collaborator','customer'), id";
$stmt = $conn->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/../includes/admin_header.php';
$roleLabel = ['admin' => 'Quản trị viên', 'collaborator' => 'Cộng tác viên', 'customer' => 'Khách hàng'];
$rolePill = ['admin' => 'is-admin', 'collaborator' => 'is-collab', 'customer' => 'is-customer'];
?>
  <?php if ($flash): ?><div class="flash ok"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="flash error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <?php if ($generated_password !== null): ?>
    <div class="flash ok">Mật khẩu mới cho <strong><?php echo htmlspecialchars($reset_target['username'] ?? ''); ?></strong>:
      <code style="font-size:1.05em;"><?php echo htmlspecialchars($generated_password); ?></code> — chỉ hiển thị 1 lần, hãy gửi cho người dùng ngay.</div>
  <?php endif; ?>

  <div class="card">
    <form method="get" class="toolbar">
      <input type="search" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Tìm theo tên đăng nhập / email">
      <select name="role" onchange="this.form.submit()">
        <option value="">Tất cả vai trò</option>
        <?php foreach ($roleLabel as $k => $l): ?><option value="<?php echo $k; ?>" <?php echo $roleFilter === $k ? 'selected' : ''; ?>><?php echo $l; ?></option><?php endforeach; ?>
      </select>
      <button type="submit" class="btn-sm">Tìm</button>
    </form>
    <p style="margin:0 0 12px;"><a href="export_users.php" class="btn-sm btn-secondary">⬇ Xuất CSV danh sách người dùng</a></p>
    <p class="muted" style="margin:0 0 12px;">Nâng một khách hàng lên <strong>cộng tác viên</strong> rồi tick các quyền họ được dùng. Admin luôn có toàn quyền và không thể bị thay đổi tại đây.</p>

    <div class="table-wrap"><table>
      <tr><th>ID</th><th>Người dùng</th><th>Vai trò</th><th>Quyền</th><th>Trạng thái</th><th>Ngày tạo</th><th>Thao tác</th></tr>
      <?php foreach ($users as $u): $isSelf = (int)$u['id'] === (int)$me['id']; $uPerms = array_filter(explode(',', (string)$u['permissions'])); ?>
        <tr>
          <td>#<?php echo (int)$u['id']; ?></td>
          <td><strong><?php echo htmlspecialchars($u['username']); ?></strong><br><span class="muted"><?php echo htmlspecialchars($u['email']); ?></span></td>
          <td><span class="role-pill <?php echo $rolePill[$u['role']]; ?>"><?php echo $roleLabel[$u['role']]; ?></span></td>
          <td>
            <?php if ($u['role'] === 'admin'): ?><span class="muted">Toàn quyền</span>
            <?php elseif ($u['role'] === 'collaborator'): ?>
              <?php foreach ($uPerms as $pp): ?><span class="chip"><?php echo htmlspecialchars($pp); ?></span> <?php endforeach; ?>
              <?php if (!$uPerms): ?><span class="muted">Chưa có quyền</span><?php endif; ?>
            <?php else: ?><span class="muted">—</span><?php endif; ?>
          </td>
          <td><?php echo (int)$u['is_locked'] === 1 ? '<span class="chip bad">Đã khoá</span>' : '<span class="chip ok">Hoạt động</span>'; ?></td>
          <td class="muted"><?php echo htmlspecialchars($u['created_at']); ?></td>
          <td>
            <?php if ($isSelf): ?><span class="muted">Tài khoản hiện tại</span>
            <?php elseif ($u['role'] === 'admin' && $u['username'] === 'admin'): ?>
              <span class="muted">Admin gốc — không thể hạ quyền</span>
            <?php elseif ($u['role'] === 'admin'): ?>
              <form method="post" class="inline-form" onsubmit="return confirm('Hạ <?php echo htmlspecialchars(addslashes($u['username'])); ?> khỏi quyền admin về khách hàng?');">
                <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>"><input type="hidden" name="action" value="demote_admin">
                <button type="submit" class="btn-sm btn-secondary">Hạ khỏi Admin</button></form>
            <?php else: ?>
              <details>
                <summary style="cursor:pointer;color:var(--accent);">Quản lý ▾</summary>
                <div style="margin-top:8px;min-width:230px;">
                  <form method="post">
                    <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                    <input type="hidden" name="action" value="<?php echo $u['role'] === 'collaborator' ? 'set_perms' : 'promote'; ?>">
                    <div class="perm-box">
                      <?php foreach (permission_list() as $pk => $pl): ?>
                        <label><input type="checkbox" name="perms[]" value="<?php echo $pk; ?>" <?php echo in_array($pk, $uPerms, true) ? 'checked' : (($u['role'] === 'customer' && in_array($pk, ['products', 'reviews'], true)) ? 'checked' : ''); ?>> <?php echo htmlspecialchars($pl); ?></label>
                      <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn-sm"><?php echo $u['role'] === 'collaborator' ? 'Lưu quyền' : 'Nâng lên cộng tác viên'; ?></button>
                  </form>
                  <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">
                    <?php if ($u['role'] === 'collaborator'): ?>
                      <form method="post" class="inline-form" onsubmit="return confirm('Hạ <?php echo htmlspecialchars(addslashes($u['username'])); ?> về khách hàng?');">
                        <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>"><input type="hidden" name="action" value="demote">
                        <button type="submit" class="btn-sm btn-secondary">Hạ về khách</button></form>
                    <?php endif; ?>
                    <form method="post" class="inline-form">
                      <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                      <input type="hidden" name="action" value="<?php echo (int)$u['is_locked'] === 1 ? 'unlock' : 'lock'; ?>">
                      <button type="submit" class="btn-sm btn-secondary"><?php echo (int)$u['is_locked'] === 1 ? 'Mở khoá' : 'Khoá'; ?></button></form>
                    <form method="post" class="inline-form" onsubmit="return confirm('CẢNH BÁO: hành động này cấp TOÀN QUYỀN ADMIN cho <?php echo htmlspecialchars(addslashes($u['username'])); ?>. Tiếp tục?');">
                      <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>"><input type="hidden" name="action" value="promote_admin">
                      <button type="submit" class="btn-sm btn-secondary" style="border-color:var(--danger);color:var(--danger);">Nâng lên Admin</button></form>
                    <form method="post" class="inline-form" onsubmit="return confirm('Đặt lại mật khẩu cho <?php echo htmlspecialchars(addslashes($u['username'])); ?>?');">
                      <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>"><input type="hidden" name="action" value="reset_password">
                      <button type="submit" class="btn-sm btn-secondary">Reset mật khẩu</button></form>
                  </div>
                </div>
              </details>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$users): ?><tr><td colspan="7" class="empty">Không tìm thấy người dùng.</td></tr><?php endif; ?>
    </table></div>
  </div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
