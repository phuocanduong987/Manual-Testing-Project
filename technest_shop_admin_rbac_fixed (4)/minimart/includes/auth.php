<?php
/**
 * Quản lý session đăng nhập.
 *
 * [VULN-SESSION] File này KHÔNG gọi session_set_cookie_params() trước session_start(),
 * nên cookie phiên dùng cấu hình mặc định của php.ini (thường thiếu cờ HttpOnly/Secure/SameSite
 * nếu chưa được cấu hình riêng). Đây là điểm cần kiểm thử ở nhóm lỗi "Quản lý phiên" (WSTG-SESS-02).
 *
 * [VULN-SESSION] Không có cơ chế hết hạn phiên theo thời gian rảnh (idle timeout) -
 * xem WSTG-SESS-07 (Testing for Session Timeout).
 */
session_start();

function current_user(): ?array {
    global $conn;
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $conn->prepare("SELECT id, username, email, role, permissions, avatar FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();
    return $user ?: null;
}

/**
 * Chỉ kiểm tra "đã đăng nhập hay chưa" (+ tài khoản có bị khoá không).
 *
 * Phân quyền khu vực /admin dùng require_staff() / require_admin() / require_permission()
 * ở phía dưới, nhưng bị VÔ HIỆU HOÁ có chủ đích bởi hằng số VULN_ADMIN_BAC (xem bên dưới).
 *
 * Có kiểm tra riêng: nếu tài khoản bị admin khoá (is_locked, xem admin/users.php) sau khi
 * phiên đã được tạo — ví dụ đang đăng nhập sẵn ở một thiết bị khác — thì huỷ phiên và
 * đưa về trang đăng nhập ngay tại đây, TRƯỚC khi trang gọi current_user().
 */
function require_login(string $login_path = 'login.php'): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . $login_path);
        exit;
    }

    global $conn;
    $stmt = $conn->prepare("SELECT is_locked FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || (int)$row['is_locked'] === 1) {
        $_SESSION = [];
        session_destroy();
        $separator = (strpos($login_path, '?') === false) ? '?' : '&';
        header('Location: ' . $login_path . $separator . 'locked=1');
        exit;
    }
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * ===== PHÂN QUYỀN (RBAC) =====
 * Vai trò: admin (toàn quyền) > collaborator (cộng tác viên, chỉ làm được các việc
 * admin cấp trong cột users.permissions) > customer (khách hàng).
 *
 * Quan trọng: vai trò/quyền luôn được đọc từ DATABASE ở mỗi request (không tin
 * $_SESSION['role']), nên khi admin nâng/hạ quyền hay khoá tài khoản, hiệu lực
 * có ngay mà người dùng không cần đăng nhập lại.
 */
function permission_list(): array {
    return [
        'products' => 'Quản lý sản phẩm (thêm / sửa / xoá, tồn kho)',
        'reviews'  => 'Đánh giá (trả lời, ẩn / hiện nhận xét)',
        'orders'   => 'Đơn hàng (xem và cập nhật trạng thái)',
    ];
}

function auth_role_row(): array {
    static $cache = null;
    static $cachedId = null;
    global $conn;
    $uid = $_SESSION['user_id'] ?? null;
    if ($uid === null) {
        return ['role' => '', 'permissions' => ''];
    }
    if ($cache === null || $cachedId !== $uid) {
        $stmt = $conn->prepare("SELECT role, permissions FROM users WHERE id = ?");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $cache = $stmt->get_result()->fetch_assoc() ?: ['role' => '', 'permissions' => ''];
        $stmt->close();
        $cachedId = $uid;
    }
    return $cache;
}

function is_admin(): bool {
    return auth_role_row()['role'] === 'admin';
}

function is_collaborator(): bool {
    return auth_role_row()['role'] === 'collaborator';
}

/** Admin hoặc cộng tác viên (được vào khu vực /admin). */
function is_staff(): bool {
    return is_admin() || is_collaborator();
}

/** Admin luôn có mọi quyền; cộng tác viên chỉ có quyền được admin cấp. */
function has_permission(string $perm): bool {
    $row = auth_role_row();
    if ($row['role'] === 'admin') return true;
    if ($row['role'] !== 'collaborator') return false;
    return in_array($perm, array_filter(explode(',', (string)$row['permissions'])), true);
}

