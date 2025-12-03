<?php
// warden/approve_leave.php
require_once __DIR__ . '/../includes/config.php';
require_role('warden');

$errors = [];
$msg = '';

// Handle action (approve/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    verify_csrf();
    $id = (int)$_POST['id'];
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $comment = trim($_POST['comment'] ?? '');
    $warden_id = (int)$_SESSION['user']['id'];

    // Update DB
    $up = $pdo->prepare("UPDATE leaves SET status = :status, reviewed_by = :rb, review_comment = :rc, reviewed_at = NOW(), updated_at = NOW() WHERE id = :id");
    $up->execute([
        ':status' => $action,
        ':rb' => $warden_id,
        ':rc' => $comment,
        ':id' => $id
    ]);
    
    // Success Message
    $msg = "Leave request #$id has been " . ($action === 'approved' ? 'approved' : 'rejected') . '.';
}

// Fetch pending
// Note: Using 'student_id' based on your provided code snippet. 
// If your DB uses 'user_id', please change 'l.student_id' to 'l.user_id' below.
$pendingStmt = $pdo->query("SELECT l.*, u.name as student_name, u.roll_no FROM leaves l JOIN users u ON l.student_id = u.id WHERE l.status = 'pending' ORDER BY l.created_at ASC");
$pending = $pendingStmt->fetchAll();

// Fetch recent history
$recentStmt = $pdo->query("SELECT l.*, u.name as student_name, u.roll_no, r.name as reviewer_name FROM leaves l JOIN users u ON l.student_id = u.id LEFT JOIN users r ON l.reviewed_by = r.id ORDER BY l.created_at DESC LIMIT 50");
$recent = $recentStmt->fetchAll();
?>
<!doctype html>
<html class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warden — Approve Leaves</title>
</head>
<body class="h-full">

<?php include_once __DIR__ . '/../includes/ui/sidebar.php'; ?>

<div class="md:ml-64 flex flex-col min-h-screen transition-all duration-300">
    
    <?php include_once __DIR__ . '/../includes/ui/header.php'; ?>

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Leave Management</h1>
            <p class="mt-1 text-sm text-gray-500">Review pending requests and view history.</p>
        </div>

        <?php if ($msg): ?>
            <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200 shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800"><?= e($msg) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-indigo-50/50 flex justify-between items-center">
                <h2 class="text-lg font-medium text-indigo-900">Pending Requests</h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                    <?= count($pending) ?> Waiting
                </span>
            </div>

            <?php if (empty($pending)): ?>
                <div class="p-8 text-center text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <p>All caught up! No pending leave requests.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Review Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($pending as $p): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= e($p['student_name']) ?></div>
                                    <div class="text-xs text-gray-500">Roll: <?= e($p['roll_no']) ?></div>
                                    <div class="text-xs text-gray-400 mt-1">Applied: <?= date('M d', strtotime($p['created_at'])) ?></div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <div class="font-medium">From: <?= e($p['start_date']) ?></div>
                                    <div class="mt-1">To: &nbsp;&nbsp;&nbsp;&nbsp;<?= e($p['end_date']) ?></div>
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs break-words">
                                    <?= e($p['reason']) ?>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <form method="post" class="flex flex-col gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                        
                                        <input type="text" name="comment" placeholder="Add a comment (optional)" 
                                               class="text-xs w-full border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                        
                                        <div class="flex gap-2 mt-1">
                                            <button type="submit" name="action" value="approve" 
                                                    class="flex-1 inline-flex justify-center items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Approve
                                            </button>
                                            
                                            <button type="submit" name="action" value="reject" 
                                                    class="flex-1 inline-flex justify-center items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                Reject
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Recent History (Last 50)</h3>
            </div>
            
            <?php if (empty($recent)): ?>
                <div class="p-6 text-center text-sm text-gray-500">No history available.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reviewer</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recent as $r): ?>
                                <?php 
                                    $st = strtolower($r['status']);
                                    $badgeClass = match($st) {
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        default    => 'bg-yellow-100 text-yellow-800',
                                    };
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= e($r['student_name']) ?> <span class="text-gray-400 text-xs">(<?= e($r['roll_no']) ?>)</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= e($r['start_date']) ?> <span class="text-xs">to</span> <?= e($r['end_date']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="<?= e($r['reason']) ?>">
                                        <?= e($r['reason']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $badgeClass ?>">
                                            <?= ucfirst($st) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($r['reviewer_name']): ?>
                                            <div><?= e($r['reviewer_name']) ?></div>
                                            <div class="text-xs text-gray-400"><?= date('M d', strtotime($r['reviewed_at'])) ?></div>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

</body>
</html>