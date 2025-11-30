<?php
// warden/mark_attendance.php
require_once __DIR__ . '/../includes/config.php';
require_role('warden'); // allow only wardens

ini_set('display_errors', 1);
error_reporting(E_ALL);

$errors = [];
$msg = '';

// default date: today or from GET/POST
$date = $_GET['date'] ?? ($_POST['date'] ?? date('Y-m-d'));

// normalize date format
$dateObj = DateTime::createFromFormat('Y-m-d', $date);
if (!$dateObj) {
    $date = date('Y-m-d');
} else {
    $date = $dateObj->format('Y-m-d');
}

// handle POST: save attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $statuses = $_POST['status'] ?? []; // expected: [student_id => 'present'|'absent']
    if (empty($statuses) || !is_array($statuses)) {
        $errors[] = 'No attendance data submitted.';
    } else {
        try {
            // upsert using INSERT ... ON DUPLICATE KEY UPDATE
            $sql = "INSERT INTO attendance (student_id, date, status, marked_by, marked_at)
                    VALUES (:student_id, :date, :status, :marked_by, NOW())
                    ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by), marked_at = VALUES(marked_at)";
            $stmt = $pdo->prepare($sql);

            $warden_id = (int)$_SESSION['user']['id'];
            $pdo->beginTransaction();
            $count = 0;
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
            $msg = "Saved attendance for {$count} student(s) on " . e($date) . ".";
            // reload existing data below with updated values
        } catch (Exception $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Database error: ' . $ex->getMessage();
        }
    }
}

// Fetch all students
$studentsStmt = $pdo->query("SELECT id, name, roll_no FROM users WHERE role = 'student' ORDER BY roll_no ASC, name ASC");
$students = $studentsStmt->fetchAll();

// Fetch attendance for selected date and who marked
$attendance = [];
if (!empty($students)) {
    $inDateStmt = $pdo->prepare("
        SELECT a.student_id, a.status, a.marked_at, a.marked_by, u.name AS marked_by_name
        FROM attendance a
        LEFT JOIN users u ON a.marked_by = u.id
        WHERE a.date = :date
    ");
    $inDateStmt->execute([':date' => $date]);
    $rows = $inDateStmt->fetchAll();
    foreach ($rows as $r) {
        $attendance[(int)$r['student_id']] = [
            'status' => $r['status'],
            'marked_at' => $r['marked_at'],
            'marked_by' => $r['marked_by'],
            'marked_by_name' => $r['marked_by_name']
        ];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Warden — Mark Attendance</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f4f6f8;margin:0;padding:20px}
    .wrap{max-width:1000px;margin:20px auto;background:#fff;padding:18px;border-radius:8px;box-shadow:0 6px 18px rgba(20,30,40,.06)}
    h1{margin-top:0}
    .top{display:flex;align-items:center;gap:12px}
    .top .actions{margin-left:auto}
    input[type="date"]{padding:8px;border-radius:6px;border:1px solid #ccc}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{padding:10px;border:1px solid #e9eef3;text-align:left}
    th{background:#f7fafc}
    .btn{padding:8px 12px;border-radius:6px;border:0;cursor:pointer}
    .btn-primary{background:#0b5ed7;color:#fff}
    .btn-ghost{background:#f1f3f5;border:1px solid #e3e6ea}
    .msg{padding:10px;border-radius:6px;margin-top:12px}
    .success{background:#e6ffed;color:#0a7a3a}
    .error{background:#fff0f0;color:#a70000}
    .small{font-size:12px;color:#666}
    @media (max-width:720px){
      table,thead,tbody,th,td,tr{display:block}
      th{display:none}
      td{border:none;padding:6px}
      tr{margin-bottom:10px;border:1px solid #eee;padding:8px;border-radius:6px}
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <h1>Mark Attendance</h1>
      <div class="actions">
        <a class="btn btn-ghost" href="<?= e(url('warden/dashboard.php')) ?>">Back to Dashboard</a>
        <a class="btn btn-ghost" href="<?= e(url('public/logout.php')) ?>">Logout</a>
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
          <input type="date" name="date" required value="<?= e($date) ?>">
        </label>

        <div style="margin-left:8px">
          <button type="submit" name="load" value="1" class="btn btn-ghost">Load</button>
        </div>

        <div style="margin-left:auto;display:flex;gap:8px">
          <button type="button" id="markAllPresent" class="btn btn-primary">Mark All Present</button>
          <button type="button" id="markAllAbsent" class="btn btn-ghost">Mark All Absent</button>
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
            <th>Marked By / At</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
            <tr><td colspan="6">No students found.</td></tr>
          <?php else: foreach ($students as $i => $s): 
              $sid = (int)$s['id'];
              $st = $attendance[$sid]['status'] ?? 'present';
              $markedBy = $attendance[$sid]['marked_by_name'] ?? '';
              $markedAt = $attendance[$sid]['marked_at'] ?? '';
          ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><?= e($s['roll_no']) ?></td>
              <td><?= e($s['name']) ?></td>
              <td style="text-align:center">
                <input type="radio" name="status[<?= $sid ?>]" value="present" id="p-<?= $sid ?>" <?= $st === 'present' ? 'checked' : ''?>>
              </td>
              <td style="text-align:center">
                <input type="radio" name="status[<?= $sid ?>]" value="absent" id="a-<?= $sid ?>" <?= $st === 'absent' ? 'checked' : ''?>>
              </td>
              <td class="small">
                <?= $markedBy ? e($markedBy) . '<br>' . e($markedAt) : '<span class="small">—</span>' ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </form>
  </div>

<script>
document.getElementById('markAllPresent').addEventListener('click', function(){
  document.querySelectorAll('input[type="radio"][value="present"]').forEach(function(r){ r.checked = true; });
});
document.getElementById('markAllAbsent').addEventListener('click', function(){
  document.querySelectorAll('input[type="radio"][value="absent"]').forEach(function(r){ r.checked = true; });
});
</script>
</body>
</html>
