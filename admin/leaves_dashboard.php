<?php
// admin/leaves_dashboard.php
require_once __DIR__ . '/../includes/config.php';
require_role('admin');

$counts = $pdo->query("SELECT status, COUNT(*) AS cnt FROM leaves GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

$recent = $pdo->query("SELECT l.*, s.name AS student_name, s.roll_no, r.name AS reviewer_name
                       FROM leaves l
                       JOIN users s ON l.student_id = s.id
                       LEFT JOIN users r ON l.reviewed_by = r.id
                       ORDER BY l.created_at DESC LIMIT 100")->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><title>Admin — Leaves Dashboard</title></head><body>
  <h1>Leaves Overview</h1>
  <p><a href="<?= e(url('admin/dashboard.php')) ?>">Back to Admin</a> | <a href="<?= e(url('public/logout.php')) ?>">Logout</a></p>

  <div>
    <strong>Pending:</strong> <?= (int)($counts['pending'] ?? 0) ?> &nbsp;
    <strong>Approved:</strong> <?= (int)($counts['approved'] ?? 0) ?> &nbsp;
    <strong>Rejected:</strong> <?= (int)($counts['rejected'] ?? 0) ?>
  </div>

  <h2>Recent Leaves</h2>
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
</body></html>