function deny_access(string $message = 'Bạn không có quyền truy cập chức năng này.'): void {
    http_response_code(403);
    $base = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false) ? '../' : '';
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>403 - TechNest</title>'
       . '<link rel="stylesheet" href="' . $base . 'assets/style.css"></head>'
       . '<body><div class="wrap"><div class="panel" style="max-width:480px;margin:60px auto;text-align:center;">'
       . '<h2 style="display:inline-block;">403 · Không có quyền</h2>'
       . '<p class="muted">' . htmlspecialchars($message) . '</p>'
       . '<a class="btn" href="' . $base . 'index.php">Về trang chủ</a>'
       . '</div></div></body></html>';
    exit;
}

/**
 * [VULN-A01 Broken Access Control] CÔNG TẮC LỖI CỐ Ý cho bài lab pentest.
 *
 * Khi VULN_ADMIN_BAC = true (mặc định, đúng như đề tài), 3 hàm require_admin() /
 * require_staff() / require_permission() bên dưới CHỈ kiểm tra "đã đăng nhập" (require_login),
 * KHÔNG kiểm tra vai trò/quyền. Hậu quả: khách hàng (customer) đã đăng nhập chỉ cần gõ URL
 * /admin/*.php là dùng được toàn bộ chức năng của cộng tác viên và admin (sửa/xoá sản phẩm,
 * trả lời/ẩn/xoá đánh giá, đổi trạng thái đơn, tự nâng quyền, khoá/reset mật khẩu người khác...).
 * Menu bên trái vẫn ẩn mục theo vai trò thật nên giao diện trông "đúng", nhưng server không chặn.
 * Xem WSTG-ATHZ-01 / WSTG-ATHZ-02.
 *
 * Đặt VULN_ADMIN_BAC = false để BẬT phân quyền thật (trả 403) - dùng cho bước "sau khi vá".
 */
const VULN_ADMIN_BAC = true;

/**
 * Helper cho GIAO DIỆN /admin. Khi VULN_ADMIN_BAC = true, khu vực /admin hiển thị đầy đủ menu,
 * số liệu và nút thao tác cho bất kỳ ai đã đăng nhập (giống trang admin gốc của đề tài, nơi
 * server không phân biệt vai trò). Khi = false, quay về hiển thị/chặn theo vai trò thật.
 */
function ui_can(string $perm): bool { return VULN_ADMIN_BAC || has_permission($perm); }
function ui_admin(): bool { return VULN_ADMIN_BAC || is_admin(); }
function ui_staff(): bool { return VULN_ADMIN_BAC || is_staff(); }

/** Chỉ admin. */
function require_admin(string $login_path = '../login.php'): void {
    require_login($login_path);
    if (VULN_ADMIN_BAC) return; // [VULN-A01] bỏ qua kiểm tra vai trò
    if (!is_admin()) deny_access('Chức năng này chỉ dành cho quản trị viên.');
}

/** Admin hoặc cộng tác viên (vào được khu vực quản trị). */
function require_staff(string $login_path = '../login.php'): void {
    require_login($login_path);
    if (VULN_ADMIN_BAC) return; // [VULN-A01] bỏ qua kiểm tra vai trò
    if (!is_staff()) deny_access('Khu vực này chỉ dành cho quản trị viên và cộng tác viên.');
}

/** Cần một quyền cụ thể (admin luôn qua). */
function require_permission(string $perm, string $login_path = '../login.php'): void {
    require_login($login_path);
    if (VULN_ADMIN_BAC) return; // [VULN-A01] bỏ qua kiểm tra quyền
    if (!has_permission($perm)) deny_access('Tài khoản của bạn chưa được cấp quyền cho chức năng này.');
}

/**
 * Nhân viên (admin + cộng tác viên) KHÔNG được mua hàng trên chính cửa hàng của mình.
 * Gọi ở mọi luồng tạo đơn (server-side) - không chỉ ẩn nút ở giao diện.
 * Muốn mua hàng, nhân viên phải dùng một tài khoản khách hàng riêng.
 */
function can_purchase(): bool {
    return is_logged_in() && !is_staff();
}

/** Ghi nhật ký hoạt động quản trị (xem admin/logs.php). */
function audit_log(string $action, string $detail = ''): void {
    global $conn;
    $uid = $_SESSION['user_id'] ?? null;
    $name = $_SESSION['username'] ?? 'system';
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $detail = mb_substr($detail, 0, 250);
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, username, action, detail, ip) VALUES (?,?,?,?,?)");
    $stmt->bind_param('issss', $uid, $name, $action, $detail, $ip);
    $stmt->execute();
    $stmt->close();
}
