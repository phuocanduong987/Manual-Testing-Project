<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Nhật ký chỉ admin được xem (để cộng tác viên không tự xoá dấu vết / xem việc của nhau).
require_admin('../login.php');
$me = current_user();
$page_title = 'Nhật ký hoạt động';

$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$where = '1=1'; $types = ''; $params = [];
if ($q !== '') { $where = "(username LIKE ? OR action LIKE ? OR detail LIKE ?)"; $like = '%' . $q . '%'; $types = 'sss'; $params = [$like, $like, $like]; }

$cs = $conn->prepare("SELECT COUNT(*) c FROM audit_logs WHERE $where");
if ($types !== '') $cs->bind_param($types, ...$params);
$cs->execute();
$total = (int)$cs->get_result()->fetch_assoc()['c'];

$ls = $conn->prepare("SELECT * FROM audit_logs WHERE $where ORDER BY id DESC LIMIT ? OFFSET ?");
$t2 = $types . 'ii'; $p2 = array_merge($params, [$perPage, $offset]);
$ls->bind_param($t2, ...$p2);
$ls->execute();
$logs = $ls->get_result()->fetch_all(MYSQLI_ASSOC);
$pages = max(1, (int)ceil($total / $perPage));

require __DIR__ . '/../includes/admin_header.php';
?>
  <div class="card">
    <form method="get" class="toolbar">
      <input type="search" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Tìm theo người thực hiện, hành động, nội dung">
      <button type="submit" class="btn-sm">Tìm</button>
      <span class="muted"><?php echo $total; ?> bản ghi</span>
    </form>
    <div class="table-wrap"><table>
      <tr><th>Thời gian</th><th>Người thực hiện</th><th>Hành động</th><th>Chi tiết</th><th>IP</th></tr>
      <?php foreach ($logs as $l): ?>
        <tr>
          <td class="muted" style="white-space:nowrap;"><?php echo htmlspecialchars($l['created_at']); ?></td>
          <td><?php echo htmlspecialchars($l['username']); ?></td>
          <td><span class="chip"><?php echo htmlspecialchars($l['action']); ?></span></td>
          <td><?php echo htmlspecialchars($l['detail'] ?? ''); ?></td>
          <td class="muted"><?php echo htmlspecialchars($l['ip'] ?? ''); ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$logs): ?><tr><td colspan="5" class="empty">Chưa có hoạt động nào được ghi lại.</td></tr><?php endif; ?>
    </table></div>
    <?php if ($pages > 1): ?>
      <div class="tabs" style="margin-top:14px;">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <a href="?page=<?php echo $i; ?>&q=<?php echo urlencode($q); ?>" class="<?php echo $i === $page ? 'on' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
