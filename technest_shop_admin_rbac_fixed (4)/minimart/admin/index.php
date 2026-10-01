<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Admin + cộng tác viên đều vào được bảng điều khiển, nhưng mỗi khối số liệu
// chỉ hiện nếu người xem có quyền tương ứng.
require_staff('../login.php');
$me = current_user();
$page_title = 'Bảng điều khiển';

$canOrders   = ui_can('orders');
$canProducts = ui_can('products');
$canReviews  = ui_can('reviews');
$fmt = fn($n) => number_format((float)$n, 0, ',', '.') . 'đ';
$excluded = "('Đã hủy','Đã hoàn hàng')";

if ($canOrders) {
    $totalOrders    = (int)$conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
    $revenue        = (float)$conn->query("SELECT COALESCE(SUM(total),0) s FROM orders WHERE status NOT IN $excluded")->fetch_assoc()['s'];
    $pendingOrders  = (int)$conn->query("SELECT COUNT(*) c FROM orders WHERE status IN ('Chờ xác nhận','Đang xử lý')")->fetch_assoc()['c'];
    $returnRequests = (int)$conn->query("SELECT COUNT(*) c FROM orders WHERE status = 'Yêu cầu hoàn hàng'")->fetch_assoc()['c'];

    // Doanh thu 7 ngày gần nhất
    $rows = $conn->query("SELECT DATE(created_at) d, SUM(total) s FROM orders
                          WHERE status NOT IN $excluded AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                          GROUP BY DATE(created_at)")->fetch_all(MYSQLI_ASSOC);
    $byDay = [];
    foreach ($rows as $r) $byDay[$r['d']] = (float)$r['s'];
    $days = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i day"));
        $days[] = ['label' => date('d/m', strtotime($d)), 'val' => $byDay[$d] ?? 0];
    }
    $maxDay = max(1, max(array_column($days, 'val')));

    $topProducts = $conn->query("SELECT p.name, SUM(oi.quantity) qty, SUM(oi.quantity*oi.price) amount
        FROM order_items oi JOIN orders o ON oi.order_id=o.id JOIN products p ON p.id=oi.product_id
        WHERE o.status NOT IN $excluded GROUP BY p.id, p.name ORDER BY qty DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

    $recentOrders = $conn->query("SELECT o.id, o.total, o.status, o.created_at, u.username
        FROM orders o JOIN users u ON o.user_id=u.id ORDER BY o.id DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
}
if ($canProducts) {
    $totalProducts = (int)$conn->query("SELECT COUNT(*) c FROM products")->fetch_assoc()['c'];
    $lowStock = $conn->query("SELECT id, name, stock FROM products WHERE stock <= 5 ORDER BY stock ASC, id ASC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
}
if ($canReviews) {
    $unanswered = (int)$conn->query("SELECT COUNT(*) c FROM reviews r WHERE r.is_hidden = 0
        AND NOT EXISTS (SELECT 1 FROM review_replies rr JOIN users u ON u.id = rr.user_id
                        WHERE rr.review_id = r.id AND u.role IN ('admin','collaborator'))")->fetch_assoc()['c'];
    $totalReviews = (int)$conn->query("SELECT COUNT(*) c FROM reviews")->fetch_assoc()['c'];
}
if (ui_admin()) {
    $totalUsers = (int)$conn->query("SELECT COUNT(*) c FROM users WHERE role='customer'")->fetch_assoc()['c'];
    $totalCollabs = (int)$conn->query("SELECT COUNT(*) c FROM users WHERE role='collaborator'")->fetch_assoc()['c'];
}

require __DIR__ . '/../includes/admin_header.php';
?>
  <p class="muted" style="margin-top:0;">Xin chào <strong><?php echo htmlspecialchars($me['username']); ?></strong> —
    <?php
      if (is_admin()) echo 'bạn có toàn quyền quản trị.';
      elseif (is_collaborator()) echo 'bạn được cấp quyền: <strong>' . htmlspecialchars(implode(', ', array_filter(explode(',', (string)$me['permissions']))) ?: 'chưa có quyền nào') . '</strong>.';
      else echo 'chào mừng bạn đến khu vực quản trị.';
    ?></p>

  <div class="stat-grid">
    <?php if ($canOrders): ?>
      <div class="stat-card"><div class="lbl">Doanh thu (trừ đơn huỷ/hoàn)</div><div class="val"><?php echo $fmt($revenue); ?></div><div class="sub"><?php echo $totalOrders; ?> đơn hàng</div></div>
      <div class="stat-card warn"><a href="orders.php?status=<?php echo urlencode('Chờ xác nhận'); ?>"><div class="lbl">Đơn chờ xử lý</div><div class="val"><?php echo $pendingOrders; ?></div><div class="sub">Bấm để xử lý</div></a></div>
      <div class="stat-card bad"><a href="orders.php?status=<?php echo urlencode('Yêu cầu hoàn hàng'); ?>"><div class="lbl">Yêu cầu hoàn hàng</div><div class="val"><?php echo $returnRequests; ?></div><div class="sub">Cần duyệt</div></a></div>
    <?php endif; ?>
    <?php if ($canProducts): ?>
      <div class="stat-card teal"><div class="lbl">Sản phẩm</div><div class="val"><?php echo $totalProducts; ?></div><div class="sub"><?php echo count($lowStock); ?> sản phẩm sắp hết hàng</div></div>
    <?php endif; ?>
    <?php if ($canReviews): ?>
      <div class="stat-card warn"><a href="reviews.php?filter=unanswered"><div class="lbl">Đánh giá chưa được trả lời</div><div class="val"><?php echo $unanswered; ?></div><div class="sub">Trên tổng <?php echo $totalReviews; ?> đánh giá</div></a></div>
    <?php endif; ?>
    <?php if (ui_admin()): ?>
      <div class="stat-card teal"><div class="lbl">Khách hàng</div><div class="val"><?php echo $totalUsers; ?></div><div class="sub"><?php echo $totalCollabs; ?> cộng tác viên</div></div>
    <?php endif; ?>
  </div>

  <?php if ($canOrders): ?>
  <div class="two-col">
    <div class="card">
      <h3>Doanh thu 7 ngày gần nhất</h3>
      <div class="bars">
        <?php foreach ($days as $d): $h = max(2, (int)round($d['val'] / $maxDay * 130)); ?>
          <div class="bar-col">
            <div class="bar-val"><?php echo $d['val'] > 0 ? number_format($d['val'] / 1000, 0, ',', '.') . 'k' : ''; ?></div>
            <div class="bar" style="height:<?php echo $h; ?>px;"></div>
            <div class="bar-lbl"><?php echo htmlspecialchars($d['label']); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="card">
      <h3>Sản phẩm bán chạy</h3>
      <?php if (!$topProducts): ?><div class="empty">Chưa có dữ liệu.</div><?php endif; ?>
      <?php foreach ($topProducts as $i => $tp): ?>
        <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid #f0eef8;">
          <span><?php echo ($i + 1) . '. ' . htmlspecialchars($tp['name']); ?></span>
          <span class="chip"><?php echo (int)$tp['qty']; ?> đã bán</span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Đơn hàng mới nhất</h3><a href="orders.php">Xem tất cả →</a></div>
    <div class="table-wrap"><table>
      <tr><th>Mã</th><th>Khách</th><th>Tổng</th><th>Trạng thái</th><th>Thời gian</th></tr>
      <?php foreach ($recentOrders as $o): ?>
        <tr><td>#<?php echo (int)$o['id']; ?></td><td><?php echo htmlspecialchars($o['username']); ?></td>
          <td><?php echo $fmt($o['total']); ?></td><td><span class="chip"><?php echo htmlspecialchars($o['status']); ?></span></td>
          <td class="muted"><?php echo htmlspecialchars($o['created_at']); ?></td></tr>
      <?php endforeach; ?>
    </table></div>
  </div>
  <?php endif; ?>

  <?php if ($canProducts): ?>
  <div class="card">
    <div class="card-head"><h3>⚠️ Sắp hết hàng (tồn ≤ 5)</h3><a href="products.php?stock=low">Xem tất cả →</a></div>
    <?php if (!$lowStock): ?><div class="empty">Tất cả sản phẩm đều còn đủ hàng.</div><?php endif; ?>
    <?php foreach ($lowStock as $p): ?>
      <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0eef8;">
        <span><?php echo htmlspecialchars($p['name']); ?></span>
        <span class="chip <?php echo (int)$p['stock'] === 0 ? 'bad' : 'warn'; ?>"><?php echo (int)$p['stock'] === 0 ? 'Hết hàng' : 'Còn ' . (int)$p['stock']; ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!$canOrders && !$canProducts && !$canReviews && !ui_admin()): ?>
    <div class="card empty">Tài khoản của bạn chưa được cấp quyền nào. Hãy liên hệ quản trị viên.</div>
  <?php endif; ?>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
