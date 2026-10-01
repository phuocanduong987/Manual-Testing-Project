<?php
require_once __DIR__ . '/includes/auth.php';

/*
 * [VULN-SESSION] Đăng xuất KHÔNG huỷ session ở phía server (không gọi session_destroy()
 * hay xoá file session trên server), mà chỉ xoá cookie PHPSESSID ở trình duyệt hiện tại.
 * -> Nếu kẻ tấn công đã có được session ID này từ trước (session fixation, sniff cookie,
 * XSS...), session đó VẪN CÒN HIỆU LỰC trên server sau khi chủ tài khoản đã logout.
 * Kẻ tấn công gửi lại đúng cookie PHPSESSID cũ vẫn đăng nhập được, dù nạn nhân tưởng
 * đã đăng xuất an toàn. Xem WSTG-SESS-06 (Testing for Logout Functionality).
 */
setcookie(session_name(), '', time() - 3600, '/');
unset($_SESSION);
header('Location: login.php');
exit;
