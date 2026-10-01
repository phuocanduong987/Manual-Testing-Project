<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

require_login();
$me = current_user();
$page_title = 'Hồ sơ';
$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword     = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_new_password'] ?? '');

    $pstmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $pstmt->bind_param('i', $me['id']);
    $pstmt->execute();
    $row = $pstmt->get_result()->fetch_assoc();

    // [VULN-A07 Authentication Failures] So khớp bằng md5() không salt, giống hệt
    // cách băm ở register.php/login.php — cố ý giữ nguyên lỗi cho bài lab, xem VULN_MAP.md.
    if (!$row || md5($currentPassword) !== $row['password']) {
        $error = 'Mật khẩu hiện tại không đúng.';
    } elseif (mb_strlen($newPassword) < 8) {
        $error = 'Mật khẩu mới phải có ít nhất 8 ký tự.';
    } elseif (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
        $error = 'Mật khẩu mới phải chứa cả chữ và số.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Xác nhận mật khẩu mới không khớp.';
    } elseif ($newPassword === $currentPassword) {
        $error = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
    } else {
        $newHash = md5($newPassword);
        $u = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $u->bind_param('si', $newHash, $me['id']);
        $u->execute();
        $flash = 'Đã đổi mật khẩu thành công.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    /*
     * [VULN-A02/A06 Unrestricted File Upload]
     * - Không kiểm tra phần mở rộng file (không whitelist .jpg/.png/.gif).
     * - Không kiểm tra MIME-type thật (finfo) mà chỉ tin vào tên file gửi lên.
     * - Giữ nguyên tên file gốc và lưu trực tiếp vào thư mục có thể truy cập qua web (uploads/avatars/).
     * -> Có thể tải lên một file .php (webshell) rồi truy cập trực tiếp để thực thi mã trên server.
     * Xem WSTG-BUSL-09 (Test Upload of Unexpected File Types) / WSTG-INPV.
     */
    $filename = basename($_FILES['avatar']['name']);
    $target = __DIR__ . '/uploads/avatars/' . $filename;

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
        $relPath = 'uploads/avatars/' . $filename;
        $u = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $u->bind_param('si', $relPath, $me['id']);
        $u->execute();
        $me['avatar'] = $relPath;
        $flash = 'Đã cập nhật ảnh đại diện.';
    } else {
        $error = 'Tải lên thất bại.';
    }
}

$tok = $conn->prepare("SELECT token FROM api_tokens WHERE user_id = ? LIMIT 1");
$tok->bind_param('i', $me['id']);
$tok->execute();
$tokenRow = $tok->get_result()->fetch_assoc();

require __DIR__ . '/includes/header.php';
?>

<div class="panel" style="max-width:520px;">
  <h2>Hồ sơ của tôi</h2>
  <?php if ($flash): ?><div class="flash ok"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="flash error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <p>
    <span class="avatar-sm">
      <?php if (!empty($me['avatar'])): ?>
        <img src="<?php echo htmlspecialchars($me['avatar']); ?>" alt="avatar">
      <?php endif; ?>
    </span>
    <strong><?php echo htmlspecialchars($me['username']); ?></strong>
  </p>
  <p class="muted">Email: <?php echo htmlspecialchars($me['email']); ?></p>
  <p class="muted">Vai trò: <span class="badge"><?php echo htmlspecialchars($me['role']); ?></span></p>

  <form method="post" enctype="multipart/form-data" id="avatar-upload">
    <label for="avatar">Cập nhật ảnh đại diện</label>
    <input type="file" id="avatar" name="avatar" required>
    <button type="submit">Tải lên</button>
  </form>

  <hr style="margin:20px 0;border-color:var(--line);">
  <h3>Đổi mật khẩu</h3>
  <form method="post">
    <input type="hidden" name="change_password" value="1">

    <label for="current_password">Mật khẩu hiện tại</label>
    <input type="password" id="current_password" name="current_password" required>

    <label for="new_password">Mật khẩu mới</label>
    <input type="password" id="new_password" name="new_password" required minlength="8"
           pattern="(?=.*[A-Za-z])(?=.*\d).{8,}"
           title="Ít nhất 8 ký tự, gồm cả chữ và số">
    <small class="muted">Ít nhất 8 ký tự, gồm cả chữ và số.</small>

    <label for="confirm_new_password">Nhập lại mật khẩu mới</label>
    <input type="password" id="confirm_new_password" name="confirm_new_password" required minlength="8">

    <button type="submit" class="btn" style="margin-top:16px;">Đổi mật khẩu</button>
  </form>

  <hr style="margin:20px 0;border-color:var(--line);">
  <p class="muted">Token API của bạn (dùng để test qua Postman/Burp):</p>
  <p><code><?php echo htmlspecialchars($tokenRow['token'] ?? ''); ?></code></p>
  <p class="muted">Ví dụ gọi API: <code>GET /api/order.php?id=1&amp;token=&lt;token&gt;</code></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
