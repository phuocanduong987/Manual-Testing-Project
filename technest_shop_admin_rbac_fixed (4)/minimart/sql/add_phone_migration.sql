-- Migration: thêm cột phone (số điện thoại) vào bảng orders đã có sẵn.
-- Dùng file này NẾU bạn đã import schema.sql/feature_migration_v2.sql từ trước
-- và không muốn reset lại toàn bộ CSDL.
-- Nếu bạn import lại schema.sql mới (đã cập nhật) từ đầu thì KHÔNG cần chạy file này.
--
-- Mục đích: bắt buộc khách phải nhập địa chỉ VÀ số điện thoại thì mới đặt hàng được
-- (xem checkout.php và checkout_cart.php).

USE minimart;

ALTER TABLE orders ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER address;
