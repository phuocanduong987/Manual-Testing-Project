# Các thay đổi đã thêm

## 1. Đăng nhập / Đăng ký
- `register.php`: thêm kiểm tra định dạng email (`filter_var`), mật khẩu tối thiểu
  8 ký tự có cả chữ và số, ô nhập lại mật khẩu để xác nhận khớp.
- Không đổi cách băm mật khẩu (`md5()`, không salt) — đây là lỗi cố ý cho bài
  pentest riêng (xem `VULN_MAP.md`). Nếu bạn KHÔNG cần giữ lỗi này cho bài lab,
  chỉ cần đổi sang `password_hash()`/`password_verify()` ở `register.php` và
  `login.php`.

## 2. Danh sách sản phẩm
- Bỏ mục "Sản phẩm" trên thanh menu (`includes/header.php`) vì trùng chức năng
  với logo.

## 3. Chi tiết sản phẩm (`product.php`)
- Chỉ khách đã có đơn hàng ở trạng thái "Đã giao" chứa sản phẩm đó mới được
  gửi đánh giá (`user_has_purchased()` trong `includes/helpers.php`).
- Thêm nút "Quay lại danh sách sản phẩm" ở đầu trang.
- Gọn lại bố cục: ảnh + thông tin nằm cùng hàng, phần review tách panel riêng.

## 4. Tìm kiếm sản phẩm
- `index.php` giờ tự lọc/tìm kiếm ngay tại trang danh sách (dùng prepared
  statement, KHÔNG dùng chung code với `search.php`), có nút "Xóa lọc".
- Thanh bên (sidebar) hiển thị danh mục 2 cấp (`category_tree()` trong
  `includes/helpers.php`); bấm danh mục cha sẽ hiện danh mục con tương ứng.
- **`search.php` được giữ nguyên 100%** (kể cả các lỗi SQL Injection/XSS cố ý)
  vì đây là mục tiêu kiểm thử riêng theo `VULN_MAP.md`.

## 5. Giỏ hàng (`cart.php`, `assets/cart.js`)
- Có thể sửa số lượng từng sản phẩm trong giỏ.
- Thêm chọn khu vực giao hàng để tính phí ship (miễn phí từ 1.000.000đ).
- Thêm ô nhập mã voucher, kiểm tra qua `api/validate_voucher.php`.
- Nút "Mua tất cả": gửi giỏ hàng lên `checkout_cart.php`, server tự tính lại
  giá/tồn kho/ship/voucher (không tin số liệu từ trình duyệt), tạo 1 đơn hàng
  gồm nhiều sản phẩm và trừ kho.

## 6. Đơn hàng
- Thêm các trạng thái: Chờ xác nhận, Đang xử lý, Đang giao, Đã giao, Đã hủy,
  Yêu cầu hoàn hàng, Đã hoàn hàng, Từ chối hoàn hàng (`order_status_list()`).
- Khách có thể Hủy đơn (khi đơn chưa giao) hoặc Yêu cầu hoàn hàng (khi đơn đã
  giao) tại `my_orders.php` / `orders.php`, xử lý qua `order_action.php` —
  file này LUÔN kiểm tra đơn có thuộc về người đang đăng nhập không trước khi
  cho sửa (khác với `orders.php` — trang xem chi tiết vẫn giữ lỗi IDOR cố ý
  theo `VULN_MAP.md`).

## 7. Trang Admin (thư mục `admin/`)
- `admin/products.php`: thêm/sửa/xoá sản phẩm, chọn danh mục + danh mục con.
- `admin/orders.php`: xem tất cả đơn hàng, lọc theo trạng thái, đổi trạng thái
  (xác nhận, giao hàng, huỷ, duyệt/từ chối hoàn hàng...).
- `admin/vouchers.php`: tạo/bật-tắt/xoá mã giảm giá.
- Thêm menu điều hướng dùng chung `includes/admin_header.php` /
  `admin_footer.php` cho tất cả trang admin.
