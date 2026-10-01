# Bảng ánh xạ lỗi cố ý — TechNest

Dùng bảng này làm điểm khởi đầu để lập kế hoạch kiểm thử (test plan) trong báo cáo.
Với mỗi lỗi, hãy tự thực hiện thao tác kiểm thử, tự chụp bằng chứng, và tự đánh giá
rủi ro — bảng này chỉ chỉ đường, không thay cho phần thực nghiệm của bạn.

| # | Nhóm lỗi (đề tài) | OWASP Top 10:2025 | WSTG | Vị trí | Gợi ý kiểm thử |
|---|---|---|---|---|---|
| 1 | Kiểm soát truy cập bị hỏng (Broken Access Control) — khu vực quản trị (leo thang ngang + dọc: khách → cộng tác viên → admin) | A01:2025 | WSTG-ATHZ-01/02 | `admin/*.php` (kể cả `admin/export_users.php`), `includes/auth.php` (hằng `VULN_ADMIN_BAC = true`) | Đăng nhập `khach/khach123`, gõ trực tiếp `/admin/products.php`, `/admin/reviews.php`, `/admin/orders.php` (việc của cộng tác viên) và `/admin/users.php`, `/admin/vouchers.php`, `/admin/logs.php`, `/admin/export_users.php` (việc của admin — riêng trang export tải ngay được file CSV chứa email/vai trò/quyền của TOÀN BỘ người dùng, không cần bấm nút gì trong giao diện). Thử tự nâng quyền lên **admin** bằng cách POST `action=promote_admin&id=<id của khach>` tới `/admin/users.php` (PoC leo thang dọc mạnh nhất: khách hàng tự cấp cho mình toàn quyền hệ thống). Tài khoản `admin` gốc (username `admin`) được chặn không cho hạ quyền (`action=demote_admin`) dù bằng cách nào, nhưng một admin **khác** do lỗi này tạo ra (ví dụ chính `khach` sau khi tự nâng) vẫn hạ được lẫn nhau — minh hoạ hệ thống mất kiểm soát hoàn toàn về phân quyền trừ tài khoản gốc. Vá: đặt `VULN_ADMIN_BAC = false` → trả 403 |
| 2 | Kiểm soát truy cập bị hỏng — IDOR trên đơn hàng | A01:2025 | WSTG-ATHZ-04 | `orders.php?id=` | Đăng nhập `khach`, tạo 1 đơn, sau đó đổi `id` trên URL sang đơn của user khác (id=3 thuộc `khach2`) |
| 3 | Tiêm — SQL Injection | A05:2025 | WSTG-INPV-05 | `search.php` (tham số `q`) | Nhập `' OR '1'='1` vào ô tìm kiếm; hoặc chạy sqlmap nhằm vào `search.php?q=` |
| 4 | XSS | A05:2025 | WSTG-INPV-01 (reflected), WSTG-INPV-02 (stored) | `search.php` (reflected, tham số `q`), `product.php` phần nhận xét (stored) | Reflected: `?q=<script>alert(1)</script>`. Stored: gửi nhận xét chứa `<script>` rồi tải lại trang |
| 5 | Lỗi xác thực (Authentication Failures) | A07:2025 | WSTG-ATHN-02, WSTG-ATHN-03, WSTG-ATHN-04 | `register.php`, `login.php` | Kiểm tra: mật khẩu chỉ băm bằng `md5()`; không giới hạn số lần đăng nhập sai (thử Burp Intruder); câu SQL đăng nhập nối chuỗi trực tiếp, thử bypass bằng `username = admin' --` (password bất kỳ) |
| 6 | Quản lý phiên (Session Management) | Liên quan A07:2025 | WSTG-SESS-02, WSTG-SESS-03, WSTG-SESS-06, WSTG-SESS-07 | `includes/auth.php`, `login.php`, `logout.php` | Dùng DevTools/Burp xem cờ cookie `PHPSESSID` (thiếu HttpOnly/Secure/SameSite); kiểm tra session ID có đổi sau khi đăng nhập không; kiểm tra phiên có hết hạn theo thời gian không. Riêng logout: lấy PHPSESSID của nạn nhân (fixation/sniff), sau đó nạn nhân bấm Đăng xuất, rồi thử gửi lại đúng cookie đó — vẫn còn đăng nhập vì server không huỷ session |
| 7 | Tải lên tệp (Unrestricted File Upload) | A02:2025 / A06:2025 | WSTG-BUSL-09 | `profile.php` (upload avatar) | Thử tải lên file `.php` chứa `<?php echo shell_exec($_GET['c']); ?>`, sau đó truy cập `uploads/avatars/<tên file>.php?c=whoami` |
| 8 | Ủy quyền API (API Authorization / BOLA) | A01:2025 (+ API Security Top 10: API1 BOLA) | Phần API testing của WSTG | `api/order.php` | Lấy token của `khach` (trang Hồ sơ), gọi `GET /api/order.php?id=3&token=<token của khach>` để đọc đơn hàng #3 (thuộc `admin`) |
| 9 | Xác thực API (Broken Authentication) | API Security Top 10: API2 | WSTG-ATHN | `api/order.php` | Gọi `GET /api/order.php?id=1` không kèm `token`, hoặc kèm token giả bất kỳ — API vẫn trả dữ liệu đơn hàng thay vì 401 |
| 10 | Tin giá từ client (Trusting Client Input / Price Tampering) | API Security Top 10: API3 | Kiểm thử luồng nghiệp vụ (Business Logic) | `checkout_cart.php` (mua nhiều sản phẩm), `checkout.php` (mua 1 sản phẩm) | `checkout_cart.php`: dùng Burp sửa `price` của từng item trong body JSON. `checkout.php`: sửa trường `price` trong body form POST (vd: về 0). Cả hai đều tạo đơn theo giá đã sửa thay vì giá thật trong DB |

## Gợi ý cấu trúc khi đưa vào báo cáo

Với mỗi dòng trên, viết theo mẫu:
- **Mô tả lỗi**: nguyên nhân kỹ thuật (thiếu kiểm tra gì, ở đâu).
- **Bằng chứng**: request/response chụp từ Burp/ZAP, ảnh chụp màn hình, hoặc video.
- **Tác động**: dữ liệu/khả năng gì bị lộ hoặc bị chiếm quyền (ví dụ: đọc đơn hàng
  người khác, chèn script vào trang, chạy lệnh trên server...).
- **Mức rủi ro**: tính theo OWASP Risk Rating (Likelihood × Impact) hoặc CVSS.
- **Khuyến nghị**: hướng vá cụ thể (không cần viết code ở bước này, chỉ cần hướng).

Sau khi bạn tự vá xong, quay lại đây báo cho tôi biết bạn đã sửa gì — tôi có thể
review hoặc giúp bạn viết bản patch hoàn chỉnh + bảng so sánh trước/sau.
