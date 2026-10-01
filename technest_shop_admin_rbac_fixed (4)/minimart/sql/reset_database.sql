-- ============================================================
-- TechNest - RESET TOÀN BỘ DATABASE (xóa sạch rồi tạo lại)
-- Dùng file NÀY thay cho mọi file khác trong thư mục sql/ khi
-- muốn reset lại CSDL từ đầu. File này đã gộp đủ mọi thay đổi
-- từ schema.sql + toàn bộ các *_migration.sql (is_locked/locked_at,
-- category/subcategory, phone, review_likes/review_replies,
-- api_tokens, vouchers...) nên KHÔNG cần chạy thêm bất kỳ file
-- migration nào khác sau khi chạy file này.
--
-- Cách chạy (chọn 1 trong 2 cách):
--   1) HeidiSQL/phpMyAdmin (Laragon): mở tab Query/SQL, dán toàn bộ
--      nội dung file, chạy 1 lần duy nhất (không dùng nút Import file
--      để tránh bị ngắt giữa chừng).
--   2) Dòng lệnh: mysql -u root < reset_database.sql
-- ============================================================

DROP DATABASE IF EXISTS minimart;


CREATE DATABASE IF NOT EXISTS minimart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE minimart;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS review_likes;
DROP TABLE IF EXISTS review_replies;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS api_tokens;
DROP TABLE IF EXISTS vouchers;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,      -- [VULN-A07] lưu md5(), không salt, không dùng password_hash()
    role ENUM('admin','collaborator','customer') NOT NULL DEFAULT 'customer',
    permissions VARCHAR(100) NOT NULL DEFAULT '',   -- quyền của cộng tác viên: products,reviews,orders (CSV)
    avatar VARCHAR(255) DEFAULT NULL,
    is_locked TINYINT(1) NOT NULL DEFAULT 0,
    locked_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    rating DECIMAL(2,1) NOT NULL DEFAULT 4.5,
    stock INT NOT NULL DEFAULT 20,
    category VARCHAR(50) DEFAULT NULL,
    subcategory VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status VARCHAR(30) DEFAULT 'Chờ xác nhận',
    address VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    voucher_code VARCHAR(30) DEFAULT NULL,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    is_hidden TINYINT(1) NOT NULL DEFAULT 0,        -- nhận xét bị ẩn (kiểm duyệt) khỏi trang sản phẩm
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Lượt thích trên nhận xét. Mỗi user chỉ được thích 1 lần / nhận xét
-- (UNIQUE KEY), bấm thích lần nữa sẽ bỏ thích (xử lý ở product.php).
CREATE TABLE review_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_review_user (review_id, user_id),
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Trả lời cho một nhận xét (bất kỳ user nào đã đăng nhập đều trả lời được;
-- nếu người trả lời có role 'admin' thì trang sản phẩm sẽ gắn nhãn "Người bán").
CREATE TABLE review_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Nhật ký hoạt động của admin / cộng tác viên (xem admin/logs.php)
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    username VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    detail VARCHAR(255) DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at)
);