- **Các trang admin mới CHỈ gọi `require_login()`, giống hệt `admin/index.php`
  và `admin/users.php` có sẵn** — theo đúng lỗi Broken Access Control cố ý của
  cả khu vực `/admin` (xem `VULN_MAP.md`). Nếu muốn khoá đúng nghĩa, đổi thành
  kiểm tra `is_admin()` (kèm redirect) ở đầu mỗi file trong `admin/`.

## 8. Bắt buộc nhập địa chỉ + số điện thoại khi đặt hàng
- Thêm cột `phone` vào bảng `orders` (`sql/schema.sql`, hoặc chạy
  `sql/add_phone_migration.sql` nếu đã có DB từ trước).

## 9. Quản lý người dùng (`admin/users.php`) — Khoá/Mở khoá tài khoản, Reset mật khẩu
- Thêm cột `is_locked`, `locked_at` vào bảng `users` (`sql/schema.sql`, hoặc
  chạy `sql/add_user_status_migration.sql` nếu đã có DB từ trước).
- Trang `admin/users.php` giờ hiển thị cột "Trạng thái" (Đang hoạt động / Đã
  khoá) và có 2 nút thao tác cho từng người dùng (trừ tài khoản đang đăng nhập,
  để tránh tự khoá/tự reset chính mình):
  - **Khoá / Mở khoá**: đổi `is_locked`. `login.php` từ chối đăng nhập nếu tài
    khoản đang bị khoá dù mật khẩu đúng; `includes/auth.php::current_user()`
    còn tự đăng xuất ngay cả phiên đang mở nếu tài khoản bị khoá giữa chừng.
  - **Reset mật khẩu**: sinh mật khẩu ngẫu nhiên 8 ký tự, cập nhật vào cột
    `password`, hiển thị 1 lần trên trang để admin gửi lại cho người dùng.
    Vẫn dùng `md5()` để băm, đồng bộ với cách băm hiện có của dự án (xem mục 1
    và `VULN_MAP.md` — lỗi A07 cố ý giữ nguyên, không đổi sang
    `password_hash()` ở đây).
- Trang này vẫn chỉ gọi `require_login()` giống các trang `/admin` khác, theo
  đúng lỗi Broken Access Control cố ý của cả khu vực `/admin` (xem mục 7 và
  `VULN_MAP.md`).
- `checkout.php` ("Mua ngay" từ trang sản phẩm) trước đây tạo đơn hàng ngay
  lập tức khi bấm link, KHÔNG hỏi thông tin giao hàng. Giờ hiển thị 1 form yêu
  cầu nhập **địa chỉ** và **số điện thoại** (kiểm tra cả 2 không được để
  trống + số điện thoại đúng định dạng), chỉ tạo đơn khi submit form hợp lệ.
- `checkout_cart.php` (nút "Mua tất cả" trong giỏ hàng): đã có sẵn kiểm tra
  bắt buộc địa chỉ, nay thêm bắt buộc **số điện thoại** (`cart.php` có thêm ô
  nhập "Số điện thoại", `assets/cart.js` kiểm tra cả 2 trường trước khi gọi
  API, server (`checkout_cart.php`) cũng kiểm tra lại để không tin dữ liệu từ
  trình duyệt).
- Hiển thị số điện thoại đã lưu ở trang chi tiết đơn hàng (`orders.php`) và
  trang quản trị đơn hàng (`admin/orders.php`), cạnh địa chỉ.
- Không đụng tới bất kỳ lỗi cố ý nào khác (IDOR ở `orders.php`, Broken Access
  Control ở `/admin`, SQLi/XSS ở `search.php`,...) — xem `VULN_MAP.md`.

## 9. Đổi mật khẩu (`profile.php`)
- Thêm form "Đổi mật khẩu" trong trang Hồ sơ: yêu cầu mật khẩu hiện tại, mật
  khẩu mới (tối thiểu 8 ký tự, có cả chữ và số, giống điều kiện ở
  `register.php`) và nhập lại mật khẩu mới để xác nhận khớp.
- Không đổi cách băm mật khẩu (`md5()`, không salt) khi so khớp mật khẩu hiện
  tại hay lưu mật khẩu mới — cố ý giữ nguyên lỗi cho bài lab (xem
  `VULN_MAP.md`, mục Authentication Failures). Nếu không cần giữ lỗi này, đổi
  `md5()`/so sánh trực tiếp sang `password_hash()`/`password_verify()`.

