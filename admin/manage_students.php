<?php
// admin/manage_students.php
require_once __DIR__ . '/../includes/config.php';
require_role('admin');

ini_set('display_errors',1);
error_reporting(E_ALL);

$perPage = 25;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$errors = [];
$msg = '';
$one_time_pw = ''; // shown after create

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $roll = trim($_POST['roll_no'] ?? '');
    $room = trim($_POST['room_no'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '' || $email === '') $errors[] = 'Name and email are required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';

    // email uniqueness check
    $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $chk->execute([':email' => $email]);
    if ($chk->fetchColumn() > 0) $errors[] = 'Email already in use.';

    if (empty($errors)) {
        // generate secure temp password (12 chars)
        $temp = bin2hex(random_bytes(6));
        $hash = password_hash($temp, PASSWORD_DEFAULT);

        $ins = $pdo->prepare("INSERT INTO users (name, email, password, role, roll_no, room_no, phone) VALUES (:name, :email, :pw, 'student', :roll, :room, :phone)");
        $ins->execute([
            ':name' => $name,
            ':email' => $email,
            ':pw' => $hash,
            ':roll' => $roll ?: null,
            ':room' => $room ?: null,
            ':phone' => $phone ?: null
        ]);
        $one_time_pw = $temp;
        $msg = "Student created successfully. Temporary password shown below — copy it and share securely.";
    }
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) $errors[] = 'Invalid student id.';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $roll = trim($_POST['roll_no'] ?? '');
    $room = trim($_POST['room_no'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gname = trim($_POST['guardian_name'] ?? '');
    $gcontact = trim($_POST['guardian_contact'] ?? '');

    if ($name === '' || $email === '') $errors[] = 'Name and email are required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';

    // check email uniqueness excluding this id
    $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND id != :id");
    $chk->execute([':email'=>$email, ':id'=>$id]);
    if ($chk->fetchColumn() > 0) $errors[] = 'Email already in use by another account.';

    if (empty($errors)) {
        $up = $pdo->prepare("UPDATE users SET name=:name, email=:email, roll_no=:roll, room_no=:room, phone=:phone, guardian_name=:gname, guardian_contact=:gcontact WHERE id=:id AND role='student'");
        $up->execute([
            ':name'=>$name, ':email'=>$email, ':roll'=>$roll ?: null, ':room'=>$room ?: null,
            ':phone'=>$phone ?: null, ':gname'=>$gname ?: null, ':gcontact'=>$gcontact ?: null, ':id'=>$id
        ]);
        $msg = 'Student updated.';
    }
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) $errors[] = 'Invalid student id.';
    if (empty($errors)) {
        $del = $pdo->prepare("DELETE FROM users WHERE id = :id AND role='student'");
        $del->execute([':id' => $id]);
        $msg = 'Student deleted.';
    }
}

// Fetch students (paginated)
$totalStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'");
$total = (int)$totalStmt->fetchColumn();
$pages = max(1, ceil($total / $perPage));

