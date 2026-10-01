# TechNest — Ứng dụng thực nghiệm cho đề tài Pentest OWASP Top 10 / WSTG

Đây là một web app PHP + MySQL nhỏ, được cấy **có chủ đích** 7 nhóm lỗi mà đề tài
yêu cầu kiểm thử: Broken Access Control, Injection (SQLi + XSS), Authentication
Failures, Session Management, Unrestricted File Upload, và API Authorization (BOLA).

Mỗi vị trí lỗi đều có chú thích `[VULN-...]` ngay trong code, và được tổng hợp lại
trong file `VULN_MAP.md`. **Không dùng ứng dụng này cho mục đích khác ngoài lab cá
nhân/nhóm của bạn** — nó thực sự khai thác được (kể cả RCE qua upload), nên chỉ nên
chạy trên máy local (Laragon), không public lên internet.

## 1. Cài đặt

1. Cài [Laragon](https://laragon.org/) (bản Full có sẵn Apache + PHP + MySQL).
2. Chép toàn bộ thư mục `minimart/` vào `C:\laragon\www\minimart`.
3. Mở Laragon, bấm **Start All** (khởi động Apache + MySQL).
4. Tạo database:
   - Cách 1 (khuyên dùng): mở **phpMyAdmin** hoặc **HeidiSQL** từ menu Laragon → chọn
     tab Import → chọn file `sql/schema.sql` → Go/Import.
   - Cách 2: mở **Terminal** trong Laragon rồi chạy:
     ```
     mysql -u root < C:\laragon\www\minimart\sql\schema.sql
     ```
5. Nếu MySQL của bạn có mật khẩu cho `root` (mặc định Laragon là không có mật khẩu),
   sửa lại `config/db.php` cho khớp.
6. Đảm bảo thư mục `uploads/avatars/` có quyền ghi (mặc định trên Windows là được).
7. Truy cập: `http://localhost/minimart/` (hoặc `http://minimart.test/` nếu bạn đã
   bật Auto Virtual Hosts trong Laragon).

## 2. Tài khoản mẫu

| Tài khoản | Mật khẩu | Vai trò |
|---|---|---|
| `admin` | `admin123` | admin |
| `khach` | `khach123` | customer |
| `khach2` | `khach123` | customer (chủ đơn #3) |
| `ctv` | `ctv123` | collaborator (quyền: sản phẩm + đánh giá) |

Khi đăng ký tài khoản mới, hệ thống tự tạo thêm 1 token API (xem trong trang **Hồ sơ**
sau khi đăng nhập) để bạn test nhóm lỗi "Ủy quyền API" qua Postman/Burp.

## 3. Cấu trúc thư mục

```
minimart/
├── config/db.php          # Kết nối MySQL
├── includes/               # auth.php (session), header.php, footer.php
├── assets/style.css
├── sql/schema.sql          # Schema + dữ liệu mẫu
├── uploads/avatars/        # Nơi lưu file upload (đích của lỗi upload)
├── admin/                  # Khu vực quản trị (đích của lỗi Broken Access Control)
├── api/order.php           # API JSON (đích của lỗi BOLA/Ủy quyền API)
├── index.php, login.php, register.php, logout.php
├── product.php, search.php  # Đích của Injection (SQLi) và XSS
├── profile.php               # Đích của Unrestricted File Upload
├── my_orders.php, orders.php, checkout.php  # Đích của IDOR/Broken Access Control
```

## 4. Quy trình đề nghị cho báo cáo pentest

1. Đọc `VULN_MAP.md` để biết vị trí và mã WSTG tương ứng của từng lỗi (dùng để lập
   kế hoạch kiểm thử, KHÔNG chép nguyên vào báo cáo — hãy tự thực hiện và tự viết
   PoC/bằng chứng bằng lời của bạn).
2. Dùng Burp Suite/OWASP ZAP để chặn bắt và thao túng request cho từng lỗi.
3. Ghi lại: mô tả lỗi, bằng chứng (screenshot/video), tác động, mức rủi ro
   (theo OWASP Risk Rating hoặc CVSS), khuyến nghị vá.
4. Tự viết bản vá cho từng lỗi (ví dụ: chuyển sang prepared statement, thêm kiểm tra
   quyền sở hữu, thêm `htmlspecialchars()`, đổi sang `password_hash()`, giới hạn
   loại file upload...).
5. Kiểm tra lại (retest) bằng đúng kịch bản ban đầu để chứng minh lỗi đã được xử lý,
   lập bảng so sánh trước/sau.

Nếu muốn, tôi có thể giúp bạn viết **phiên bản đã vá (patched)** của từng file khi
bạn đã hoàn thành phần "trước vá" trong báo cáo — cứ nhắn khi bạn cần.
