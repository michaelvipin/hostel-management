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

// Total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// Fetch page data
$stmt = $pdo->prepare("SELECT id, name, email, roll_no, room_no, phone FROM users $where ORDER BY roll_no ASC, name ASC LIMIT :lim OFFSET :off");
// Bind params manually for Limit/Offset integrity
if (isset($params[':q'])) {
    $stmt->bindValue(':q', $params[':q'], PDO::PARAM_STR);
}
$stmt->bindValue(':lim', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$students = $stmt->fetchAll();
?>
<!doctype html>
<html class="h-full bg-gray-50">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Warden — Students List</title>
</head>
<body class="h-full">

<!-- 1. Sidebar First -->
<?php include_once __DIR__ . '/../includes/ui/sidebar.php'; ?>

<!-- 2. Main Wrapper: Pushes Header & Content to the right -->
<div class="md:ml-64 flex flex-col min-h-screen transition-all duration-300">
    
    <!-- Header Inside Wrapper -->
    <?php include_once __DIR__ . '/../includes/ui/header.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        
        <!-- Page Header & Search -->
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
             <div>
                <h1 class="text-2xl font-bold text-gray-900">Student Directory</h1>
                <p class="mt-1 text-sm text-gray-500">Manage student details and track room assignments.</p>
             </div>
             
             <!-- Search Form -->
             <form method="get" class="mt-4 sm:mt-0 flex gap-2">
                <div class="relative rounded-md shadow-sm">
                    <input type="search" name="q" value="<?= e($search) ?>" 
                           placeholder="Search name, roll, room..." 
                           class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-3 pr-10 sm:text-sm border-gray-300 rounded-md py-2 border">
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Search
                </button>
             </form>
        </div>

        <!-- Students Table Card -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roll No</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($students)): ?>
                  <tr>
                      <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                        No students found matching your search.
                      </td>
                  </tr>
                <?php else: ?>
                    <?php foreach ($students as $i => $s): ?>
                      <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= e($offset + $i + 1) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?= e($s['roll_no'] ?? 'N/A') ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs mr-3">
                                    <?= strtoupper(substr($s['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?= e($s['name']) ?></div>
                                    <div class="text-xs text-gray-500"><?= e($s['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <?= e($s['room_no'] ?? 'Unassigned') ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= e($s['phone'] ?? '—') ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                          <a href="<?= e(url('warden/student_profile.php?id=' . $s['id'])) ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Profile</a>
                          <!-- Optional Action -->
                          <!-- <a href="#" class="text-gray-400 hover:text-gray-600">History</a> -->
                        </td>
                      </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <?php if ($total > 0): ?>
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6 flex items-center justify-between">
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?= ($offset + 1) ?></span> to <span class="font-medium"><?= min($offset + $perPage, $total) ?></span> of <span class="font-medium"><?= $total ?></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                                <a href="<?= e(url('warden/students.php') . '?q=' . urlencode($search) . '&page=' . ($page-1)) ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            <?php endif; ?>
                            
                            <!-- Current Page Indicator -->
                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                Page <?= $page ?> of <?= $pages ?>
                            </span>

                            <?php if ($page < $pages): ?>
                                <a href="<?= e(url('warden/students.php') . '?q=' . urlencode($search) . '&page=' . ($page+1)) ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </main>
</div>

</body>
</html>