$listStmt = $pdo->prepare("SELECT id, name, email, roll_no, room_no, phone, guardian_name FROM users WHERE role='student' ORDER BY roll_no ASC, name ASC LIMIT :lim OFFSET :off");
$listStmt->bindValue(':lim', (int)$perPage, PDO::PARAM_INT);
$listStmt->bindValue(':off', (int)$offset, PDO::PARAM_INT);
$listStmt->execute();
$students = $listStmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin — Manage Students</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
<?php include_once __DIR__ . '/../includes/ui/sidebar.php'; ?>
<div class="md:pl-64">
  <?php include_once __DIR__ . '/../includes/ui/header.php'; ?>

  <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <h1 class="text-2xl font-semibold mb-4">Manage Students</h1>

    <?php if ($msg): ?><div class="mb-4 p-3 bg-green-50 text-green-800 rounded"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($one_time_pw): ?><div class="mb-4 p-3 bg-yellow-50 text-yellow-800 rounded"><strong>Temporary password:</strong> <?= e($one_time_pw) ?> — share securely and advise reset on first login.</div><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="mb-4 p-3 bg-red-50 text-red-800 rounded"><?php foreach($errors as $er) echo '<div>'.e($er).'</div>'; ?></div><?php endif; ?>

    <!-- Create form -->
    <section class="bg-white p-4 rounded shadow mb-6">
      <h2 class="text-lg font-medium mb-2">Create student</h2>
      <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <div>
          <label class="block text-sm text-gray-600">Name</label>
          <input name="name" class="w-full px-3 py-2 border rounded" required>
        </div>
        <div>
          <label class="block text-sm text-gray-600">Email</label>
          <input name="email" type="email" class="w-full px-3 py-2 border rounded" required>
        </div>
        <div>
          <label class="block text-sm text-gray-600">Roll No</label>
          <input name="roll_no" class="w-full px-3 py-2 border rounded">
        </div>
        <div>
          <label class="block text-sm text-gray-600">Room No</label>
          <input name="room_no" class="w-full px-3 py-2 border rounded">
        </div>
        <div>
          <label class="block text-sm text-gray-600">Phone</label>
          <input name="phone" class="w-full px-3 py-2 border rounded">
        </div>
        <div class="text-right">
          <button class="px-4 py-2 bg-indigo-600 text-white rounded">Create</button>
        </div>
      </form>
    </section>

    <!-- List -->
    <section class="bg-white p-4 rounded shadow">
      <div class="overflow-auto">
        <table class="min-w-full divide-y">
          <thead><tr class="text-sm text-gray-600"><th class="px-3 py-2">#</th><th class="px-3 py-2">Roll</th><th class="px-3 py-2">Name</th><th class="px-3 py-2">Email</th><th class="px-3 py-2">Room</th><th class="px-3 py-2">Phone</th><th class="px-3 py-2">Actions</th></tr></thead>
          <tbody>
            <?php if (empty($students)): ?>
              <tr><td class="px-3 py-3" colspan="7">No students found.</td></tr>
            <?php else: foreach($students as $i=>$s): ?>
              <tr class="<?= $i%2 ? 'bg-gray-50' : '' ?>">
                <td class="px-3 py-2"><?= e($offset + $i + 1) ?></td>
                <td class="px-3 py-2"><?= e($s['roll_no'] ?? '—') ?></td>
                <td class="px-3 py-2"><?= e($s['name']) ?></td>
                <td class="px-3 py-2"><?= e($s['email']) ?></td>
                <td class="px-3 py-2"><?= e($s['room_no'] ?? '—') ?></td>
                <td class="px-3 py-2"><?= e($s['phone'] ?? '—') ?></td>
                <td class="px-3 py-2">
                  <!-- Edit: opens simple inline form -->
                  <details>
                    <summary class="text-indigo-600 cursor-pointer">Edit</summary>
                    <form method="post" class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-2">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                      <input name="name" value="<?= e($s['name']) ?>" class="px-2 py-1 border rounded">
                      <input name="email" value="<?= e($s['email']) ?>" class="px-2 py-1 border rounded">
                      <input name="roll_no" value="<?= e($s['roll_no']) ?>" class="px-2 py-1 border rounded">
                      <input name="room_no" value="<?= e($s['room_no']) ?>" class="px-2 py-1 border rounded">
                      <input name="phone" value="<?= e($s['phone']) ?>" class="px-2 py-1 border rounded">
                      <input name="guardian_name" placeholder="Guardian name" value="<?= e($s['guardian_name'] ?? '') ?>" class="px-2 py-1 border rounded">
                      <input name="guardian_contact" placeholder="Guardian contact" value="<?= e($s['guardian_contact'] ?? '') ?>" class="px-2 py-1 border rounded">
                      <div class="md:col-span-3 text-right">
                        <button class="px-3 py-1 bg-green-600 text-white rounded">Save</button>
                      </div>
                    </form>
                  </details>

                  <form method="post" onsubmit="return confirm('Delete this student? This will remove their account and related data.');" class="inline-block mt-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <button class="px-2 py-1 bg-red-600 text-white rounded">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
        <div>Showing <?= $offset+1 ?> – <?= min($offset+$perPage, $total) ?> of <?= $total ?></div>
        <div class="space-x-2">
          <?php if ($page > 1): ?><a class="px-3 py-1 border rounded" href="<?= e(url('admin/manage_students.php?page=' . ($page-1))) ?>">Prev</a><?php endif; ?>
          <?php if ($page < $pages): ?><a class="px-3 py-1 border rounded" href="<?= e(url('admin/manage_students.php?page=' . ($page+1))) ?>">Next</a><?php endif; ?>
        </div>
      </div>

    </section>
  </main>

  <?php include_once __DIR__ . '/../includes/ui/footer.php'; ?>
</div>
</body>
</html>
