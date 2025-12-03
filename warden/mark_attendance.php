<?php
// warden/mark_attendance.php
require_once __DIR__ . '/../includes/config.php';
require_role('warden');

$errors = [];
$msg = '';

// 1. Date Handling
$date = $_GET['date'] ?? ($_POST['date'] ?? date('Y-m-d'));
$dateObj = DateTime::createFromFormat('Y-m-d', $date);
$date = $dateObj ? $dateObj->format('Y-m-d') : date('Y-m-d');

// 2. Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $statuses = $_POST['status'] ?? [];
    
    if (empty($statuses)) {
        $errors[] = 'No data submitted.';
    } else {
        try {
            $pdo->beginTransaction();
            $warden_id = $_SESSION['user']['id'];
            
            $sql = "INSERT INTO attendance (student_id, date, status, marked_by, marked_at)
                    VALUES (:student_id, :date, :status, :marked_by, NOW())
                    ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by), marked_at = VALUES(marked_at)";
            $stmt = $pdo->prepare($sql);

            $count = 0;
            foreach ($statuses as $sid => $st) {
                $stmt->execute([
                    ':student_id' => $sid,
                    ':date'       => $date,
                    ':status'     => ($st === 'present' ? 'present' : 'absent'),
                    ':marked_by'  => $warden_id
                ]);
                $count++;
            }
            $pdo->commit();
            $msg = "Attendance saved successfully for $count students.";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

// 3. Fetch Data
$students = $pdo->query("SELECT id, name, roll_no FROM users WHERE role = 'student' ORDER BY roll_no ASC")->fetchAll();

$attendanceData = [];
if ($students) {
    $stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE date = :date");
    $stmt->execute([':date' => $date]);
    $attendanceData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [student_id => status]
}
?>
<!doctype html>
<html class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Warden</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        /* Custom Toggle Switch Logic */
        .toggle-radio:checked + .toggle-label {
            background-color: var(--active-bg);
            color: var(--active-text);
            border-color: var(--active-border);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .row-present { background-color: #ecfdf5; /* green-50 */ }
        .row-absent { background-color: #fef2f2; /* red-50 */ }
        .row-default { background-color: #ffffff; }
    </style>
</head>
<body class="h-full">

<?php include __DIR__ . '/../includes/ui/sidebar.php'; ?>

<div class="md:ml-64 flex flex-col min-h-screen transition-all duration-300">
    
    <?php include __DIR__ . '/../includes/ui/header.php'; ?>

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full pb-24 relative">
        
        <?php if($msg): ?>
            <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-700 border border-green-200 shadow-sm animate__animated animate__fadeInDown">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <?= e($msg) ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            
            <form id="dateForm" method="get" class="flex items-center gap-3 bg-white p-2 rounded-lg shadow-sm border border-gray-200">
                <div class="p-2 bg-indigo-50 rounded text-indigo-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 font-semibold uppercase tracking-wide">Attendance Date</label>
                    <input type="date" name="date" value="<?= e($date) ?>" 
                           class="border-none p-0 text-gray-900 font-bold focus:ring-0 text-sm cursor-pointer"
                           onchange="this.form.submit()">
                </div>
            </form>

            <div class="flex gap-4">
                <div class="bg-white px-4 py-2 rounded-lg shadow-sm border border-gray-200 text-center min-w-[80px]">
                    <span class="block text-xs text-gray-400 font-bold uppercase">Total</span>
                    <span class="text-xl font-bold text-gray-700" id="stat-total"><?= count($students) ?></span>
                </div>
                <div class="bg-green-50 px-4 py-2 rounded-lg shadow-sm border border-green-100 text-center min-w-[80px]">
                    <span class="block text-xs text-green-600 font-bold uppercase">Present</span>
                    <span class="text-xl font-bold text-green-700" id="stat-present">0</span>
                </div>
                <div class="bg-red-50 px-4 py-2 rounded-lg shadow-sm border border-red-100 text-center min-w-[80px]">
                    <span class="block text-xs text-red-600 font-bold uppercase">Absent</span>
                    <span class="text-xl font-bold text-red-700" id="stat-absent">0</span>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6 sticky top-20 z-10 bg-gray-50/95 backdrop-blur py-2">
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" id="searchInput" placeholder="Search student name or roll..." 
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-shadow">
            </div>

            <div class="flex gap-2 w-full sm:w-auto">
                <button type="button" id="btnMarkAllPresent" class="flex-1 sm:flex-none px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-green-50 hover:text-green-700 hover:border-green-200 transition-colors">
                    Mark All Present
                </button>
                <button type="button" id="btnMarkAllAbsent" class="flex-1 sm:flex-none px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-red-50 hover:text-red-700 hover:border-red-200 transition-colors">
                    Mark All Absent
                </button>
            </div>
        </div>

        <form method="post" id="attendanceForm">
            <?= csrf_field() ?>
            <input type="hidden" name="date" value="<?= e($date) ?>">

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <ul class="divide-y divide-gray-100" id="studentList">
                    <?php if (empty($students)): ?>
                        <li class="p-8 text-center text-gray-500">No students found in the database.</li>
                    <?php else: foreach ($students as $s): 
                        $sid = $s['id'];
                        // Default logic: If existing data, use it. Else default to Present.
                        $status = $attendanceData[$sid] ?? 'present'; 
                    ?>
                        <li class="student-row transition-colors duration-200 group hover:bg-gray-50" id="row-<?= $sid ?>" data-status="<?= $status ?>">
                            <div class="p-4 sm:flex sm:items-center sm:justify-between gap-4">
                                
                                <div class="flex items-center gap-4 mb-3 sm:mb-0 w-full sm:w-auto">
                                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm shrink-0">
                                        <?= strtoupper(substr($s['name'], 0, 1)) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-gray-900 student-name"><?= e($s['name']) ?></p>
                                        <p class="text-xs text-gray-500 student-roll">Roll: <?= e($s['roll_no']) ?></p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end sm:justify-start w-full sm:w-auto">
                                    <div class="bg-gray-100 p-1 rounded-lg flex shadow-inner">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="status[<?= $sid ?>]" value="present" 
                                                   class="sr-only toggle-radio attendance-radio" 
                                                   data-sid="<?= $sid ?>"
                                                   <?= $status === 'present' ? 'checked' : '' ?>>
                                            <span class="toggle-label block px-4 py-1.5 rounded-md text-sm font-medium text-gray-500 transition-all duration-200 hover:text-gray-700"
                                                  style="--active-bg: #ffffff; --active-text: #059669; --active-border: transparent;">
                                                Present
                                            </span>
                                        </label>

                                        <label class="cursor-pointer ml-1">
                                            <input type="radio" name="status[<?= $sid ?>]" value="absent" 
                                                   class="sr-only toggle-radio attendance-radio" 
                                                   data-sid="<?= $sid ?>"
                                                   <?= $status === 'absent' ? 'checked' : '' ?>>
                                            <span class="toggle-label block px-4 py-1.5 rounded-md text-sm font-medium text-gray-500 transition-all duration-200 hover:text-gray-700"
                                                  style="--active-bg: #ffffff; --active-text: #dc2626; --active-border: transparent;">
                                                Absent
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>

            <div class="fixed bottom-0 right-0 left-0 md:ml-64 bg-white border-t border-gray-200 p-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-20 flex justify-between items-center">
                <div class="text-sm text-gray-500 hidden sm:block">
                    Review selections before saving.
                </div>
                <button type="submit" class="w-full sm:w-auto px-8 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    Save Attendance
                </button>
            </div>

        </form>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const radios = document.querySelectorAll('.attendance-radio');
    const rows = document.querySelectorAll('.student-row');
    const statPresent = document.getElementById('stat-present');
    const statAbsent = document.getElementById('stat-absent');
    const searchInput = document.getElementById('searchInput');

    // 1. Function to update row colors and stats
    function updateUI() {
        let pCount = 0;
        let aCount = 0;

        rows.forEach(row => {
            const sid = row.id.replace('row-', '');
            // Find checked radio in this row
            const checked = row.querySelector(`input[name="status[${sid}]"]:checked`);
            if (checked) {
                const val = checked.value;
                
                // Update Row Color
                row.classList.remove('row-present', 'row-absent', 'row-default');
                if (val === 'present') {
                    row.classList.add('row-present');
                    pCount++;
                } else {
                    row.classList.add('row-absent');
                    aCount++;
                }
            }
        });

        // Update Top Stats
        statPresent.textContent = pCount;
        statAbsent.textContent = aCount;
    }

    // 2. Event Listeners for individual toggles
    radios.forEach(radio => {
        radio.addEventListener('change', updateUI);
    });

    // 3. Bulk Actions
    document.getElementById('btnMarkAllPresent').addEventListener('click', () => {
        document.querySelectorAll('input[value="present"]').forEach(el => {
            if(!el.closest('li').classList.contains('hidden')) { // Only affect visible rows
                el.checked = true; 
            }
        });
        updateUI();
    });

    document.getElementById('btnMarkAllAbsent').addEventListener('click', () => {
        document.querySelectorAll('input[value="absent"]').forEach(el => {
            if(!el.closest('li').classList.contains('hidden')) {
                el.checked = true;
            }
        });
        updateUI();
    });

    // 4. Smart Search
    searchInput.addEventListener('keyup', function(e) {
        const term = e.target.value.toLowerCase();
        rows.forEach(row => {
            const name = row.querySelector('.student-name').textContent.toLowerCase();
            const roll = row.querySelector('.student-roll').textContent.toLowerCase();
            
            if (name.includes(term) || roll.includes(term)) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
    });

    // Initial Run
    updateUI();
});
</script>

</body>
</html>