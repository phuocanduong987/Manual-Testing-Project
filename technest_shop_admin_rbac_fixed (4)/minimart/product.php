<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$me = current_user();
$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    http_response_code(404);
    require __DIR__ . '/includes/header.php';
    echo '<div class="panel">Không tìm thấy sản phẩm. <a href="index.php">Quay lại danh sách sản phẩm</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$canReview = $me ? user_has_purchased($conn, (int)$me['id'], $id) : false;
// Trả lời nhận xét: khách phải đã mua sản phẩm (giống điều kiện viết nhận xét
// gốc) HOẶC là nhân viên được cấp quyền 'reviews' (admin luôn có quyền này;
// cộng tác viên chỉ khi được admin cấp). Nhân viên không cần mua hàng.
$isSeller = $me ? has_permission('reviews') : false;
$canReply = $me ? ($canReview || $isSeller) : false;
$canBuy = can_purchase(); // admin không được mua hàng trên chính cửa hàng của mình

$flash = '';
$error = '';
if ($me && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['review_content'])) {
        if (!$canReview) {
            $error = 'Bạn cần mua và nhận sản phẩm này thành công mới có thể đánh giá.';
        } else {
            $content = trim($_POST['review_content']);
            if ($content !== '') {
                // Ghi vào DB bằng prepared statement (an toàn với SQL Injection ở bước lưu trữ).
                $rstmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, content) VALUES (?, ?, ?)");
                $rstmt->bind_param('iis', $id, $me['id'], $content);
                $rstmt->execute();
                $flash = 'Đã gửi nhận xét.';
            }
        }
    } elseif (isset($_POST['toggle_like_review_id'])) {
        // Thích không yêu cầu đã mua hàng — bất kỳ ai đăng nhập cũng thích được.
        $reviewId = (int)$_POST['toggle_like_review_id'];
        toggle_review_like($conn, (int)$me['id'], $reviewId);
    } elseif (isset($_POST['reply_review_id']) && isset($_POST['reply_content'])) {
        if (!$canReply) {
            $error = 'Bạn cần mua và nhận sản phẩm này thành công (hoặc là nhân viên được cấp quyền đánh giá) mới có thể trả lời nhận xét.';
        } else {
            $replyContent = trim($_POST['reply_content']);
            if ($replyContent !== '') {
                add_review_reply($conn, (int)$_POST['reply_review_id'], (int)$me['id'], $replyContent);
                $flash = 'Đã gửi trả lời.';
            }
        }
    }
}

// Nhận xét bị ẩn (kiểm duyệt ở admin/reviews.php) chỉ hiện với nhân viên có quyền 'reviews'.
$hiddenClause = $isSeller ? '' : ' AND r.is_hidden = 0';
$reviews = $conn->prepare("SELECT r.id, r.content, r.is_hidden, r.created_at, u.id AS user_id, u.username, u.avatar FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ?$hiddenClause ORDER BY r.created_at DESC");
$reviews->bind_param('i', $id);
$reviews->execute();
$reviewRows = $reviews->get_result();

$page_title = $product['name'];
require __DIR__ . '/includes/header.php';
?>

<a href="index.php" class="back-link">&larr; Quay lại danh sách sản phẩm</a>

