<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$me = current_user();
$page_title = 'Đăng ký';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $email === '' || $password === '') {
        $error = 'Vui lòng điền đầy đủ thông tin.';
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
        // Whitelist: chỉ chữ cái không dấu, chữ số và dấu gạch dưới (_), dài 3-30 ký tự.
        // Chặn mọi ký tự đặc biệt như ' " # - ; khoảng trắng... khi tạo tài khoản mới.
        $error = 'Tên đăng nhập chỉ gồm chữ cái không dấu, chữ số và dấu gạch dưới (_), dài 3-30 ký tự. Không được chứa ký tự đặc biệt như \' # - hoặc khoảng trắng.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không đúng định dạng.';
    } elseif (mb_strlen($password) < 8) {
        $error = 'Mật khẩu phải có ít nhất 8 ký tự.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = 'Mật khẩu phải chứa cả chữ và số.';
    } elseif (($_POST['confirm_password'] ?? '') !== $password) {
        $error = 'Xác nhận mật khẩu không khớp.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param('ss', $username, $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Tên đăng nhập hoặc email đã được sử dụng.';
        } else {
            // [VULN-A07 Authentication Failures] Băm mật khẩu bằng md5(), không có salt,
            // không dùng password_hash()/BCRYPT. Không có yêu cầu độ mạnh mật khẩu.
            // Xem WSTG-ATHN-02 (Testing for Weak Password Policy) và WSTG-CRYP.
            $hash = md5($password);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'customer')");
            $stmt->bind_param('sss', $username, $email, $hash);
            $stmt->execute();
            $newId = $stmt->insert_id;

            $token = 'tok_' . bin2hex(random_bytes(12));
            $tstmt = $conn->prepare("INSERT INTO api_tokens (user_id, token) VALUES (?, ?)");
            $tstmt->bind_param('is', $newId, $token);
            $tstmt->execute();

            // [VULN-SESSION] Không gọi session_regenerate_id() sau khi tạo phiên đăng nhập mới.
            $_SESSION['user_id'] = $newId;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'customer';

            header('Location: index.php');
            exit;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="panel" style="max-width:420px;">
  <h2>Tạo tài khoản</h2>
  <?php if ($error): ?><div class="flash error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <form method="post">
    <label for="username">Tên đăng nhập</label>
    <input type="text" id="username" name="username" required minlength="3" maxlength="30" pattern="[A-Za-z0-9_]{3,30}" title="Chỉ gồm chữ cái không dấu, chữ số và dấu gạch dưới (_)">
    <small class="muted">3-30 ký tự: chữ không dấu, số, dấu gạch dưới (_). Không dùng ' " # - hay khoảng trắng.</small>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" required placeholder="vd: ten@example.com">

    <label for="password">Mật khẩu</label>
    <input type="password" id="password" name="password" required minlength="8"
           pattern="(?=.*[A-Za-z])(?=.*\d).{8,}"
           title="Ít nhất 8 ký tự, gồm cả chữ và số">
    <small class="muted">Ít nhất 8 ký tự, gồm cả chữ và số.</small>

    <label for="confirm_password">Nhập lại mật khẩu</label>
    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">

    <button type="submit">Đăng ký</button>
  </form>
  <p class="muted" style="margin-top:14px;">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
