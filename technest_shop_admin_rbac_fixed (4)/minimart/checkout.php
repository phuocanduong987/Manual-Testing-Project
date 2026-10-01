<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

require_login();
$me = current_user();

// Admin không được mua hàng trên chính cửa hàng của mình (chặn ở server, không chỉ ẩn nút).
if (!can_purchase()) {
    deny_access('Tài khoản quản trị / cộng tác viên không thể mua hàng. Hãy dùng một tài khoản khách hàng để đặt hàng.');
}

$productId = (int)($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
$stmt = $conn->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
$stmt->bind_param('i', $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: index.php');
    exit;
}

$error = '';
$address = '';
$phone = '';
$voucherCode = '';

if ((int)$product['stock'] <= 0) {
    $error = 'Rất tiếc, sản phẩm này hiện đã hết hàng.';
}

// Bắt buộc phải nhập địa chỉ và số điện thoại trước khi tạo đơn hàng.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim((string)($_POST['address'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $voucherCode = trim((string)($_POST['voucher_code'] ?? ''));

    if ($address === '' || $phone === '') {
        $error = 'Vui lòng nhập đầy đủ địa chỉ giao hàng và số điện thoại.';
    } elseif (!preg_match('/^[0-9+ ]{8,15}$/', $phone)) {
        $error = 'Số điện thoại không hợp lệ.';
    } else {
        /*
         * [VULN-API Toàn vẹn dữ liệu - Trusting Client Input / Price Tampering]
         * Giá được lấy từ trường ẩn "price" trong form (client gửi lên), KHÔNG lấy từ DB.
         * Dùng Burp sửa price=0 (hoặc số bất kỳ) trước khi forward -> đơn hàng được tạo
         * với giá tuỳ ý. Cùng loại lỗi với checkout_cart.php. Xem API3:2023 / TC-API-03.
         */
        $clientPrice = (float)($_POST['price'] ?? $product['price']);
        $subtotal = $clientPrice;
        $discount = 0.0;
        $appliedVoucherCode = null;
        $voucherRow = null;

        // Nếu khách có nhập mã giảm giá, kiểm tra trước khi tạo đơn (dùng chung
        // hàm validate_voucher() với luồng giỏ hàng ở checkout_cart.php).
        if ($voucherCode !== '') {
            $v = validate_voucher($conn, $voucherCode, $subtotal);
            if (!$v['ok']) {
                $error = $v['message'];
            } else {
                $discount = $v['discount'];
                $appliedVoucherCode = $v['voucher']['code'];
                $voucherRow = $v['voucher'];
            }
        }

        if ($error === '') {
            $total = max(0, $subtotal - $discount);

            // Kiểm tra lại tồn kho ngay trước khi tạo đơn (không tin số liệu đã tải
            // từ trước đó trên trang, vì có thể đã có người khác mua hết trong lúc chờ).
            $conn->begin_transaction();
            try {
                $ins = $conn->prepare(
                    "INSERT INTO orders (user_id, total, status, address, phone, voucher_code, discount_amount)
                     VALUES (?, ?, 'Chờ xác nhận', ?, ?, ?, ?)"
                );
                $ins->bind_param('idsssd', $me['id'], $total, $address, $phone, $appliedVoucherCode, $discount);
                $ins->execute();
                $orderId = $ins->insert_id;

                $item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, 1, ?)");
                $item->bind_param('iid', $orderId, $product['id'], $clientPrice);
                $item->execute();

                $stockStmt = $conn->prepare("UPDATE products SET stock = stock - 1 WHERE id = ? AND stock >= 1");
                $stockStmt->bind_param('i', $product['id']);
                $stockStmt->execute();
                if ($stockStmt->affected_rows < 1) {
                    throw new RuntimeException('out_of_stock');
                }

                if ($voucherRow) {
                    $vu = $conn->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?");
                    $vu->bind_param('i', $voucherRow['id']);
                    $vu->execute();
                }

                $conn->commit();
                header('Location: orders.php?id=' . $orderId);
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                $error = 'Rất tiếc, sản phẩm này vừa hết hàng. Vui lòng quay lại trang sản phẩm để kiểm tra lại.';
            }
        }
    }
}

$page_title = 'Thanh toán';
require __DIR__ . '/includes/header.php';
?>

<div class="panel" style="max-width:480px;">
  <h2>Thông tin giao hàng</h2>
  <p class="muted">Sản phẩm: <strong><?php echo htmlspecialchars($product['name']); ?></strong> — <?php echo number_format((float)$product['price'], 0, ',', '.'); ?>đ</p>

  <?php if ($error): ?>
    <div class="flash error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="post" action="checkout.php">
    <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
    <input type="hidden" name="price" value="<?php echo htmlspecialchars((string)$product['price']); ?>">

    <label for="address">Địa chỉ giao hàng <span style="color:var(--danger);">*</span></label>
    <input type="text" id="address" name="address" required placeholder="Số nhà, đường, phường/xã, quận/huyện..." value="<?php echo htmlspecialchars($address); ?>">

    <label for="phone">Số điện thoại <span style="color:var(--danger);">*</span></label>
    <input type="text" id="phone" name="phone" required placeholder="VD: 0901234567" value="<?php echo htmlspecialchars($phone); ?>">

    <label for="voucher_code">Mã giảm giá (nếu có)</label>
    <input type="text" id="voucher_code" name="voucher_code" placeholder="VD: TECHNEST10" value="<?php echo htmlspecialchars($voucherCode); ?>">

    <button type="submit" class="btn" style="margin-top:16px;">Xác nhận đặt hàng</button>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
