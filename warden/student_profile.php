<?php
// warden/student_profile.php
require_once __DIR__ . '/../includes/config.php';
require_role('warden');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('warden/students.php');
}

// fetch student
$stmt = $pdo->prepare("SELECT id, name, email, roll_no, room_no, phone, guardian_name, guardian_contact FROM users WHERE id = :id AND role = 'student' LIMIT 1");
$stmt->execute([':id' => $id]);
$student = $stmt->fetch();
if (!$student) {
    redirect('warden/students.php');
}

$errors = [];
$msg = '';

// Handle update by warden (only a few editable fields)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    verify_csrf();
    $room = trim($_POST['room_no'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gname = trim($_POST['guardian_name'] ?? '');
    $gcontact = trim($_POST['guardian_contact'] ?? '');

    // basic validation
    if ($room === '') $room = null;
    if ($phone === '') $phone = null;

    $up = $pdo->prepare("UPDATE users SET room_no = :room, phone = :phone, guardian_name = :gname, guardian_contact = :gcontact WHERE id = :id");
    $up->execute([
        ':room' => $room,
        ':phone' => $phone,
        ':gname' => $gname,
        ':gcontact' => $gcontact,
        ':id' => $id
    ]);
    $msg = 'Student info updated.';
    // refresh $student
    $stmt->execute([':id' => $id]);
    $student = $stmt->fetch();
}

// Attendance summary (last 30 days)
$fromDate = (new DateTime())->modify('-29 days')->format('Y-m-d');
$toDate = date('Y-m-d');

// total days where attendance exists for that student between dates
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE student_id = :sid AND date BETWEEN :from AND :to");
$totalStmt->execute([':sid'=>$id, ':from'=>$fromDate, ':to'=>$toDate]);
$totalDays = (int)$totalStmt->fetchColumn();

// present days
$presentStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE student_id = :sid AND date BETWEEN :from AND :to AND status = 'present'");
$presentStmt->execute([':sid'=>$id, ':from'=>$fromDate, ':to'=>$toDate]);
$presentDays = (int)$presentStmt->fetchColumn();

$presentPct = $totalDays > 0 ? round(100 * $presentDays / $totalDays, 1) : null;

// recent leaves for this student
$leavesStmt = $pdo->prepare("SELECT * FROM leaves WHERE student_id = :sid ORDER BY created_at DESC LIMIT 20");
$leavesStmt->execute([':sid'=>$id]);
$leaves = $leavesStmt->fetchAll();

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Student Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
<?php include_once __DIR__ . '/../includes/ui/sidebar.php'; include_once __DIR__ . '/../includes/ui/header.php'; ?>
<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:pl-64">
  <div class="bg-white p-6 rounded shadow">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold"><?= e($student['name']) ?></h1>
        <div class="text-sm text-gray-600"><?= e($student['email']) ?> • Roll: <?= e($student['roll_no']) ?></div>
      </div>
      <div class="text-right">
        <a href="<?= e(url('warden/students.php')) ?>" class="text-indigo-600 hover:underline">Back to list</a><br>
        <a href="<?= e(url('warden/mark_attendance.php?date=' . date('Y-m-d'))) ?>" class="mt-2 inline-block px-3 py-1 bg-indigo-600 text-white rounded">Mark Today</a>
      </div>
    </div>

    <?php if ($msg): ?>
      <div class="mt-4 p-3 bg-green-50 text-green-800 rounded"><?= e($msg) ?></div>
    <?php endif; ?>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="md:col-span-2">
        <h2 class="text-lg font-medium">Details</h2>
        <form method="post" class="mt-3 space-y-3">
          <?= csrf_field() ?>
          <label class="block"><div class="text-sm text-gray-600">Room No</div>
            <input name="room_no" value="<?= e($student['room_no']) ?>" class="w-full px-3 py-2 border rounded">
          </label>
          <label class="block"><div class="text-sm text-gray-600">Phone</div>
            <input name="phone" value="<?= e($student['phone']) ?>" class="w-full px-3 py-2 border rounded">
          </label>
          <label class="block"><div class="text-sm text-gray-600">Guardian Name</div>
            <input name="guardian_name" value="<?= e($student['guardian_name']) ?>" class="w-full px-3 py-2 border rounded">
          </label>
          <label class="block"><div class="text-sm text-gray-600">Guardian Contact</div>
            <input name="guardian_contact" value="<?= e($student['guardian_contact']) ?>" class="w-full px-3 py-2 border rounded">
          </label>
          <div><button type="submit" name="save" class="px-4 py-2 bg-indigo-600 text-white rounded">Save changes</button></div>
        </form>
      </div>

      <div>
        <h2 class="text-lg font-medium">Attendance (last 30 days)</h2>
        <div class="mt-2 text-sm text-gray-700">
          <?php if ($presentPct === null): ?>
            <div>No attendance records in the last 30 days.</div>
          <?php else: ?>
            <div class="text-2xl font-semibold"><?= e($presentPct) ?>%</div>
            <div class="text-xs text-gray-500"><?= e($presentDays) ?> present out of <?= e($totalDays) ?> days recorded</div>
          <?php endif; ?>
        </div>

        <h3 class="mt-4 text-md font-medium">Recent Leaves</h3>
        <?php if (empty($leaves)): ?>
          <div class="text-sm text-gray-500 mt-2">No leave requests.</div>
        <?php else: ?>
          <ul class="mt-2 space-y-2 text-sm">
            <?php foreach ($leaves as $lv): ?>
              <li class="border p-2 rounded">
                <div><strong><?= e($lv['start_date']) ?> → <?= e($lv['end_date']) ?></strong> — <?= e(ucfirst($lv['status'])) ?></div>
                <div class="text-xs text-gray-600"><?= e($lv['reason']) ?></div>
                <div class="text-xs text-gray-500 mt-1">Reviewed by: <?= e($lv['reviewed_by'] ? $lv['reviewed_by'] : '—') ?> at <?= e($lv['reviewed_at'] ?? '—') ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>
<?php include_once __DIR__ . '/../includes/ui/footer.php'; ?>
</body>
</html>
