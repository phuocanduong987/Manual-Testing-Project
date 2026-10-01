<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$me = current_user();
$page_title = 'Kết quả tìm kiếm';
$q = $_GET['q'] ?? '';

// Danh mục / khoảng giá / sắp xếp: các bộ lọc "bình thường", không phải trọng tâm bài lab
// nên được kiểm tra qua whitelist/regex trước khi đưa vào câu SQL.
$category = $_GET['category'] ?? '';
$price_range = $_GET['price_range'] ?? '';
$sort = $_GET['sort'] ?? '';

$valid_categories = ['phu-kien-may-tinh', 'am-thanh', 'luu-tru-ket-noi', 'man-hinh-gia-do', 'noi-that-van-phong', 'phu-kien-di-dong'];
if (!in_array($category, $valid_categories, true)) {
    $category = '';
}

$valid_sorts = [
    'price_asc'  => 'price ASC',
    'price_desc' => 'price DESC',
    'rating'     => 'rating DESC',
    'name_asc'   => 'name ASC',
];
$order_by = $valid_sorts[$sort] ?? 'id ASC';

/*
 * [VULN-A05 Injection - SQL Injection]
 * Chuỗi tìm kiếm được nối trực tiếp vào câu SQL, KHÔNG dùng prepared statement.
 * Đây là điểm kiểm thử chính cho nhóm lỗi "Tiêm" (WSTG-INPV-05 SQL Injection).
 * Thử nghiệm gợi ý: nhập  ' OR '1'='1  hoặc dùng ' UNION SELECT ... để dò cấu trúc bảng,
 * hoặc chạy sqlmap nhằm vào tham số "q".
 */
$sql = "SELECT id, name, price, description, category FROM products WHERE name LIKE '%$q%'";

if ($category !== '') {
    $sql .= " AND category = '" . $conn->real_escape_string($category) . "'";
}

if ($price_range !== '' && preg_match('/^(\d+)-(\d+)$/', $price_range, $m)) {
    $sql .= " AND price BETWEEN " . (int)$m[1] . " AND " . (int)$m[2];
}

$sql .= " ORDER BY $order_by";

$result = $conn->query($sql);

require __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <!--
    [VULN-A05 Injection - Reflected XSS]
    Giá trị $q được in lại nguyên văn, không escape. Xem WSTG-INPV-01 (Reflected XSS).
    Thử: ?q=<script>alert(1)</script>
  -->
  <p class="muted">
    Kết quả tìm kiếm cho: <?php echo $q; ?>
    <?php if ($category !== ''): ?> · Danh mục: <?php echo htmlspecialchars($category); ?><?php endif; ?>
    <?php if ($price_range !== ''): ?> · Khoảng giá: <?php echo htmlspecialchars($price_range); ?><?php endif; ?>
  </p>
</div>

<?php
/*
 * [VULN-A05 Injection - Error-based Information Disclosure]
 * In nguyên văn lỗi MySQL ra trang khi câu SQL bị lỗi cú pháp, giúp kẻ tấn công
 * dò cấu trúc câu query gốc để dựng payload UNION SELECT chính xác hơn.
 * Xem WSTG-INPV-05 (SQL Injection) / TC-SQLI-02.
 */
?>
<?php if ($result === false): ?>
  <div class="panel"><div class="flash error">Lỗi cơ sở dữ liệu: <?php echo $conn->error; ?><br><code><?php echo htmlspecialchars($sql); ?></code></div></div>
<?php elseif ($result->num_rows === 0): ?>
  <div class="panel">Không tìm thấy sản phẩm phù hợp.</div>
<?php else: ?>
  <div class="grid">
    <?php while ($p = $result->fetch_assoc()): ?>
      <div class="product-card">
        <div class="thumb">Ảnh sản phẩm</div>
        <strong><?php echo htmlspecialchars($p['name']); ?></strong>
        <div class="price"><?php echo number_format((float)$p['price'], 0, ',', '.'); ?>đ</div>
        <a class="btn" href="product.php?id=<?php echo (int)$p['id']; ?>">Xem chi tiết</a>
      </div>
    <?php endwhile; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
