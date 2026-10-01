<?php
/**
 * Cấu hình kết nối cơ sở dữ liệu.
 * Mặc định khớp với cấu hình MySQL của Laragon (user root, không mật khẩu).
 * Nếu MySQL của bạn có mật khẩu khác, chỉnh lại DB_PASS bên dưới.
 */
$DB_HOST = '127.0.0.1';
$DB_NAME = 'minimart';
$DB_USER = 'root';
$DB_PASS = '';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    die('Kết nối cơ sở dữ liệu thất bại: ' . $conn->connect_error .
        '<br>Kiểm tra: đã import sql/schema.sql chưa? MySQL trong Laragon đã Start chưa?');
}

$conn->set_charset('utf8mb4');