CREATE TABLE api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE vouchers (
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

-- Tài khoản mẫu
-- admin / admin123
-- khach / khach123
-- ctv / ctv123 (cộng tác viên: quản lý sản phẩm + trả lời đánh giá)
-- khach2 / khach123 (khách hàng thứ 2 - chủ đơn #3)
INSERT INTO users (username, email, password, role, permissions) VALUES
('admin', 'admin@minimart.local', MD5('admin123'), 'admin', ''),
('khach', 'khach@minimart.local', MD5('khach123'), 'customer', ''),
('ctv', 'ctv@minimart.local', MD5('ctv123'), 'collaborator', 'products,reviews'),
('khach2', 'khach2@minimart.local', MD5('khach123'), 'customer', '');

INSERT INTO api_tokens (user_id, token) VALUES
(1, 'tok_admin_9f8a7b6c5d4e3f2a'),
(2, 'tok_khach_1a2b3c4d5e6f7g8h'),
(3, 'tok_ctv_3c4d5e6f7a8b9c0d'),
(4, 'tok_khach2_5e6f7a8b9c0d1e2f');

INSERT INTO products (name, description, price, image, rating, stock, category, subcategory) VALUES
('Bàn phím cơ TechNest K1', 'Bàn phím cơ 87 phím, switch đỏ, đèn RGB.', 890000, NULL, 4.7, 12, 'phu-kien-may-tinh', 'ban-phim'),
('Chuột không dây M-Fly', 'Chuột wireless 2.4GHz, pin 6 tháng.', 350000, NULL, 4.8, 26, 'phu-kien-may-tinh', 'chuot'),
('Tai nghe chụp tai Aero', 'Tai nghe chống ồn, mic gập.', 620000, NULL, 4.5, 9, 'am-thanh', 'tai-nghe'),
('Bàn di chuột Neo Pad', 'Bàn di chuột cỡ lớn, chống trượt.', 150000, NULL, 4.6, 40, 'phu-kien-may-tinh', 'lot-chuot'),
('Webcam HD Clarity', 'Webcam 1080p, tự động lấy nét.', 480000, NULL, 4.3, 0, 'phu-kien-may-tinh', 'webcam'),
('Loa Bluetooth Wave 20', 'Loa di động chống nước IPX5, bass mạnh, pin 12 giờ.', 590000, NULL, 4.6, 18, 'am-thanh', 'loa'),
('Ổ cứng di động Vault 1TB', 'Ổ cứng ngoài USB 3.0, tốc độ đọc/ghi cao, vỏ nhôm.', 1250000, NULL, 4.9, 7, 'luu-tru-ket-noi', 'o-cung'),
('Màn hình 24 inch ViewMax', 'Màn hình IPS Full HD 75Hz, viền mỏng, chân đế xoay.', 2990000, NULL, 4.7, 5, 'man-hinh-gia-do', 'man-hinh'),
('Giá đỡ laptop Stand Air', 'Giá đỡ nhôm gấp gọn, tản nhiệt tốt, chỉnh 6 góc độ.', 320000, NULL, 4.4, 33, 'man-hinh-gia-do', 'gia-do'),
('Ghế công thái học ErgoSit', 'Ghế văn phòng tựa lưng lưới thoáng khí, có tựa đầu.', 2450000, NULL, 4.8, 4, 'noi-that-van-phong', 'ghe'),
('Đèn bàn LED FocusLight', 'Đèn học/làm việc 3 chế độ sáng, cổng sạc USB.', 275000, NULL, 4.5, 22, 'noi-that-van-phong', 'den-ban'),
('Balo laptop UrbanPack 15.6"', 'Balo chống nước, ngăn đệm laptop, cổng sạc USB ngoài.', 460000, NULL, 4.6, 15, 'phu-kien-di-dong', 'balo'),
('Bộ sạc nhanh PowerDock 65W', 'Sạc GaN 2 cổng USB-C + 1 USB-A, sạc nhanh cho laptop & điện thoại.', 399000, NULL, 4.7, 29, 'phu-kien-di-dong', 'sac'),
('Cáp USB-C to USB-C 100W', 'Cáp bện dù dài 1.5m, hỗ trợ sạc nhanh và truyền dữ liệu.', 99000, NULL, 4.4, 50, 'luu-tru-ket-noi', 'cap-sac'),
('Micro thu âm PodCast One', 'Micro USB condenser, chân đế chống rung, phù hợp livestream.', 780000, NULL, 4.5, 11, 'am-thanh', 'micro');

INSERT INTO vouchers (code, description, discount_type, discount_value, min_order, max_uses, expires_at, used_count) VALUES
('TECHNEST10', 'Giảm 10% cho đơn từ 300.000đ', 'percent', 10, 300000, 100, '2026-12-31', 1),
('FREESHIP50', 'Giảm 50.000đ phí vận chuyển', 'amount', 50000, 0, NULL, '2026-12-31', 0);

-- Đơn #1 (khach, Đã giao): 890.000 + 150.000 = 1.040.000đ subtotal >= 1.000.000đ
-- nên được miễn phí ship (xem shipping_fee_for() trong includes/helpers.php) -> total giữ nguyên 1.040.000đ.
-- Đơn #2 (khach, Đang xử lý): subtotal 350.000đ, khu vực nội thành -> phí ship 20.000đ -> total 370.000đ.
-- Đơn #3 (khach2, Đã giao): subtotal 620.000đ, áp mã TECHNEST10 (giảm 10% = 62.000đ),
-- khu vực ngoại thành -> phí ship 35.000đ -> total = 620.000 - 62.000 + 35.000 = 593.000đ.
INSERT INTO orders (user_id, total, status, address, phone, shipping_fee, voucher_code, discount_amount) VALUES
(2, 1040000, 'Đã giao',     '12 Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP.HCM',        '0901234567', 0,     NULL,          0),
(2, 370000,  'Đang xử lý',  '45 Lê Lợi, Phường Bến Thành, Quận 1, TP.HCM',           '0901234567', 20000, NULL,          0),
(4, 593000,  'Đã giao',     '8 Trần Hưng Đạo, Phường Cầu Ông Lãnh, Quận 1, TP.HCM',  '0987654321', 35000, 'TECHNEST10', 62000);

INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 1, 890000),
(1, 4, 1, 150000),
(2, 2, 1, 350000),
(3, 3, 1, 620000);

INSERT INTO reviews (product_id, user_id, content) VALUES
(1, 2, 'Gõ rất sướng, đèn LED đẹp!'),
(3, 2, 'Đeo thoải mái, chống ồn tốt.');

-- Lượt thích: admin (người bán) thích cả 2 nhận xét của khách -> demo tính năng Thích.
INSERT INTO review_likes (review_id, user_id) VALUES
(1, 1),
(2, 1);

-- Trả lời của người bán (role admin) -> demo nhãn "Người bán" trên product.php.
INSERT INTO review_replies (review_id, user_id, content) VALUES
(1, 1, 'Cảm ơn bạn đã ủng hộ TechNest! Chúc bạn dùng sản phẩm vui vẻ nhé.'),
(2, 1, 'Rất vui vì bạn hài lòng với sản phẩm. TechNest luôn sẵn sàng hỗ trợ nếu cần thêm gì ạ!');