## 10. Thích & trả lời nhận xét (`product.php`)
- Mỗi nhận xét giờ có nút "Thích" (❤️/🤍) cho **bất kỳ người dùng nào đã đăng
  nhập** (không cần mua sản phẩm); bấm lại để bỏ thích. Mỗi người chỉ thích
  được 1 lần/nhận xét (bảng `review_likes`, ràng buộc UNIQUE(review_id,
  user_id)); người chưa đăng nhập chỉ xem được số lượt thích, không bấm được.
- Nút "Trả lời" (mở form bằng thẻ `<details>`, không cần JavaScript) chỉ hiện
  cho: (1) khách đã mua và nhận sản phẩm thành công (điều kiện giống viết
  nhận xét gốc, `user_has_purchased()`), hoặc (2) tài khoản admin (được trả
  lời mọi nhận xét, không cần mua) — biến `$canReply` trong `product.php`.
  Khách chưa mua chỉ thích được, không trả lời được. Có kiểm tra lại điều
  kiện này ở phía server khi xử lý POST, không chỉ ẩn nút ở giao diện.
- Nếu người trả lời có `role = 'admin'`, trang sẽ gắn nhãn "Người bán" cạnh
  tên để phân biệt phản hồi từ shop với phản hồi của khách khác.
- Nội dung nhận xét gốc (`reviews.content`) vẫn được in ra KHÔNG qua
  `htmlspecialchars()` — đây là lỗi Stored XSS cố ý cho bài pentest, giữ
  nguyên theo `VULN_MAP.md` mục 4. Nội dung trả lời mới (`review_replies`) và
  toàn bộ phần thích **được escape an toàn bằng `htmlspecialchars()`** vì đây
  là tính năng mới, không nằm trong phạm vi lỗi cố ý của đề tài.
- Các hàm xử lý: `review_like_count()`, `user_has_liked_review()`,
  `toggle_review_like()`, `add_review_reply()`, `get_review_replies()` trong
  `includes/helpers.php`.

## Cần chạy trước khi dùng
Import lại `sql/schema.sql` (reset toàn bộ CSDL, đã có đủ cột/bảng mới) HOẶC
nếu muốn giữ dữ liệu cũ, chạy thêm `sql/feature_migration_v2.sql`, rồi
`sql/add_phone_migration.sql`, rồi `sql/add_review_likes_replies_migration.sql`.


## 11. Nâng cấp khu vực quản trị + phân quyền (admin / cộng tác viên)
- **3 vai trò**: `admin` (toàn quyền), `collaborator` (cộng tác viên - chỉ làm được việc admin cấp
  trong `users.permissions`: `products`, `reviews`, `orders`), `customer`.
- `includes/auth.php`: thêm `is_staff()`, `is_collaborator()`, `has_permission()`, `require_admin()`,
  `require_staff()`, `require_permission()`, `can_purchase()`, `audit_log()`. Vai trò/quyền được đọc từ
  DB mỗi request (không tin `$_SESSION['role']`) nên nâng/hạ quyền có hiệu lực ngay.
- **Giao diện mới** (`includes/admin_header.php`, `assets/admin.css`): thanh bên, menu tự ẩn mục không có quyền.
- `admin/index.php`: thẻ thống kê, biểu đồ doanh thu 7 ngày, sản phẩm bán chạy, sắp hết hàng, đánh giá chưa trả lời
  (mỗi khối chỉ hiện nếu có quyền tương ứng).
- `admin/users.php` (chỉ admin): tìm/lọc, **nâng khách → cộng tác viên**, tick quyền, hạ về khách, khoá/mở khoá, reset mật khẩu.
  Không thao tác được trên admin khác hay chính mình.
- `admin/reviews.php` (quyền `reviews`): xem mọi đánh giá, lọc "chưa trả lời", **trả lời với nhãn Người bán**, ẩn/hiện nhận xét;
  xoá vĩnh viễn chỉ admin. Nội dung nhận xét luôn được escape trong trang này.
