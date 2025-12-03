<?php
// admin/dashboard.php
require_once __DIR__ . '/../includes/config.php';
require_role('admin');

// --- 1. Fetch Quick Stats ---
$totalStudents = 0;
$pendingLeaves = 0;
$recentStudents = [];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
    $totalStudents = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'");
    $pendingLeaves = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT name, email, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC LIMIT 5");
    $recentStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Fail silently
}

$pageTitle = "Admin Dashboard";
?>

<!doctype html>
<html class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - HostelMgmt</title>
</head>
<body class="h-full">

<?php include __DIR__ . '/../includes/ui/sidebar.php'; ?>

<div class="md:ml-64 flex flex-col min-h-screen transition-all duration-300">
    
    <?php include __DIR__ . '/../includes/ui/header.php'; ?>

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Admin Overview</h1>
            <p class="mt-1 text-sm text-gray-500">Manage your hostel, approve leaves, and handle student accounts.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Students</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $totalStudents ?></p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="p-3 bg-yellow-50 text-yellow-600 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Pending Leaves</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $pendingLeaves ?></p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">System Status</p>
                    <p class="text-lg font-bold text-green-600">Operational</p>
                </div>
            </div>
        </div>

        <h2 class="text-lg font-semibold text-gray-900 mb-4">Management Modules</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            
            <a href="<?= e(url('admin/manage_students.php')) ?>" class="group block bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-all hover:border-indigo-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600">Manage Students</h3>
                    <svg class="h-6 w-6 text-gray-400 group-hover:text-indigo-600 transform group-hover:translate-x-1 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </div>
                <p class="text-gray-500 text-sm mb-4">
                    Add new students, update profiles, and <strong>reset passwords</strong>.
                </p>
                <span class="text-xs font-semibold bg-indigo-50 text-indigo-700 px-2 py-1 rounded">Primary Action</span>
            </a>

            <a href="<?= e(url('admin/leaves_dashboard.php')) ?>" class="group block bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-all hover:border-indigo-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600">Leaves Dashboard</h3>
                    <svg class="h-6 w-6 text-gray-400 group-hover:text-indigo-600 transform group-hover:translate-x-1 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </div>
                <p class="text-gray-500 text-sm mb-4">
                    Review incoming leave requests, view history, and generate reports.
                </p>
                <?php if($pendingLeaves > 0): ?>
                    <span class="text-xs font-semibold bg-red-100 text-red-700 px-2 py-1 rounded"><?= $pendingLeaves ?> Pending</span>
                <?php else: ?>
                    <span class="text-xs font-semibold bg-gray-100 text-gray-600 px-2 py-1 rounded">All Caught Up</span>
                <?php endif; ?>
            </a>
            
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Newest Students</h3>
            </div>
            <ul class="divide-y divide-gray-200">
                <?php foreach ($recentStudents as $student): ?>
                <li class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs">
                            <?= strtoupper(substr($student['name'], 0, 1)) ?>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900"><?= e($student['name']) ?></p>
                            <p class="text-xs text-gray-500"><?= e($student['email']) ?></p>
                        </div>
                    </div>
                    <span class="text-xs text-gray-400">
                        Joined <?= date('M d', strtotime($student['created_at'])) ?>
                    </span>
                </li>
                <?php endforeach; ?>
                <?php if (empty($recentStudents)): ?>
                    <li class="px-6 py-4 text-sm text-gray-500 text-center">No students found.</li>
                <?php endif; ?>
            </ul>
        </div>

    </main>
</div>

</body>
</html>