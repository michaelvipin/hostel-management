<?php
// warden/mark_attendance.php
require_once __DIR__ . '/../includes/config.php';
require_role('warden'); // ensure only wardens access

// enable errors in dev (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$pdo->beginTransaction();
$msg = '';
$errors = [];

try {
    // Handle form POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();

        $date = $_POST['date'] ?? '';
        if (!$date) {
            $errors[] = 'Date is required.';
        } else {
            // normalize date
            $d = date_create_from_format('Y-m-d', $date);
            if (!$d) $errors[] = 'Invalid date format.';
        }

        $statuses = $_POST['status'] ?? []; // expected: [student_id => 'present'/'absent']
        if (empty($errors)) {
            // prepare statement for upsert
            // MySQL: use INSERT ... ON DUPLICATE KEY UPDATE
            $sql = "INSERT INTO attendance (student_id, date, status, marked_by, marked_at)
                    VALUES (:student_id, :date, :status, :marked_by, NOW())
                    ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by), marked_at = NOW()";
            $stmt = $pdo->prepare($sql);

            $warden_id = (int)$_SESSION['user']['id'];
            $count = 0;

            // use transaction
            $pdo->beginTransaction();
            foreach ($statuses as $sid => $status) {
                $sid = (int)$sid;
                $status = ($status === 'present') ? 'present' : 'absent';
                $stmt->execute([
                    ':student_id' => $sid,
                    ':date' => $date,
                    ':status' => $status,
                    ':marked_by' => $warden_id
                ]);
                $count++;
            }
            $pdo->commit();

            $msg = "Saved attendance for {$count} student(s) on " . e($date);
        }
    }

    // Fetch students to display (ordered by roll_no then name)
    $students = $pdo->query("SELECT id, name, roll_no FROM users WHERE role = 'student' ORDER BY roll_no ASC, name ASC")->fetchAll();

} catch (Exception $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $errors[] = 'An error occurred: ' . $ex->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Mark Attendance — Warden</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f4f6f8;margin:0;padding:24px}
    .wrap{max-width:1100px;margin:24px auto;background:#fff;padding:18px;border-radius:8px;box-shadow:0 6px 24px rgba(20,30,40,.06)}
    h1{margin-top:0}
    .topbar{display:flex;gap:12px;align-items:center}
    .btn{padding:8px 12px;border-radius:6px;border:none;cursor:pointer}
    .btn-primary{background:#0b5ed7;color:#fff}
    .btn-ghost{background:#f1f3f5;border:1px solid #e3e6ea}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{padding:10px;border:1px solid #e9eef3;text-align:left}
    th{background:#f7fafc}
    .msg{padding:8px;border-radius:6px;margin-top:12px}
    .success{background:#e6ffed;color:#0a7a3a}
    .error{background:#fff0f0;color:#a70000}
    .controls{display:flex;gap:8px;align-items:center}
    @media (max-width:720px){
      table, thead, tbody, th, td, tr{display:block}
      th{display:none}
      td{border:none;padding:6px}
      tr{margin-bottom:10px;border:1px solid #eee;padding:8px;border-radius:6px}
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <h1>Mark Attendance</h1>
      <div style="margin-left:auto">
        <a class="btn btn-ghost" href="<?= BASE_URL ?>/warden/dashboard.php">Back to Dashboard</a>
        <a class="btn btn-ghost" href="<?= BASE_URL ?>/public/logout.php">Logout</a>
      </div>
    </div>

    <?php if ($msg): ?>
      <div class="msg success"><?= e($msg) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="msg error">
        <?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <?= csrf_field() ?>
      <div style="display:flex;gap:12px;align-items:center;margin-top:12px">
        <label>
          Date:
          <input type="date" name="date" required value="<?= e($_POST['date'] ?? date('Y-m-d')) ?>">
        </label>

        <div class="controls" style="margin-left:16px">
          <button type="button" id="markAllPresent" class="btn btn-primary">Mark All Present</button>
          <button type="button" id="markAllAbsent" class="btn btn-ghost">Mark All Absent</button>
        </div>

        <div style="margin-left:auto">
          <button type="submit" class="btn btn-primary">Save Attendance</button>
        </div>
      </div>

      <table aria-describedby="attendance-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Roll No</th>
            <th>Name</th>
            <th>Present</th>
            <th>Absent</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
            <tr><td colspan="5">No students found.</td></tr>
          <?php else: foreach ($students as $i => $s): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><?= e($s['roll_no']) ?></td>
              <td><?= e($s['name']) ?></td>
              <td style="text-align:center">
                <input type="radio" name="status[<?= (int)$s['id'] ?>]" value="present" id="p-<?= (int)$s['id'] ?>" checked>
              </td>
              <td style="text-align:center">
                <input type="radio" name="status[<?= (int)$s['id'] ?>]" value="absent" id="a-<?= (int)$s['id'] ?>">
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </form>
  </div>

  <script>
    // mark all present / absent quick toggles
    document.getElementById('markAllPresent').addEventListener('click', function(){
      document.querySelectorAll('input[type="radio"][value="present"]').forEach(function(r){ r.checked = true; });
    });
    document.getElementById('markAllAbsent').addEventListener('click', function(){
      document.querySelectorAll('input[type="radio"][value="absent"]').forEach(function(r){ r.checked = true; });
    });
  </script>
</body>
</html>
