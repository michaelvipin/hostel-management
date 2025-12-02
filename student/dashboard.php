<?php
// student/dashboard.php — Student Home with Stats & Quick Actions
require_once __DIR__ . '/../includes/config.php';
require_role('student');

// 1. Fetch recent leaves for the dashboard "Recent Activity" section
// (Assuming table 'leaves' exists with user_id, type, from_date, to_date, status)
$recentLeaves = [];
try {
    $stmt = $pdo->prepare("
        SELECT * FROM leaves 
        WHERE user_id = :uid 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([':uid' => $_SESSION['user']['id']]);
    $recentLeaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If table doesn't exist yet, just ignore to keep UI working
    $recentLeaves = []; 
}

// 2. Mock Attendance Stat (You can replace this with a real SQL COUNT query later)
$attendancePercent = 85; // Placeholder
$attendanceColor = $attendancePercent >= 75 ? 'text-green-600' : 'text-red-600';

// Page Title
$pageTitle = "Student Dashboard";
?>

<!doctype html>
<html class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - HostelMgmt</title>
</head>
<body class="h-full">

<?php include __DIR__ . '/../includes/ui/header.php'; ?>
<?php include __DIR__ . '/../includes/ui/sidebar.php'; ?>

<div class="md:ml-64 transition-all duration-300">
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Welcome back, <?= e($_SESSION['user']['name']) ?>! 👋</h1>
            <p class="mt-1 text-sm text-gray-500">Here is what’s happening with your hostel account today.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            
            <div class="bg-white overflow-hidden rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900">Attendance</h3>
                        </div>
                        <span class="text-xs font-medium bg-green-100 text-green-700 px-2 py-1 rounded-full">Good Standing</span>
                    </div>
                    
                    <div class="flex items-baseline gap-2 mb-4">
                        <span class="text-4xl font-bold <?= $attendanceColor ?>"><?= $attendancePercent ?>%</span>
                        <span class="text-sm text-gray-500">Average this semester</span>
                    </div>

                    <a href="<?= e(url('student/view_attendance.php')) ?>" class="block w-full text-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                        View Detailed History
                    </a>
                </div>
            </div>

            <div class="bg-white overflow-hidden rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900">Leave Requests</h3>
                        </div>
                        <span class="text-xs font-medium bg-gray-100 text-gray-600 px-2 py-1 rounded-full">Apply Anytime</span>
                    </div>
                    
                    <p class="text-sm text-gray-500 mb-6 h-10">
                        Need to go home or take sick leave? Submit a request to your warden instantly.
                    </p>

                    <div class="flex gap-3">
                        <a href="<?= e(url('student/apply_leave.php')) ?>" class="flex-1 flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                            Apply for Leave
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Recent Leave Requests</h3>
                <a href="<?= e(url('student/apply_leave.php')) ?>" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">View All &rarr;</a>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied On</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($recentLeaves) > 0): ?>
                            <?php foreach ($recentLeaves as $leave): ?>
                                <?php 
                                    // Status Badge Color Logic
                                    $statusColor = match($leave['status']) {
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        default    => 'bg-yellow-100 text-yellow-800',
                                    };
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= e(ucfirst($leave['leave_type'] ?? 'General')) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= e($leave['from_date']) ?> <span class="text-gray-400 mx-1">to</span> <?= e($leave['to_date']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusColor ?>">
                                            <?= e(ucfirst($leave['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M d, Y', strtotime($leave['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                                    No leave requests found. 
                                    <a href="<?= e(url('student/apply_leave.php')) ?>" class="text-indigo-600 hover:underline">Apply for one now?</a>
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