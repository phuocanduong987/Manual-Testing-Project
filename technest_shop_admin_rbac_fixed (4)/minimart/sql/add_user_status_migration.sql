-- Migration: thêm cột trạng thái khoá tài khoản vào bảng users đã có sẵn.
-- Dùng file này NẾU bạn đã import schema.sql (hoặc các migration trước đó) từ
-- trước và không muốn reset lại toàn bộ CSDL.
-- Nếu bạn import lại schema.sql mới (đã cập nhật) từ đầu thì KHÔNG cần chạy
-- file này.
--
-- Mục đích: phục vụ chức năng "Khoá / Mở khoá tài khoản" và "Reset mật khẩu"
-- ở trang admin/users.php.

USE minimart;

ALTER TABLE users ADD COLUMN is_locked TINYINT(1) NOT NULL DEFAULT 0 AFTER avatar;
ALTER TABLE users ADD COLUMN locked_at TIMESTAMP NULL DEFAULT NULL AFTER is_locked;
