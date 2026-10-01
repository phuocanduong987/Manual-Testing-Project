<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$me = current_user();
$page_title = 'Đăng nhập';
$error = '';

if (isset($_GET['locked'])) {
    // Đến từ require_login() (includes/auth.php): tài khoản bị khoá giữa chừng
    // trong khi phiên vẫn đang mở (ví dụ đăng nhập sẵn ở thiết bị khác).
    $error = 'Tài khoản của bạn đã bị khoá. Bạn đã được đăng xuất. Vui lòng liên hệ quản trị viên.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    // [VULN-A07 Authentication Failures] Không giới hạn số lần thử đăng nhập sai,
    // không có cơ chế khoá tài khoản / CAPTCHA sau nhiều lần thất bại.
    // Xem WSTG-ATHN-03 (Testing for Weak Lock Out Mechanism) / có thể brute-force bằng Burp Intruder hoặc Hydra.
    /*
     * [VULN-A07 Authentication - SQL Injection Auth Bypass]
     * Username VÀ điều kiện mật khẩu được nối trực tiếp vào câu SQL, không dùng
     * prepared statement. Cho phép bypass đăng nhập bằng cách comment phần kiểm tra
     * mật khẩu trong câu query. Xem WSTG-ATHN-04 / TC-AUTH-03.
     * Ví dụ: username = admin' --      (password bất kỳ)
     */
    $password_hash = md5($password);
    $sql = "SELECT id, username, role, is_locked FROM users WHERE username = '$username' AND password = '$password_hash'";
    $result = $conn->query($sql);
    $user = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;

    if ($user) {
        if ((int)$user['is_locked'] === 1) {
            // Tài khoản bị quản trị viên khoá (xem admin/users.php) - không cho đăng nhập
            // dù mật khẩu đúng.
            $error = 'Tài khoản của bạn đã bị khoá. Vui lòng liên hệ quản trị viên.';
        } else {
            // [VULN-SESSION] Cố ý KHÔNG gọi session_regenerate_id(true) sau khi đăng nhập thành công
            // -> nguy cơ session fixation (WSTG-SESS-03).
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: index.php');
            exit;
        }
    } else {
        $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="panel" style="max-width:420px;">
  <h2>Đăng nhập</h2>
  <?php if ($error): ?><div class="flash error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <form method="post">
    <label for="username">Tên đăng nhập</label>
    <input type="text" id="username" name="username" required>

    <label for="password">Mật khẩu</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Đăng nhập</button>
  </form>
  <p class="muted" style="margin-top:14px;">Chưa có tài khoản? <a href="register.php">Đăng ký</a></p>
  <p class="muted">Tài khoản mẫu: <code>admin/admin123</code> · <code>khach/khach123</code></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
