<?php
// warden/approve_leave.php
require_once __DIR__ . '/../includes/config.php';
require_role('warden');

$errors = [];
$msg = '';

// Handle action (approve/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    verify_csrf();
    $id = (int)$_POST['id'];
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $comment = trim($_POST['comment'] ?? '');
    $warden_id = (int)$_SESSION['user']['id'];

    // Update
    $up = $pdo->prepare("UPDATE leaves SET status = :status, reviewed_by = :rb, review_comment = :rc, reviewed_at = NOW(), updated_at = NOW() WHERE id = :id");
    $up->execute([
        ':status' => $action,
        ':rb' => $warden_id,
        ':rc' => $comment,
        ':id' => $id
    ]);
    $msg = "Leave request #$id has been " . ($action === 'approved' ? 'approved' : 'rejected') . '.';
}

// Fetch pending and recent
$pendingStmt = $pdo->query("SELECT l.*, u.name as student_name, u.roll_no FROM leaves l JOIN users u ON l.student_id = u.id WHERE l.status = 'pending' ORDER BY l.created_at ASC");
$pending = $pendingStmt->fetchAll();

$recentStmt = $pdo->query("SELECT l.*, u.name as student_name, u.roll_no, r.name as reviewer_name FROM leaves l JOIN users u ON l.student_id = u.id LEFT JOIN users r ON l.reviewed_by = r.id ORDER BY l.created_at DESC LIMIT 50");
$recent = $recentStmt->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><title>Warden — Approve Leaves</title></head><body>
  <h1>Approve / Reject Leave Requests</h1>
  <p><a href="<?= e(url('warden/dashboard.php')) ?>">Back to Dashboard</a> | <a href="<?= e(url('public/logout.php')) ?>">Logout</a></p>

  <?php if ($msg): ?><div style="background:#e6ffed;padding:8px"><?= e($msg) ?></div><?php endif; ?>

  <h2>Pending Requests</h2>
  <?php if (empty($pending)): ?>
    <p>No pending leave requests.</p>
  <?php else: ?>
    <table border="1" cellpadding="6" cellspacing="0">
      <thead><tr><th>#</th><th>Student</th><th>Period</th><th>Reason</th><th>Requested At</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($pending as $i => $p): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= e($p['student_name']) ?> (<?= e($p['roll_no']) ?>)</td>
            <td><?= e($p['start_date']) ?> → <?= e($p['end_date']) ?></td>
            <td><?= e($p['reason']) ?></td>
            <td><?= e($p['created_at']) ?></td>
            <td>
              <form method="post" style="display:inline-block;margin:0">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="action" value="approve">
                <input name="comment" placeholder="Optional comment">
                <button type="submit">Approve</button>
              </form>

              <form method="post" style="display:inline-block;margin:0">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="action" value="reject">
                <input name="comment" placeholder="Optional comment">
                <button type="submit">Reject</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <h2>Recent Requests (last 50)</h2>
  <?php if (empty($recent)): ?>
    <p>No recent requests.</p>
  <?php else: ?>
    <table border="1" cellpadding="6" cellspacing="0">
      <thead><tr><th>#</th><th>Student</th><th>Period</th><th>Reason</th><th>Status</th><th>Reviewed By</th><th>Reviewed At</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $i => $r): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= e($r['student_name']) ?> (<?= e($r['roll_no']) ?>)</td>
            <td><?= e($r['start_date']) ?> → <?= e($r['end_date']) ?></td>
            <td><?= e($r['reason']) ?></td>
            <td><?= e(ucfirst($r['status'])) ?></td>
            <td><?= e($r['reviewer_name'] ?? '—') ?></td>
            <td><?= e($r['reviewed_at'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

</body></html>
