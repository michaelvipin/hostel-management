<?php
// warden/students.php
require_once __DIR__ . '/../includes/config.php';
require_role('warden');

$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$search = trim($_GET['q'] ?? '');

// Build base query and params
$where = "WHERE role = 'student'";
$params = [];

if ($search !== '') {
    $where .= " AND (name LIKE :q OR email LIKE :q OR roll_no LIKE :q OR room_no LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}

// total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// fetch page
$stmt = $pdo->prepare("SELECT id, name, email, roll_no, room_no, phone FROM users $where ORDER BY roll_no ASC, name ASC LIMIT :lim OFFSET :off");
foreach ($params as $k=>$v) { /* bound later */ }
$stmt->bindValue(':lim', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', (int)$offset, PDO::PARAM_INT);
if (isset($params[':q'])) {
    $stmt->bindValue(':q', $params[':q'], PDO::PARAM_STR);
}
$stmt->execute();
$students = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Warden — Students</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
<?php include_once __DIR__ . '/../includes/ui/sidebar.php'; include_once __DIR__ . '/../includes/ui/header.php'; ?>
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:pl-64">
  <div class="bg-white p-4 rounded shadow">
    <div class="flex items-center gap-3 mb-4">
      <h1 class="text-xl font-semibold">Students</h1>
      <form method="get" class="ml-auto flex items-center gap-2">
        <input type="search" name="q" placeholder="Search name, email, roll, room" value="<?= e($search) ?>" class="px-3 py-2 border rounded">
        <button class="px-3 py-2 bg-indigo-600 text-white rounded">Search</button>
      </form>
    </div>

    <div class="overflow-auto">
      <table class="min-w-full divide-y">
        <thead>
          <tr class="text-left text-sm text-gray-600">
            <th class="px-3 py-2">#</th>
            <th class="px-3 py-2">Roll</th>
            <th class="px-3 py-2">Name</th>
            <th class="px-3 py-2">Room</th>
            <th class="px-3 py-2">Phone</th>
            <th class="px-3 py-2">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($students)): ?>
          <tr><td class="px-3 py-4" colspan="6">No students found.</td></tr>
        <?php else: foreach ($students as $i => $s): ?>
          <tr class="<?= $i%2 ? 'bg-gray-50' : '' ?>">
            <td class="px-3 py-2"><?= e($offset + $i + 1) ?></td>
            <td class="px-3 py-2"><?= e($s['roll_no']) ?></td>
            <td class="px-3 py-2"><?= e($s['name']) ?><div class="text-xs text-gray-500"><?= e($s['email']) ?></div></td>
            <td class="px-3 py-2"><?= e($s['room_no'] ?? '—') ?></td>
            <td class="px-3 py-2"><?= e($s['phone'] ?? '—') ?></td>
            <td class="px-3 py-2">
              <a href="<?= e(url('warden/student_profile.php?id=' . $s['id'])) ?>" class="text-indigo-600 hover:underline mr-3">View</a>
              <a href="<?= e(url('warden/mark_attendance.php?date=' . date('Y-m-d'))) ?>" class="text-gray-600 hover:underline">Mark Attendance</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- pagination -->
    <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
      <div>Showing <?= ($offset+1) ?> – <?= min($offset + $perPage, $total) ?> of <?= $total ?></div>
      <div class="space-x-2">
        <?php if ($page > 1): ?>
          <a class="px-3 py-1 border rounded" href="<?= e(url('warden/students.php') . '?q=' . urlencode($search) . '&page=' . ($page-1)) ?>">Prev</a>
        <?php endif; ?>
        <?php if ($page < $pages): ?>
          <a class="px-3 py-1 border rounded" href="<?= e(url('warden/students.php') . '?q=' . urlencode($search) . '&page=' . ($page+1)) ?>">Next</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>
<?php include_once __DIR__ . '/../includes/ui/footer.php'; ?>
</body>
</html>