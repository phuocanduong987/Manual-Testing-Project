<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Admin luôn qua; cộng tác viên cần quyền 'reviews'.
require_permission('reviews', '../login.php');
$me = current_user();
$page_title = 'Quản lý đánh giá';
$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reviewId = (int)($_POST['review_id'] ?? 0);

    $chk = $conn->prepare("SELECT id, product_id FROM reviews WHERE id = ?");
    $chk->bind_param('i', $reviewId);
    $chk->execute();
    $rev = $chk->get_result()->fetch_assoc();

    if (!$rev) {
        $error = 'Không tìm thấy đánh giá.';
    } elseif ($action === 'reply') {
        $content = trim((string)($_POST['reply_content'] ?? ''));
        if ($content === '') {
            $error = 'Nội dung trả lời không được để trống.';
        } else {
            add_review_reply($conn, $reviewId, (int)$me['id'], $content);
            audit_log('review.reply', 'Trả lời đánh giá #' . $reviewId);
            $flash = 'Đã gửi trả lời.';
        }
    } elseif ($action === 'toggle_hidden') {
        $stmt = $conn->prepare("UPDATE reviews SET is_hidden = 1 - is_hidden WHERE id = ?");
        $stmt->bind_param('i', $reviewId);
        $stmt->execute();
        audit_log('review.toggle_hidden', 'Ẩn/hiện đánh giá #' . $reviewId);
        $flash = 'Đã cập nhật trạng thái hiển thị của đánh giá.';
    } elseif ($action === 'delete') {
        // Xoá vĩnh viễn là thao tác không hoàn tác được -> chỉ admin.
        if (!ui_admin()) {
            $error = 'Chỉ quản trị viên mới được xoá vĩnh viễn đánh giá (cộng tác viên có thể ẩn).';
        } else {
            $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->bind_param('i', $reviewId);
            $stmt->execute();
            audit_log('review.delete', 'Xoá đánh giá #' . $reviewId);
            $flash = 'Đã xoá đánh giá.';
        }
    }
}

$filter = $_GET['filter'] ?? 'all';
$staffReplied = "EXISTS (SELECT 1 FROM review_replies rr JOIN users su ON su.id = rr.user_id
                         WHERE rr.review_id = r.id AND su.role IN ('admin','collaborator'))";
$where = '1=1';
if ($filter === 'unanswered') $where = "r.is_hidden = 0 AND NOT $staffReplied";
elseif ($filter === 'hidden') $where = "r.is_hidden = 1";
$q = trim($_GET['q'] ?? '');

$sql = "SELECT r.id, r.content, r.is_hidden, r.created_at, r.product_id, p.name AS product_name, u.username,
               (SELECT COUNT(*) FROM review_likes l WHERE l.review_id = r.id) AS likes
        FROM reviews r JOIN products p ON p.id = r.product_id JOIN users u ON u.id = r.user_id
        WHERE $where";
$types = ''; $params = [];
if ($q !== '') { $sql .= " AND (r.content LIKE ? OR p.name LIKE ? OR u.username LIKE ?)"; $like = '%' . $q . '%'; $types = 'sss'; $params = [$like, $like, $like]; }
$sql .= " ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$counts = [
    'all' => (int)$conn->query("SELECT COUNT(*) c FROM reviews")->fetch_assoc()['c'],
    'unanswered' => (int)$conn->query("SELECT COUNT(*) c FROM reviews r WHERE r.is_hidden = 0 AND NOT $staffReplied")->fetch_assoc()['c'],
    'hidden' => (int)$conn->query("SELECT COUNT(*) c FROM reviews WHERE is_hidden = 1")->fetch_assoc()['c'],
];

require __DIR__ . '/../includes/admin_header.php';
?>
  <?php if ($flash): ?><div class="flash ok"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="flash error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <div class="tabs">
    <a href="?filter=all" class="<?php echo $filter === 'all' ? 'on' : ''; ?>">Tất cả (<?php echo $counts['all']; ?>)</a>
    <a href="?filter=unanswered" class="<?php echo $filter === 'unanswered' ? 'on' : ''; ?>">Chưa trả lời (<?php echo $counts['unanswered']; ?>)</a>
    <a href="?filter=hidden" class="<?php echo $filter === 'hidden' ? 'on' : ''; ?>">Đã ẩn (<?php echo $counts['hidden']; ?>)</a>
  </div>

  <div class="card">
    <form method="get" class="toolbar">
      <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
      <input type="search" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Tìm theo nội dung, sản phẩm, người viết">
      <button type="submit" class="btn-sm">Tìm</button>
    </form>

    <?php if (!$reviews): ?><div class="empty">Không có đánh giá nào.</div><?php endif; ?>

    <?php foreach ($reviews as $r):
        $replies = get_review_replies($conn, (int)$r['id']); ?>
      <div class="review-row <?php echo $r['is_hidden'] ? 'is-hidden' : ''; ?>">
        <div>
          <span class="who"><?php echo htmlspecialchars($r['username']); ?></span>
          <span class="muted">về</span>
          <a href="../product.php?id=<?php echo (int)$r['product_id']; ?>" target="_blank"><?php echo htmlspecialchars($r['product_name']); ?></a>
          <span class="muted">· <?php echo htmlspecialchars($r['created_at']); ?> · ❤️ <?php echo (int)$r['likes']; ?></span>
          <?php if ($r['is_hidden']): ?><span class="chip gray">Đã ẩn</span><?php endif; ?>
        </div>
        <?php /* Nội dung nhận xét luôn được escape trong khu vực quản trị để nội dung độc hại
                 (Stored XSS ở trang sản phẩm) không chạy được trên phiên của admin/cộng tác viên. */ ?>
        <div class="body"><?php echo htmlspecialchars($r['content']); ?></div>

        <?php foreach ($replies as $rep): $isStaff = in_array($rep['role'], ['admin', 'collaborator'], true); ?>
          <div class="reply-box <?php echo $isStaff ? 'staff' : ''; ?>">
            <strong><?php echo htmlspecialchars($rep['username']); ?></strong>
            <?php if ($isStaff): ?><span class="chip ok">Người bán</span><?php endif; ?>
            <span class="muted">· <?php echo htmlspecialchars($rep['created_at']); ?></span><br>
            <?php echo htmlspecialchars($rep['content']); ?>
          </div>
        <?php endforeach; ?>

        <details style="margin-top:8px;">
          <summary style="cursor:pointer;color:var(--accent);">💬 Trả lời</summary>
          <form method="post" style="margin-top:8px;">
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="review_id" value="<?php echo (int)$r['id']; ?>">
            <textarea name="reply_content" placeholder="Viết phản hồi với tư cách người bán..." required></textarea>
            <button type="submit" class="btn-sm">Gửi trả lời</button>
          </form>
        </details>

        <div style="margin-top:8px;display:flex;gap:8px;">
          <form method="post" class="inline-form">
            <input type="hidden" name="action" value="toggle_hidden">
            <input type="hidden" name="review_id" value="<?php echo (int)$r['id']; ?>">
            <button type="submit" class="btn-sm btn-secondary"><?php echo $r['is_hidden'] ? '👁 Hiện lại' : '🙈 Ẩn khỏi trang sản phẩm'; ?></button>
          </form>
          <?php if (ui_admin()): ?>
            <form method="post" class="inline-form" onsubmit="return confirm('Xoá vĩnh viễn đánh giá này?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="review_id" value="<?php echo (int)$r['id']; ?>">
              <button type="submit" class="btn-sm btn-danger">Xoá</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
