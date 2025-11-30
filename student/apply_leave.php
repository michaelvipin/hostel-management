<?php
// student/apply_leave.php
require_once __DIR__ . '/../includes/config.php';
require_role('student');

$errors = [];
$msg = '';

$student_id = (int)$_SESSION['user']['id'];

// Handle POST: create new leave
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $start = trim($_POST['start_date'] ?? '');
    $end   = trim($_POST['end_date'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    // basic validation
    if (!$start || !$end) {
        $errors[] = 'Start and end date are required.';
    } else {
        $sd = DateTime::createFromFormat('Y-m-d', $start);
        $ed = DateTime::createFromFormat('Y-m-d', $end);
        if (!$sd || !$ed) {
            $errors[] = 'Invalid date format.';
        } elseif ($ed < $sd) {
            $errors[] = 'End date must be same or after start date.';
        }
    }
    if (strlen($reason) < 6) {
        $errors[] = 'Reason is too short (min 6 chars).';
    }

    if (empty($errors)) {
        $ins = $pdo->prepare("INSERT INTO leaves (student_id, start_date, end_date, reason) VALUES (:student_id, :start, :end, :reason)");
        $ins->execute([
            ':student_id' => $student_id,
            ':start' => $sd->format('Y-m-d'),
            ':end' => $ed->format('Y-m-d'),
            ':reason' => $reason
        ]);
        $msg = 'Leave request submitted successfully.';
    }
}

// Fetch my leaves
$stmt = $pdo->prepare("SELECT l.*, u.name as reviewer_name FROM leaves l LEFT JOIN users u ON l.reviewed_by = u.id WHERE l.student_id = :sid ORDER BY l.created_at DESC");
$stmt->execute([':sid' => $student_id]);
$leaves = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Apply for Leave</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f6f8fa;padding:24px}
    .card{max-width:800px;margin:36px auto;padding:18px;background:#fff;border-radius:8px;box-shadow:0 6px 20px rgba(10,20,30,.06)}
    input, textarea{width:100%;padding:10px;margin:8px 0;border:1px solid #ddd;border-radius:4px}
    button{padding:10px 14px;border:none;background:#0b5ed7;color:#fff;border-radius:5px;cursor:pointer}
    .error{background:#fff0f0;color:#a70000;padding:8px;border-radius:6px}
    .success{background:#e6ffed;color:#0a7a3a;padding:8px;border-radius:6px}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{padding:8px;border:1px solid #e9eef3;text-align:left}
    th{background:#f7fafc}
  </style>
</head>
<body>
  <div class="card">
    <h1>Apply for Leave</h1>
    <p><a href="<?= e(url('student/dashboard.php')) ?>">Back to Dashboard</a> | <a href="<?= e(url('public/logout.php')) ?>">Logout</a></p>

    <?php if (!empty($msg)): ?>
      <div class="success"><?= e($msg) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="error">
        <?php foreach ($errors as $er) : ?>
          <div><?= e($er) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <?= csrf_field() ?>
      <label>
        Start date
        <input type="date" name="start_date" required value="<?= e($_POST['start_date'] ?? '') ?>">
      </label>
      <label>
        End date
        <input type="date" name="end_date" required value="<?= e($_POST['end_date'] ?? '') ?>">
      </label>
      <label>
        Reason
        <textarea name="reason" rows="4" required><?= e($_POST['reason'] ?? '') ?></textarea>
      </label>
      <div style="display:flex;gap:8px">
        <button type="submit">Submit Leave Request</button>
      </div>
    </form>

    <h2 style="margin-top:20px">Your Leaves</h2>
    <?php if (empty($leaves)): ?>
      <p>No leave requests yet.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr><th>#</th><th>Period</th><th>Reason</th><th>Status</th><th>Reviewed By</th><th>Reviewed At</th><th>Comment</th></tr>
        </thead>
        <tbody>
          <?php foreach ($leaves as $i => $l): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><?= e($l['start_date']) ?> → <?= e($l['end_date']) ?></td>
              <td><?= e($l['reason']) ?></td>
              <td><?= e(ucfirst($l['status'])) ?></td>
              <td><?= e($l['reviewer_name'] ?? '—') ?></td>
              <td><?= e($l['reviewed_at'] ?? '—') ?></td>
              <td><?= e($l['review_comment'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</body>
</html>
