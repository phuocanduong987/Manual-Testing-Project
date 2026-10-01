<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

/*
 * [VULN-A01 Broken Access Control] Xuất toàn bộ dữ liệu người dùng ra CSV.
 * Cũng như các trang admin khác, require_admin() chỉ kiểm tra "đã đăng nhập" khi
 * VULN_ADMIN_BAC = true (xem includes/auth.php), không kiểm tra vai trò thật.
 * -> Một khách hàng đã đăng nhập gõ thẳng URL /admin/export_users.php là tải được
 * ngay file CSV chứa email, số điện thoại, vai trò của TOÀN BỘ người dùng trong hệ
 * thống — mô phỏng một vụ rò rỉ dữ liệu cá nhân hàng loạt (mass data leak / A01:2025).
 * Vá: đặt VULN_ADMIN_BAC = false để require_admin() trả 403 cho vai trò không phải admin.
 */
require_admin('../login.php');

audit_log('user.export', 'Xuất CSV danh sách người dùng');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="danh_sach_nguoi_dung_' . date('Y-m-d_His') . '.csv"');

$out = fopen('php://output', 'w');
// BOM để Excel hiển thị đúng tiếng Việt UTF-8
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['ID', 'Tên đăng nhập', 'Email', 'Vai trò', 'Quyền', 'Trạng thái', 'Ngày tạo']);

$result = $conn->query(
    "SELECT id, username, email, role, permissions, is_locked, created_at FROM users ORDER BY id"
);
while ($row = $result->fetch_assoc()) {
    fputcsv($out, [
        $row['id'],
        $row['username'],
        $row['email'],
        $row['role'],
        $row['permissions'],
        ((int)$row['is_locked'] === 1) ? 'Đã khoá' : 'Hoạt động',
        $row['created_at'],
    ]);
}
fclose($out);
exit;
