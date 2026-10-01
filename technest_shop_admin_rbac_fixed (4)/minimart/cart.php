<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$me = current_user();
$page_title = 'Giỏ hàng';

require __DIR__ . '/includes/header.php';
?>

<h2>Giỏ hàng của bạn</h2>
<div class="panel" id="cart-page-content">
  <p class="muted">Đang tải giỏ hàng...</p>
</div>

<div class="panel" id="cart-checkout-panel" style="display:none;">
  <h3>Thông tin giao hàng</h3>
  <div id="cart-checkout-error" class="flash error" style="display:none;"></div>

  <label for="cart-address">Địa chỉ giao hàng <span style="color:var(--danger);">*</span></label>
  <input type="text" id="cart-address" placeholder="Số nhà, đường, phường/xã, quận/huyện...">

  <label for="cart-phone">Số điện thoại <span style="color:var(--danger);">*</span></label>
  <input type="text" id="cart-phone" placeholder="VD: 0901234567">

  <label for="cart-zone">Khu vực</label>
  <select id="cart-zone">
    <?php foreach (shipping_rate_table() as $zoneKey => $zoneInfo): ?>
      <option value="<?php echo htmlspecialchars($zoneKey); ?>">
        <?php echo htmlspecialchars($zoneInfo['label']); ?> (<?php echo number_format($zoneInfo['fee'], 0, ',', '.'); ?>đ, miễn phí từ 1.000.000đ)
      </option>
    <?php endforeach; ?>
  </select>

  <label for="cart-voucher">Mã giảm giá (nếu có)</label>
  <div style="display:flex;gap:8px;">
    <input type="text" id="cart-voucher" placeholder="VD: TECHNEST10" style="flex:1;">
    <button type="button" class="btn btn-secondary" id="cart-apply-voucher" style="margin-top:0;">Áp dụng</button>
  </div>
  <div id="cart-voucher-msg" class="muted" style="margin-top:6px;"></div>

  <div id="cart-summary" style="margin-top:16px;"></div>

  <?php if ($me && can_purchase()): ?>
    <button type="button" class="btn" id="cart-buy-all" style="margin-top:16px;">Mua tất cả</button>
  <?php elseif ($me): ?>
    <p class="muted" style="margin-top:16px;">Tài khoản quản trị / cộng tác viên không thể mua hàng. Hãy đăng nhập bằng tài khoản khách hàng để thanh toán.</p>
  <?php else: ?>
    <p class="muted" style="margin-top:16px;"><a href="login.php">Đăng nhập</a> để thanh toán giỏ hàng.</p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