<div class="panel product-detail">
  <div class="product-detail-media"><?php echo product_visual($product['name'], 120); ?></div>
  <div class="product-detail-info">
    <h2><?php echo htmlspecialchars($product['name']); ?></h2>
    <div class="rating"><span class="stars"><?php echo render_stars((float)$product['rating']); ?></span> <?php echo number_format((float)$product['rating'], 1); ?></div>
    <p class="price" style="font-size:1.2rem;"><?php echo number_format((float)$product['price'], 0, ',', '.'); ?>đ</p>
    <?php [$stockClass, $stockText] = stock_label((int)$product['stock']); ?>
    <div class="stock <?php echo $stockClass; ?>"><?php echo $stockText; ?></div>
    <p class="muted"><?php echo htmlspecialchars($product['description']); ?></p>
    <div class="card-actions" style="max-width:340px;">
      <?php if ($canBuy): ?>
        <a class="btn<?php echo $product['stock'] <= 0 ? ' disabled' : ''; ?>" href="checkout.php?product_id=<?php echo (int)$product['id']; ?>">Mua ngay</a>
      <?php elseif ($me): ?>
        <p class="muted">Tài khoản quản trị / cộng tác viên không thể mua hàng.<?php if (has_permission('products')): ?> <a href="admin/products.php?edit=<?php echo (int)$product['id']; ?>">Sửa sản phẩm này</a><?php endif; ?></p>
      <?php else: ?>
        <p class="muted"><a href="login.php">Đăng nhập</a> để mua sản phẩm này.</p>
      <?php endif; ?>
      <?php if (!$me || $canBuy): ?>
      <button type="button" class="btn-cart" title="Thêm vào giỏ"
        data-add-to-cart
        data-id="<?php echo (int)$product['id']; ?>"
        data-name="<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>"
        data-price="<?php echo (float)$product['price']; ?>"
        <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>🛒 Thêm vào giỏ</button>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="panel">
  <h3>Nhận xét khách hàng (<?php echo $reviewRows->num_rows; ?>)</h3>
  <?php if ($flash): ?><div class="flash ok"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="flash error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <?php if ($reviewRows->num_rows === 0): ?>
    <p class="muted">Chưa có nhận xét nào.</p>
  <?php endif; ?>

  <?php while ($r = $reviewRows->fetch_assoc()):
      $reviewId = (int)$r['id'];
      $likeCount = review_like_count($conn, $reviewId);
      $liked = $me ? user_has_liked_review($conn, (int)$me['id'], $reviewId) : false;
      $replies = get_review_replies($conn, $reviewId);
  ?>
    <div class="review" id="review-<?php echo $reviewId; ?>"<?php echo $r['is_hidden'] ? ' style="opacity:.55;"' : ''; ?>>
      <!--
        [VULN-A05 Injection / XSS] Nội dung nhận xét được in ra trực tiếp, KHÔNG qua
        htmlspecialchars(). Nếu $r['content'] chứa mã HTML/JS (ví dụ <script>...</script>),
        trình duyệt sẽ thực thi -> Stored XSS. Xem WSTG-INPV-02 (Stored XSS).
      -->
      <?php if ($r['is_hidden']): ?><div class="meta">🙈 Nhận xét đang bị ẩn — chỉ nhân viên nhìn thấy</div><?php endif; ?>
      <div class="review-head">
        <?php echo avatar_html($r['avatar'], $r['username'], $me && (int)$r['user_id'] === (int)$me['id']); ?>
        <div>
          <div><?php echo $r['content']; ?></div>
          <div class="meta"><?php echo htmlspecialchars($r['username']); ?> · <?php echo htmlspecialchars($r['created_at']); ?></div>
        </div>
      </div>

      <div class="review-actions">
        <?php if ($me): ?>
          <form method="post">
            <input type="hidden" name="toggle_like_review_id" value="<?php echo $reviewId; ?>">
            <button type="submit" class="like-btn<?php echo $liked ? ' liked' : ''; ?>">
              <?php echo $liked ? '❤️' : '🤍'; ?> Thích<?php echo $likeCount > 0 ? ' (' . $likeCount . ')' : ''; ?>
            </button>
          </form>
        <?php else: ?>
          <span class="like-btn disabled">🤍 Thích<?php echo $likeCount > 0 ? ' (' . $likeCount . ')' : ''; ?></span>
        <?php endif; ?>
      </div>

      <?php if ($replies): ?>
        <div class="review-replies">
          <?php foreach ($replies as $rep): ?>
            <div class="review-reply">
              <div class="review-head">
                <?php echo avatar_html($rep['avatar'], $rep['username'], $me && (int)$rep['user_id'] === (int)$me['id'], 'xs'); ?>
                <div>
                  <div class="meta">
                    <strong><?php echo htmlspecialchars($rep['username']); ?></strong>
                    <?php if (in_array($rep['role'], ['admin', 'collaborator'], true)): ?><span class="seller-badge">Người bán</span><?php endif; ?>
                    · <?php echo htmlspecialchars($rep['created_at']); ?>
                  </div>
                  <div><?php echo htmlspecialchars($rep['content']); ?></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($canReply): ?>
        <details class="reply-toggle">
          <summary>Trả lời</summary>
          <form method="post" class="reply-form">
            <input type="hidden" name="reply_review_id" value="<?php echo $reviewId; ?>">
            <textarea name="reply_content" placeholder="Viết trả lời..." required></textarea>
            <button type="submit">Gửi trả lời</button>
          </form>
        </details>
      <?php endif; ?>
    </div>
  <?php endwhile; ?>

  <?php if ($me && $canReview): ?>
    <form method="post" style="margin-top:16px;">
      <label for="review_content">Viết nhận xét của bạn</label>
      <textarea id="review_content" name="review_content" required></textarea>
      <button type="submit">Gửi nhận xét</button>
    </form>
  <?php elseif ($me): ?>
    <p class="muted" style="margin-top:16px;">Bạn cần mua và nhận sản phẩm này thành công mới có thể để lại đánh giá.</p>
  <?php else: ?>
    <p class="muted" style="margin-top:16px;"><a href="login.php">Đăng nhập</a> để đánh giá sản phẩm (chỉ áp dụng cho khách đã mua hàng).</p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
