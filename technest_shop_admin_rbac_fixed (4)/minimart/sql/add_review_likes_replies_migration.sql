-- Migration: thêm tính năng "Thích" và "Trả lời" cho nhận xét sản phẩm.
-- Dùng file này NẾU bạn đã import schema.sql (hoặc các migration trước đó) từ
-- trước và không muốn reset lại toàn bộ CSDL.
-- Nếu bạn import lại schema.sql mới (đã cập nhật) từ đầu thì KHÔNG cần chạy
-- file này nữa.
--
-- Mục đích: cho phép người dùng đã đăng nhập "thích" một nhận xét (mỗi người
-- chỉ thích được 1 lần/nhận xét, bấm lại để bỏ thích) và viết trả lời cho
-- nhận xét đó (xem product.php).

USE minimart;

CREATE TABLE IF NOT EXISTS review_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_review_user (review_id, user_id),
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS review_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
