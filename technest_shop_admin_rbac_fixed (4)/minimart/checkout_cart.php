<?php
/**
 * Xử lý "Mua tất cả" từ giỏ hàng (giỏ hàng lưu ở localStorage phía trình duyệt,
 * xem assets/cart.js). Nhận JSON qua POST, tự tính lại giá/ship/voucher ở server
 * (không tin số liệu do client gửi lên) rồi tạo 1 đơn hàng gồm nhiều sản phẩm.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Bạn cần đăng nhập trước khi thanh toán.']);
    exit;
}
$me = current_user();

// Admin không được mua hàng trên chính cửa hàng của mình.
if (!can_purchase()) {
    http_response_code(403);
    echo json_encode(['error' => 'Tài khoản quản trị / cộng tác viên không thể mua hàng. Hãy dùng tài khoản khách hàng.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body) || empty($body['items']) || !is_array($body['items'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Giỏ hàng trống hoặc dữ liệu không hợp lệ.']);
    exit;
}

$zone = (string)($body['zone'] ?? '');
$address = trim((string)($body['address'] ?? ''));
$phone = trim((string)($body['phone'] ?? ''));
$voucherCode = trim((string)($body['voucher_code'] ?? ''));

if ($address === '' || $phone === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Vui lòng nhập đầy đủ địa chỉ giao hàng và số điện thoại.']);
    exit;
}

if (!preg_match('/^[0-9+ ]{8,15}$/', $phone)) {
    http_response_code(400);
    echo json_encode(['error' => 'Số điện thoại không hợp lệ.']);
    exit;
}

$rateTable = shipping_rate_table();
if (!isset($rateTable[$zone])) {
    http_response_code(400);
    echo json_encode(['error' => 'Khu vực giao hàng không hợp lệ.']);
    exit;
}

/*
 * [VULN-API Toàn vẹn dữ liệu - Trusting Client Input / Price Tampering]
 * Server không còn tính lại giá từ DB mà TIN THẲNG giá ($it['price']) do client
 * gửi lên trong body JSON. Có thể sửa total_price/price về 0 (hoặc bất kỳ số nào)
 * bằng Burp trước khi forward request để tạo đơn hàng với giá tuỳ ý.
 * Xem API3:2023 Broken Object Property Level Authorization / TC-API-03.
 */
$subtotal = 0.0;
$lineItems = [];
foreach ($body['items'] as $it) {
    $pid = (int)($it['id'] ?? 0);
    $qty = max(1, (int)($it['qty'] ?? 1));
    $price = (float)($it['price'] ?? 0);
    $stmt = $conn->prepare("SELECT id, name, stock FROM products WHERE id = ?");
    $stmt->bind_param('i', $pid);
    $stmt->execute();
    $prod = $stmt->get_result()->fetch_assoc();
    if (!$prod) {
        continue;
    }
    if ($prod['stock'] < $qty) {
        http_response_code(409);
        echo json_encode(['error' => 'Sản phẩm "' . $prod['name'] . '" chỉ còn ' . $prod['stock'] . ' trong kho.']);
        exit;
    }
    $subtotal += $price * $qty;
    $lineItems[] = ['id' => $prod['id'], 'qty' => $qty, 'price' => $price];
}

if (empty($lineItems)) {
    http_response_code(400);
    echo json_encode(['error' => 'Không có sản phẩm hợp lệ trong giỏ hàng.']);
    exit;
}

$shippingFee = shipping_fee_for($zone, $subtotal);

$discount = 0.0;
$appliedVoucherCode = null;
$voucherRow = null;
if ($voucherCode !== '') {
    $v = validate_voucher($conn, $voucherCode, $subtotal);
    if (!$v['ok']) {
        http_response_code(400);
        echo json_encode(['error' => $v['message']]);
        exit;
    }
    $discount = $v['discount'];
    $appliedVoucherCode = $v['voucher']['code'];
    $voucherRow = $v['voucher'];
    // Voucher loại "amount" trên đơn hàng này áp dụng vào phí ship trước, phần dư trừ vào tổng tiền hàng.
    if ($v['voucher']['discount_type'] === 'amount') {
        $shipDiscount = min($discount, $shippingFee);
        $shippingFee -= $shipDiscount;
    }
}

$total = max(0, $subtotal + $shippingFee - $discount);

$conn->begin_transaction();
try {
    $ins = $conn->prepare(
        "INSERT INTO orders (user_id, total, status, address, phone, shipping_fee, voucher_code, discount_amount)
         VALUES (?, ?, 'Chờ xác nhận', ?, ?, ?, ?, ?)"
    );
    $ins->bind_param('idssdsd', $me['id'], $total, $address, $phone, $shippingFee, $appliedVoucherCode, $discount);
    $ins->execute();
    $orderId = $ins->insert_id;

    $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stockStmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
    foreach ($lineItems as $li) {
        $itemStmt->bind_param('iiid', $orderId, $li['id'], $li['qty'], $li['price']);
        $itemStmt->execute();
        $stockStmt->bind_param('iii', $li['qty'], $li['id'], $li['qty']);
        $stockStmt->execute();
        // Nếu có người khác vừa mua hết hàng giữa lúc kiểm tra và lúc trừ kho
        // (race condition), affected_rows sẽ là 0 -> huỷ toàn bộ đơn thay vì
        // tạo đơn với số lượng vượt quá tồn kho thực tế.
        if ($stockStmt->affected_rows < 1) {
            throw new RuntimeException('Sản phẩm "' . $li['id'] . '" vừa hết hàng trong lúc xử lý đơn.');
        }
    }

    if ($voucherRow) {
        $vu = $conn->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?");
        $vu->bind_param('i', $voucherRow['id']);
        $vu->execute();
    }

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Không thể tạo đơn hàng, vui lòng thử lại.']);
    exit;
}

echo json_encode([
    'order_id' => $orderId,
    'subtotal' => $subtotal,
    'shipping_fee' => $shippingFee,
    'discount' => $discount,
    'total' => $total,
]);
