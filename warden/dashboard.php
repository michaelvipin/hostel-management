<?php
// warden/dashboard.php
require_once __DIR__ . '/../includes/config.php';
require_role('warden');

// --- Fetch Warden Stats ---
$pendingLeaves = 0;
$totalStudents = 0;
$todayAttendance = 0;
$pendingRequests = [];

try {
    // 1. Count Pending Leaves
    $stmt = $pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'");
    $pendingLeaves = $stmt->fetchColumn();

    // 2. Count Total Students (assigned to hostel)
    // Assuming all 'student' role users are relevant. 
    // If you have specific hostel assignment logic, add WHERE hostel_id = ...
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
    $totalStudents = $stmt->fetchColumn();

    // 3. Fetch 5 Pending Leave Requests for "Quick Action" table
    $stmt = $pdo->query("
        SELECT l.*, u.name as student_name, u.roll_no 
        FROM leaves l 
        JOIN users u ON l.user_id = u.id 
        WHERE l.status = 'pending' 
        ORDER BY l.created_at ASC 
        LIMIT 5
    ");
    $pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Fail silently for UI demo
}

$pageTitle = "Warden Dashboard";
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
            <h1 class="text-2xl font-bold text-gray-900">Warden Control Panel</h1>
            <p class="mt-1 text-sm text-gray-500">Overview of student attendance and leave requests.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Pending Approvals</p>
                    <p class="text-2xl font-bold text-indigo-600"><?= $pendingLeaves ?></p>
                </div>
                <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000  /svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Students</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $totalStudents ?></p>
                </div>
                <div class="p-3 bg-gray-50 text-gray-600 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Today's Date</p>
                    <p class="text-lg font-bold text-gray-900"><?= date('M d, Y') ?></p>
                </div>
                <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>

        </div>

        <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            
            <a href="<?= e(url('warden/approve_leave.php')) ?>" class="group block bg-white p-6 rounded-xl border border-gray-200 hover:shadow-md hover:border-indigo-300 transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-yellow-50 text-yellow-600 rounded-lg group-hover:bg-yellow-100 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-900">Approve Leaves</h3>
                </div>
                <p class="text-sm text-gray-500">Review and action pending leave requests from students.</p>
                <?php if ($pendingLeaves > 0): ?>
                    <div class="mt-4 inline-block px-2 py-1 text-xs font-semibold bg-red-100 text-red-700 rounded animate-pulse">
                        <?= $pendingLeaves ?> Action Required
                    </div>
                <?php endif; ?>
            </a>

            <a href="<?= e(url('warden/mark_attendance.php')) ?>" class="group block bg-white p-6 rounded-xl border border-gray-200 hover:shadow-md hover:border-indigo-300 transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-lg group-hover:bg-blue-100 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-900">Mark Attendance</h3>
                </div>
                <p class="text-sm text-gray-500">Log daily attendance for all students in your block.</p>
            </a>

            <a href="<?= e(url('warden/students.php')) ?>" class="group block bg-white p-6 rounded-xl border border-gray-200 hover:shadow-md hover:border-indigo-300 transition-all">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-purple-50 text-purple-600 rounded-lg group-hover:bg-purple-100 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-900">Student Directory</h3>
                </div>
                <p class="text-sm text-gray-500">View student profiles, room details, and contact info.</p>
            </a>

        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Pending Leave Approvals</h3>
                <a href="<?= e(url('warden/approve_leave.php')) ?>" class="text-xs font-medium text-indigo-600 hover:text-indigo-900">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($pendingRequests) > 0): ?>
                            <?php foreach ($pendingRequests as $req): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= e($req['student_name']) ?></div>
                                    <div class="text-xs text-gray-500">Roll: <?= e($req['roll_no'] ?? 'N/A') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M d', strtotime($req['start_date'])) ?> - <?= date('M d', strtotime($req['end_date'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-xs truncate">
                                    <?= e($req['reason']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="<?= e(url('warden/approve_leave.php?id='.$req['id'])) ?>" class="text-indigo-600 hover:text-indigo-900">Review</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                                    No pending requests right now.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

</body>
</html>