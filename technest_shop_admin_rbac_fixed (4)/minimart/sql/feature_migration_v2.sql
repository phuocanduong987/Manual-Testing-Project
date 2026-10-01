-- Migration v2: các cột/bảng phục vụ tính năng mới (không đụng tới cấu trúc
-- phục vụ bài lab pentest — chỉ CỘNG THÊM, không sửa/xoá cột cũ).
-- Dùng file này NẾU bạn đã có DB từ trước. Nếu import lại schema.sql (đã cập
-- nhật) từ đầu thì KHÔNG cần chạy file này.

USE minimart;

-- Danh mục con (danh mục bậc 2) cho sản phẩm
ALTER TABLE products ADD COLUMN subcategory VARCHAR(50) DEFAULT NULL AFTER category;

UPDATE products SET subcategory = 'ban-phim'  WHERE name = 'Bàn phím cơ TechNest K1';
UPDATE products SET subcategory = 'chuot'     WHERE name = 'Chuột không dây M-Fly';
UPDATE products SET subcategory = 'tai-nghe'  WHERE name = 'Tai nghe chụp tai Aero';
UPDATE products SET subcategory = 'lot-chuot' WHERE name = 'Bàn di chuột Neo Pad';
UPDATE products SET subcategory = 'webcam'    WHERE name = 'Webcam HD Clarity';
UPDATE products SET subcategory = 'loa'       WHERE name = 'Loa Bluetooth Wave 20';
UPDATE products SET subcategory = 'o-cung'    WHERE name = 'Ổ cứng di động Vault 1TB';
UPDATE products SET subcategory = 'man-hinh'  WHERE name = 'Màn hình 24 inch ViewMax';
UPDATE products SET subcategory = 'gia-do'    WHERE name = 'Giá đỡ laptop Stand Air';
UPDATE products SET subcategory = 'ghe'       WHERE name = 'Ghế công thái học ErgoSit';
UPDATE products SET subcategory = 'den-ban'   WHERE name = 'Đèn bàn LED FocusLight';
UPDATE products SET subcategory = 'balo'      WHERE name = 'Balo laptop UrbanPack 15.6"';
UPDATE products SET subcategory = 'sac'       WHERE name = 'Bộ sạc nhanh PowerDock 65W';
UPDATE products SET subcategory = 'cap-sac'   WHERE name = 'Cáp USB-C to USB-C 100W';
UPDATE products SET subcategory = 'micro'     WHERE name = 'Micro thu âm PodCast One';

-- Thông tin giao hàng / giảm giá trên đơn hàng
ALTER TABLE orders ADD COLUMN address VARCHAR(255) DEFAULT NULL AFTER status;
ALTER TABLE orders ADD COLUMN shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER address;
ALTER TABLE orders ADD COLUMN voucher_code VARCHAR(30) DEFAULT NULL AFTER shipping_fee;
ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER voucher_code;

-- Voucher / mã giảm giá
CREATE TABLE IF NOT EXISTS vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    description VARCHAR(150) DEFAULT NULL,
    discount_type ENUM('percent','amount') NOT NULL DEFAULT 'percent',
    discount_value DECIMAL(10,2) NOT NULL,
    min_order DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_uses INT DEFAULT NULL,
    used_count INT NOT NULL DEFAULT 0,
    expires_at DATE DEFAULT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO vouchers (code, description, discount_type, discount_value, min_order, max_uses, expires_at) VALUES
('TECHNEST10', 'Giảm 10% cho đơn từ 300.000đ', 'percent', 10, 300000, 100, '2026-12-31'),
('FREESHIP50', 'Giảm 50.000đ phí vận chuyển', 'amount', 50000, 0, NULL, '2026-12-31');
