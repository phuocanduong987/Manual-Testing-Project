<?php
/**
 * Include này dùng cho các trang ở gốc site (index.php, login.php, ...).
 * Biến $me (mảng người dùng hiện tại hoặc null) cần được set trước khi include.
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title><?php echo isset($page_title) ? $page_title . ' - TechNest' : 'TechNest'; ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="topbar">
  <a class="brand" href="index.php">TechNest</a>
  <nav>
    <?php if ($me): ?>
      <?php if (!is_staff()): ?><a href="my_orders.php">Đơn hàng của tôi</a><?php endif; ?>
      <a href="profile.php">Hồ sơ</a>
      <?php if (is_staff()): ?><a href="admin/index.php"><?php echo is_admin() ? 'Quản trị' : 'Khu cộng tác viên'; ?></a><?php endif; ?>
      <a href="logout.php">Đăng xuất (<?php echo htmlspecialchars($me['username']); ?>)</a>
    <?php else: ?>
      <a href="login.php">Đăng nhập</a>
      <a href="register.php">Đăng ký</a>
    <?php endif; ?>
    <?php if (!$me || !is_staff()): ?><a class="cart-link" href="cart.php" title="Giỏ hàng">🛒<span class="cart-badge" id="cart-badge">0</span></a><?php endif; ?>
  </nav>
</div>
<div class="wrap">
