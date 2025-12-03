<?php
// admin/leaves_dashboard.php
require_once __DIR__ . '/../includes/config.php';
require_role('admin');

// 1. Fetch Counts
try {
    $counts = $pdo->query("SELECT status, COUNT(*) AS cnt FROM leaves GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $counts = [];
}

// 2. Fetch Recent Leaves
$recent = [];
try {
    $recent = $pdo->query("
        SELECT l.*, s.name AS student_name, s.email, r.name AS reviewer_name
        FROM leaves l
        JOIN users s ON l.user_id = s.id
        LEFT JOIN users r ON l.reviewed_by = r.id
        ORDER BY l.created_at DESC 
        LIMIT 100
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent = [];
}

$pageTitle = "Leaves Dashboard";
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
        
        <div class="sm:flex sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Leaves Overview</h1>
                <p class="mt-1 text-sm text-gray-500">Monitor, approve, and reject student leave applications.</p>
            </div>
            <div class="mt-4 sm:mt-0">
                </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white overflow-hidden rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 truncate">Pending Requests</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900"><?= (int)($counts['pending'] ?? 0) ?></p>
                </div>
                <div class="p-3 bg-yellow-50 text-yellow-600 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <div class="bg-white overflow-hidden rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 truncate">Approved</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900"><?= (int)($counts['approved'] ?? 0) ?></p>
                </div>
                <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <div class="bg-white overflow-hidden rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 truncate">Rejected</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900"><?= (int)($counts['rejected'] ?? 0) ?></p>
                </div>
                <div class="p-3 bg-red-50 text-red-600 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-medium text-gray-900">Recent Applications</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reviewed By</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($recent) > 0): ?>
                            <?php foreach ($recent as $r): ?>
                                <?php 
                                    $st = strtolower($r['status']);
                                    $badgeClass = match($st) {
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        default    => 'bg-yellow-100 text-yellow-800'
                                    };
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-gray-900"><?= e($r['student_name']) ?></span>
                                            <span class="text-xs text-gray-500"><?= e($r['email'] ?? 'No Email') ?></span>
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex flex-col">
                                            <span>From: <?= date('M d', strtotime($r['from_date'] ?? $r['start_date'])) ?></span>
                                            <span>To: &nbsp;&nbsp;&nbsp;&nbsp;<?= date('M d', strtotime($r['to_date'] ?? $r['end_date'])) ?></span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate" title="<?= e($r['reason']) ?>">
                                            <?= e($r['reason']) ?>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $badgeClass ?>">
                                            <?= ucfirst($st) ?>
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if (!empty($r['reviewer_name'])): ?>
                                            <span class="font-medium text-gray-700"><?= e($r['reviewer_name']) ?></span>
                                            <div class="text-xs text-gray-400"><?= date('M d H:i', strtotime($r['updated_at'] ?? 'now')) ?></div>
                                        <?php else: ?>
                                            <span class="text-gray-400">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="#" class="text-indigo-600 hover:text-indigo-900">Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                    No leave records found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex justify-between items-center sm:justify-end">
                    <span class="text-sm text-gray-700 mr-4">Showing <strong><?= count($recent) ?></strong> results</span>
                    <div class="relative z-0 inline-flex shadow-sm rounded-md -space-x-px" aria-label="Pagination">
                         <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                             <span>&larr; Prev</span>
                         </a>
                         <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                             <span>Next &rarr;</span>
                         </a>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

</body>
</html>