-- Migration: thêm cột category vào bảng products đã có sẵn (không mất dữ liệu users/orders).
-- Dùng file này NẾU bạn đã import schema.sql từ trước và không muốn reset lại toàn bộ CSDL.
-- Nếu bạn import lại schema.sql mới (đã cập nhật) thì KHÔNG cần chạy file này nữa.

USE minimart;

ALTER TABLE products ADD COLUMN category VARCHAR(50) DEFAULT NULL AFTER stock;

UPDATE products SET category = 'phu-kien-may-tinh' WHERE name = 'Bàn phím cơ TechNest K1';
UPDATE products SET category = 'phu-kien-may-tinh' WHERE name = 'Chuột không dây M-Fly';
UPDATE products SET category = 'am-thanh'          WHERE name = 'Tai nghe chụp tai Aero';
UPDATE products SET category = 'phu-kien-may-tinh' WHERE name = 'Bàn di chuột Neo Pad';
UPDATE products SET category = 'phu-kien-may-tinh' WHERE name = 'Webcam HD Clarity';
UPDATE products SET category = 'am-thanh'          WHERE name = 'Loa Bluetooth Wave 20';
UPDATE products SET category = 'luu-tru-ket-noi'   WHERE name = 'Ổ cứng di động Vault 1TB';
UPDATE products SET category = 'man-hinh-gia-do'   WHERE name = 'Màn hình 24 inch ViewMax';
UPDATE products SET category = 'man-hinh-gia-do'   WHERE name = 'Giá đỡ laptop Stand Air';
UPDATE products SET category = 'noi-that-van-phong' WHERE name = 'Ghế công thái học ErgoSit';
UPDATE products SET category = 'noi-that-van-phong' WHERE name = 'Đèn bàn LED FocusLight';
UPDATE products SET category = 'phu-kien-di-dong'  WHERE name = 'Balo laptop UrbanPack 15.6"';
UPDATE products SET category = 'phu-kien-di-dong'  WHERE name = 'Bộ sạc nhanh PowerDock 65W';
UPDATE products SET category = 'luu-tru-ket-noi'   WHERE name = 'Cáp USB-C to USB-C 100W';
UPDATE products SET category = 'am-thanh'          WHERE name = 'Micro thu âm PodCast One';
