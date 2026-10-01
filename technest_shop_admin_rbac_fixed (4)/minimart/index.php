<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$me = current_user();
$page_title = 'Sản phẩm';

/*
 * Tìm kiếm / lọc "an toàn" ngay tại trang danh sách sản phẩm (khác với search.php,
 * vốn là mục tiêu kiểm thử SQL Injection / XSS cố ý của đề tài pentest và KHÔNG
 * được sửa ở đây). Toàn bộ điều kiện dưới đây dùng prepared statement + whitelist.
 */
$q = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';
$subcategory = $_GET['subcategory'] ?? '';
$price_range = $_GET['price_range'] ?? '';
$sort = $_GET['sort'] ?? '';

$tree = category_tree();
if (!isset($tree[$category])) {
    $category = '';
    $subcategory = '';
} elseif ($subcategory !== '' && !isset($tree[$category]['subs'][$subcategory])) {
    $subcategory = '';
}

$valid_sorts = [
    'newest'     => 'created_at DESC, id DESC',
    'price_asc'  => 'price ASC',
    'price_desc' => 'price DESC',
    'rating'     => 'rating DESC',
    'name_asc'   => 'name ASC',
];
$order_by = $valid_sorts[$sort] ?? 'id ASC';

$where = [];
$types = '';
$params = [];

if ($q !== '') {
    $where[] = '(name LIKE ? OR description LIKE ?)';
    $like = '%' . $q . '%';
    $types .= 'ss';
    $params[] = $like;
    $params[] = $like;
}
if ($category !== '') {
    $where[] = 'category = ?';
    $types .= 's';
    $params[] = $category;
}
if ($subcategory !== '') {
    $where[] = 'subcategory = ?';
    $types .= 's';
    $params[] = $subcategory;
}
if ($price_range !== '' && preg_match('/^(\d+)-(\d+)$/', $price_range, $m)) {
    $where[] = 'price BETWEEN ? AND ?';
    $types .= 'ii';
    $params[] = (int)$m[1];
    $params[] = (int)$m[2];
}

$hasFilters = ($q !== '' || $category !== '' || $subcategory !== '' || $price_range !== '' || $sort !== '');

$sql = "SELECT id, name, price, description, rating, stock FROM products";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= " ORDER BY $order_by";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

$productCount = $conn->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'];
$featured = $conn->query("SELECT id, name, price FROM products ORDER BY rating DESC, id ASC LIMIT 3");

require __DIR__ . '/includes/header.php';
?>

<?php if (!$hasFilters): ?>
<div class="hero">
  <div class="hero-text">
    <h1>Chào mừng đến với TechNest</h1>
    <p>Phụ kiện công nghệ & đồ dùng văn phòng chọn lọc — giá tốt, giao nhanh. Hiện có <?php echo (int)$productCount; ?> sản phẩm đang bán.</p>
  </div>
  <div class="hero-featured">
    <?php while ($f = $featured->fetch_assoc()): ?>
      <a class="mini-card" href="product.php?id=<?php echo (int)$f['id']; ?>">
        <?php echo product_visual($f['name'], 48); ?>
        <span class="mini-name"><?php echo htmlspecialchars(mb_strimwidth($f['name'], 0, 22, '...')); ?></span>
        <span class="mini-price"><?php echo number_format((float)$f['price'], 0, ',', '.'); ?>đ</span>
      </a>
    <?php endwhile; ?>
  </div>
</div>
<?php endif; ?>