- `admin/products.php` (quyền `products`): tìm kiếm, lọc sắp hết hàng, nhãn tồn kho. `admin/orders.php` (quyền `orders`):
  nhãn trạng thái; **xuất CSV chỉ admin**. `admin/vouchers.php`, `admin/logs.php`: chỉ admin.
- **Nhật ký hoạt động** (`audit_logs`): ghi mọi thao tác quản trị quan trọng, xem ở `admin/logs.php`.
- **Admin không được mua hàng**: chặn ở server (`checkout.php`, `checkout_cart.php` trả 403) và ẩn nút Mua/Giỏ hàng/
  "Đơn hàng của tôi" ở giao diện. Dữ liệu mẫu: đơn #3 chuyển sang `khach2`.
- `product.php`: nhận xét bị ẩn không hiện với khách; cộng tác viên có quyền `reviews` trả lời được không cần mua.
- **Lưu ý lab**: lỗi cố ý #1 (Broken Access Control ở `/admin`) được GIỮ LẠI qua hằng `VULN_ADMIN_BAC = true`
  trong `includes/auth.php` (xem mục 13). Các lỗi khác (SQLi/XSS ở `search.php`, IDOR `orders.php`, upload, API, md5, session...) giữ nguyên.
- Migration cho DB cũ: `sql/rbac_admin_upgrade_migration.sql`; hoặc import lại `sql/reset_database.sql`.

## 12. Cập nhật: cộng tác viên cũng không được mua hàng + ràng buộc username khi đăng ký
- `can_purchase()` giờ chặn cả admin lẫn cộng tác viên (`!is_staff()`), áp dụng ở `checkout.php`,
  `checkout_cart.php` (403) và ẩn nút Mua/Giỏ hàng/"Đơn hàng của tôi" ở giao diện.
- `register.php`: username phải khớp `^[A-Za-z0-9_]{3,30}$` (chữ không dấu, số, gạch dưới). Kiểm tra cả ở server
  lẫn thuộc tính `pattern` ở form. Chỉ áp dụng cho tài khoản MỚI: `login.php` vẫn nối chuỗi SQL cố ý (VULN-A07),
  nên bypass `admin' --` vẫn thử được trên các tài khoản có sẵn như `admin`.

## 13. Giữ lại lỗi Broken Access Control cố ý (VULN-A01)
- `includes/auth.php`: thêm hằng `VULN_ADMIN_BAC` (mặc định `true`). Khi bật, `require_admin()`,
  `require_staff()`, `require_permission()` chỉ gọi `require_login()` - khách hàng đã đăng nhập vẫn vào và dùng
  được mọi trang `/admin/*` (kể cả trang chỉ dành cho cộng tác viên/admin) nếu gõ trực tiếp URL.
- Menu và các khối số liệu vẫn ẩn theo vai trò thật, nên đây là lỗi kiểu "ẩn ở giao diện nhưng server không chặn".
- Đặt `VULN_ADMIN_BAC = false` để bật phân quyền thật (403) cho bước "sau vá" trong báo cáo.
- Các ràng buộc khác vẫn hoạt động: admin/cộng tác viên không mua được hàng (`can_purchase()`), nút xoá đánh giá và xuất CSV
  vẫn kiểm tra `is_admin()` (không thuộc lỗi cố ý).

## 14. Sửa: khu vực /admin hiển thị đầy đủ khi lỗi BAC đang bật
- Trước đó menu/khối số liệu vẫn ẩn theo vai trò thật và có dòng gợi ý "xem VULN-A01" -> làm lộ lỗi và khiến khách
  thấy trang trống giống như bị chặn. Nay khi `VULN_ADMIN_BAC = true`, `/admin` hiện đủ menu, số liệu, nút Xoá/Xuất CSV
  cho mọi người đã đăng nhập (giống trang admin gốc của đề tài). Thêm `ui_can()/ui_admin()/ui_staff()` trong `auth.php`.
- Nhãn vai trò ở thanh bên hiển thị đúng vai trò thật (khách hàng không còn bị ghi là "Cộng tác viên").
- Đã bỏ dòng gợi ý lộ lỗi trên giao diện. Link "Quản trị" ở menu chính vẫn chỉ hiện với admin/CTV (cần tự đoán URL).
