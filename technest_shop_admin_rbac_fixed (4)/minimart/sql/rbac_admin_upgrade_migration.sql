-- Nâng cấp phân quyền (admin / cộng tác viên / khách) + nhật ký hoạt động + ẩn nhận xét.
-- Chạy 1 lần trên DB đã có sẵn dữ liệu (nếu import lại schema.sql/reset_database.sql thì KHÔNG cần).
USE minimart;

ALTER TABLE users MODIFY role ENUM('admin','collaborator','customer') NOT NULL DEFAULT 'customer';
ALTER TABLE users ADD COLUMN permissions VARCHAR(100) NOT NULL DEFAULT '' AFTER role;
ALTER TABLE reviews ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0 AFTER content;

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    username VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    detail VARCHAR(255) DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at)
);

-- Admin không được mua hàng: nếu DB cũ có đơn hàng thuộc tài khoản admin (vd. đơn #3 trong dữ liệu mẫu),
-- chuyển chúng sang một khách hàng thật để dữ liệu không mâu thuẫn.
INSERT IGNORE INTO users (username, email, password, role) VALUES
('khach2', 'khach2@minimart.local', MD5('khach123'), 'customer');
UPDATE orders SET user_id = (SELECT id FROM users WHERE username = 'khach2')
WHERE user_id IN (SELECT id FROM users WHERE role = 'admin');

-- Tuỳ chọn: tạo sẵn 1 cộng tác viên mẫu (ctv / ctv123)
INSERT IGNORE INTO users (username, email, password, role, permissions) VALUES
('ctv', 'ctv@minimart.local', MD5('ctv123'), 'collaborator', 'products,reviews');