<div class="shop-layout">
  <aside class="panel category-sidebar">
    <h3>Danh mục</h3>
    <ul class="cat-tree">
      <li>
        <a href="index.php" class="<?php echo $category === '' ? 'active' : ''; ?>">Tất cả sản phẩm</a>
      </li>
      <?php foreach ($tree as $catKey => $catInfo): ?>
        <li>
          <a href="index.php?category=<?php echo urlencode($catKey); ?>"
             class="<?php echo ($category === $catKey && $subcategory === '') ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($catInfo['label']); ?>
          </a>
          <?php if ($category === $catKey && !empty($catInfo['subs'])): ?>
            <ul class="cat-tree-sub">
              <?php foreach ($catInfo['subs'] as $subKey => $subLabel): ?>
                <li>
                  <a href="index.php?category=<?php echo urlencode($catKey); ?>&amp;subcategory=<?php echo urlencode($subKey); ?>"
                     class="<?php echo $subcategory === $subKey ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($subLabel); ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </aside>

  <div class="shop-main">
    <div class="panel search-bar">
      <form action="index.php" method="get" style="display:flex;gap:10px;flex-wrap:wrap;flex:1;">
        <?php if ($category !== ''): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>"><?php endif; ?>
        <?php if ($subcategory !== ''): ?><input type="hidden" name="subcategory" value="<?php echo htmlspecialchars($subcategory); ?>"><?php endif; ?>
        <input type="search" id="q" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Tìm sản phẩm... (ví dụ: chuột, bàn phím)">
        <select name="price_range" aria-label="Khoảng giá">
          <option value="">Khoảng giá</option>
          <option value="0-200000" <?php echo $price_range === '0-200000' ? 'selected' : ''; ?>>Dưới 200.000đ</option>
          <option value="200000-500000" <?php echo $price_range === '200000-500000' ? 'selected' : ''; ?>>200.000đ - 500.000đ</option>
          <option value="500000-1000000" <?php echo $price_range === '500000-1000000' ? 'selected' : ''; ?>>500.000đ - 1.000.000đ</option>
          <option value="1000000-2000000" <?php echo $price_range === '1000000-2000000' ? 'selected' : ''; ?>>1.000.000đ - 2.000.000đ</option>
          <option value="2000000-999999999" <?php echo $price_range === '2000000-999999999' ? 'selected' : ''; ?>>Trên 2.000.000đ</option>
        </select>
        <select name="sort" aria-label="Sắp xếp">
          <option value="">Sắp xếp</option>
          <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
          <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Giá tăng dần</option>
          <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Giá giảm dần</option>
          <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Đánh giá cao nhất</option>
          <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Tên A-Z</option>
        </select>
        <button type="submit">Tìm kiếm</button>
        <?php if ($hasFilters): ?><a class="btn btn-secondary" href="index.php">Xóa lọc</a><?php endif; ?>
      </form>
    </div>

    <h2>
      <?php echo $hasFilters ? 'Kết quả lọc / tìm kiếm' : 'Danh sách sản phẩm'; ?>
      <span class="muted" style="font-size:0.9rem;font-weight:400;">(<?php echo $products->num_rows; ?> sản phẩm)</span>
    </h2>

    <?php if ($products->num_rows === 0): ?>
      <div class="panel">Không tìm thấy sản phẩm phù hợp. <a href="index.php">Xem tất cả sản phẩm</a>.</div>
    <?php else: ?>
    <div class="grid">
      <?php while ($p = $products->fetch_assoc()):
          [$stockClass, $stockText] = stock_label((int)$p['stock']);
      ?>
        <div class="product-card">
          <div class="thumb"><?php echo product_visual($p['name'], 64); ?></div>
          <strong><?php echo htmlspecialchars($p['name']); ?></strong>
          <div class="rating"><span class="stars"><?php echo render_stars((float)$p['rating']); ?></span> <?php echo number_format((float)$p['rating'], 1); ?></div>
          <div class="price"><?php echo number_format((float)$p['price'], 0, ',', '.'); ?>đ</div>
          <div class="stock <?php echo $stockClass; ?>"><?php echo $stockText; ?></div>
          <p class="muted"><?php echo htmlspecialchars(mb_strimwidth($p['description'], 0, 70, '...')); ?></p>
          <div class="card-actions">
            <a class="btn<?php echo $p['stock'] <= 0 ? ' disabled' : ''; ?>" href="product.php?id=<?php echo (int)$p['id']; ?>">Xem chi tiết</a>
            <?php if (!$me || can_purchase()): ?>
            <button type="button" class="btn-cart" title="Thêm vào giỏ"
              data-add-to-cart
              data-id="<?php echo (int)$p['id']; ?>"
              data-name="<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>"
              data-price="<?php echo (float)$p['price']; ?>"
              <?php echo $p['stock'] <= 0 ? 'disabled' : ''; ?>>🛒</button>
            <?php endif; ?>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
