<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Admin luôn qua; cộng tác viên cần được cấp quyền 'products'.
require_permission('products', '../login.php');
$me = current_user();
$page_title = 'Quản lý sản phẩm';
$flash = '';
$error = '';

$tree = category_tree();

function product_form_errors(array $d): string {
    if (trim($d['name']) === '') return 'Vui lòng nhập tên sản phẩm.';
    if (!is_numeric($d['price']) || (float)$d['price'] < 0) return 'Giá sản phẩm không hợp lệ.';
    if (!ctype_digit((string)$d['stock']) ) return 'Tồn kho phải là số nguyên không âm.';
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            audit_log('product.delete', 'Xoá sản phẩm #' . $id);
            $flash = 'Đã xoá sản phẩm.';
        } else {
            // Xoá thất bại thường do sản phẩm đã có trong order_items/reviews
            // (ràng buộc khoá ngoại). Báo rõ cho admin thay vì báo "đã xoá" sai sự thật.
            $error = 'Không thể xoá sản phẩm này vì đã có đơn hàng hoặc đánh giá liên quan. Bạn có thể đặt tồn kho về 0 để ẩn khỏi bán thay vì xoá.';
        }
    } elseif ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = $_POST['price'] ?? '0';
        $stock = $_POST['stock'] ?? '0';
        $category = $_POST['category'] ?? '';
        $subcategory = $_POST['subcategory'] ?? '';

        $err = product_form_errors(['name' => $name, 'price' => $price, 'stock' => $stock]);
        if (!isset($tree[$category])) { $category = null; $subcategory = null; }
        elseif (!isset($tree[$category]['subs'][$subcategory])) { $subcategory = null; }

        if ($err !== '') {
            $error = $err;
        } elseif ($id > 0) {
            $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, category=?, subcategory=? WHERE id=?");
            $stmt->bind_param('ssdissi', $name, $description, $price, $stock, $category, $subcategory, $id);
            $stmt->execute();
            audit_log('product.update', 'Sửa sản phẩm #' . $id . ' (' . $name . ')');
            $flash = 'Đã cập nhật sản phẩm.';
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, category, subcategory) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('ssdiss', $name, $description, $price, $stock, $category, $subcategory);
            $stmt->execute();
            audit_log('product.create', 'Thêm sản phẩm ' . $name);
            $flash = 'Đã thêm sản phẩm mới.';
        }
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editProduct = null;
if ($editId > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editProduct = $stmt->get_result()->fetch_assoc();
}

$q = trim($_GET['q'] ?? '');
$stockFilter = $_GET['stock'] ?? '';
$psql = "SELECT * FROM products WHERE 1=1";
$ptypes = ''; $pparams = [];
if ($q !== '') { $psql .= " AND name LIKE ?"; $ptypes .= 's'; $pparams[] = '%' . $q . '%'; }
if ($stockFilter === 'low') { $psql .= " AND stock <= 5"; }
$psql .= " ORDER BY id DESC";
$pstmt = $conn->prepare($psql);
if ($ptypes !== '') $pstmt->bind_param($ptypes, ...$pparams);
$pstmt->execute();
$products = $pstmt->get_result();

require __DIR__ . '/../includes/admin_header.php';
?>
  <?php if ($flash): ?><div class="flash ok"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="flash error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <div class="card">
    <h3><?php echo $editProduct ? '✏️ Sửa sản phẩm #' . (int)$editProduct['id'] : '➕ Thêm sản phẩm mới'; ?></h3>
    <form method="post">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?php echo (int)($editProduct['id'] ?? 0); ?>">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:0 16px;">
        <div><label for="name">Tên sản phẩm</label>
          <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($editProduct['name'] ?? ''); ?>"></div>
        <div><label for="price">Giá (đ)</label>
          <input type="number" id="price" name="price" min="0" step="1000" required value="<?php echo htmlspecialchars((string)($editProduct['price'] ?? 0)); ?>"></div>
        <div><label for="stock">Tồn kho</label>
          <input type="number" id="stock" name="stock" min="0" required value="<?php echo htmlspecialchars((string)($editProduct['stock'] ?? 0)); ?>"></div>
        <div><label for="category">Danh mục</label>
          <select id="category" name="category">
            <option value="">-- Chọn danh mục --</option>
            <?php foreach ($tree as $catKey => $catInfo): ?>
              <option value="<?php echo htmlspecialchars($catKey); ?>" <?php echo (($editProduct['category'] ?? '') === $catKey) ? 'selected' : ''; ?>><?php echo htmlspecialchars($catInfo['label']); ?></option>
            <?php endforeach; ?>
          </select></div>
        <div><label for="subcategory">Danh mục con</label>
          <select id="subcategory" name="subcategory">
            <option value="">-- Không có --</option>
            <?php foreach ($tree as $catKey => $catInfo): foreach ($catInfo['subs'] as $subKey => $subLabel): ?>
              <option value="<?php echo htmlspecialchars($subKey); ?>" <?php echo (($editProduct['subcategory'] ?? '') === $subKey) ? 'selected' : ''; ?>><?php echo htmlspecialchars($catInfo['label'] . ' / ' . $subLabel); ?></option>
            <?php endforeach; endforeach; ?>
          </select></div>
      </div>
      <label for="description">Mô tả</label>
      <textarea id="description" name="description"><?php echo htmlspecialchars($editProduct['description'] ?? ''); ?></textarea>
      <button type="submit"><?php echo $editProduct ? 'Lưu thay đổi' : 'Thêm sản phẩm'; ?></button>
      <?php if ($editProduct): ?><a class="btn btn-secondary" href="products.php" style="margin-left:8px;">Huỷ</a><?php endif; ?>
    </form>
  </div>

  <div class="card">
    <div class="card-head">
      <h3>Tất cả sản phẩm</h3>
      <form method="get" class="toolbar" style="margin:0;">
        <input type="search" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Tìm tên sản phẩm">
        <select name="stock" onchange="this.form.submit()">
          <option value="">Mọi tồn kho</option>
          <option value="low" <?php echo $stockFilter === 'low' ? 'selected' : ''; ?>>Sắp hết (≤ 5)</option>
        </select>
        <button type="submit" class="btn-sm">Lọc</button>
      </form>
    </div>
    <div class="table-wrap"><table>
      <tr><th>ID</th><th>Tên</th><th>Giá</th><th>Tồn kho</th><th>Danh mục</th><th></th></tr>
      <?php while ($p = $products->fetch_assoc()): $st = (int)$p['stock']; ?>
        <tr>
          <td>#<?php echo (int)$p['id']; ?></td>
          <td><?php echo htmlspecialchars($p['name']); ?></td>
          <td><?php echo number_format((float)$p['price'], 0, ',', '.'); ?>đ</td>
          <td><span class="chip <?php echo $st === 0 ? 'bad' : ($st <= 5 ? 'warn' : 'ok'); ?>"><?php echo $st === 0 ? 'Hết hàng' : $st; ?></span></td>
          <td class="muted"><?php echo htmlspecialchars(($tree[$p['category']]['label'] ?? $p['category']) ?: '—'); ?></td>
          <td style="white-space:nowrap;">
            <a class="btn btn-sm btn-secondary" href="products.php?edit=<?php echo (int)$p['id']; ?>">Sửa</a>
            <form method="post" class="inline-form" onsubmit="return confirm('Xoá sản phẩm này?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
              <button type="submit" class="btn-sm btn-danger">Xoá</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
      <?php if ($products->num_rows === 0): ?><tr><td colspan="6" class="empty">Không có sản phẩm phù hợp.</td></tr><?php endif; ?>
    </table></div>
  </div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
