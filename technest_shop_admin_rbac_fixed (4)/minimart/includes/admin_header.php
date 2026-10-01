<?php
/**
 * Layout dùng chung cho khu vực /admin (thanh bên + nội dung).
 * Cần set trước khi include: $me (người dùng hiện tại), $page_title.
 * Menu tự ẩn các mục mà người dùng không có quyền (admin thấy tất cả,
 * cộng tác viên chỉ thấy mục được cấp). Việc CHẶN truy cập thật sự nằm ở
 * require_admin()/require_permission() đầu mỗi trang, không phải ở menu.
 */
$__cur = basename($_SERVER['SCRIPT_NAME'] ?? '');
$__nav = [
    ['index.php',    '📊', 'Bảng điều khiển', ui_staff()],
    ['products.php', '📦', 'Sản phẩm',        ui_can('products')],
    ['orders.php',   '🧾', 'Đơn hàng',        ui_can('orders')],
    ['reviews.php',  '💬', 'Đánh giá',        ui_can('reviews')],
    ['vouchers.php', '🏷️', 'Voucher',         ui_admin()],
    ['users.php',    '👥', 'Người dùng & phân quyền', ui_admin()],
    ['logs.php',     '🕘', 'Nhật ký hoạt động', ui_admin()],
];
$__realRole = auth_role_row()['role'];
$__roleLabel = ['admin' => 'Quản trị viên', 'collaborator' => 'Cộng tác viên'][$__realRole] ?? 'Khách hàng';
$__rolePill = ['admin' => 'is-admin', 'collaborator' => 'is-collab'][$__realRole] ?? 'is-customer';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($page_title ?? 'Quản trị'); ?> - TechNest</title>
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/admin.css">
</head>
<body class="admin-body">
<div class="admin-shell">
  <aside class="admin-side">
    <a class="admin-brand" href="index.php"><span class="logo">T</span><span>TechNest<small>Trung tâm quản trị</small></span></a>
    <nav class="admin-nav">
      <?php foreach ($__nav as [$file, $icon, $label, $show]): if (!$show) continue; ?>
        <a href="<?php echo $file; ?>" class="<?php echo $__cur === $file ? 'active' : ''; ?>"><span class="ico"><?php echo $icon; ?></span><?php echo htmlspecialchars($label); ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="admin-side-foot">
      <div class="admin-user">
        <div class="av"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($me['username'], 0, 1))); ?></div>
        <div><strong><?php echo htmlspecialchars($me['username']); ?></strong><br><span class="role-pill <?php echo $__rolePill; ?>"><?php echo $__roleLabel; ?></span></div>
      </div>
      <a href="../index.php" class="side-link">↩ Về cửa hàng</a>
      <a href="../logout.php" class="side-link">⎋ Đăng xuất</a>
    </div>
  </aside>
  <main class="admin-main">
    <header class="admin-top">
      <h1><?php echo htmlspecialchars($page_title ?? 'Quản trị'); ?></h1>
      <span class="muted"><?php echo date('d/m/Y'); ?></span>
    </header>
    <div class="admin-content">
