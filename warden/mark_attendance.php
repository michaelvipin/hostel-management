<?php
require_once '../../includes/config.php';
require_role('warden');

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $statuses = $_POST['status']; // array: student_id => 'present'/'absent'
    $warden_id = $_SESSION['user']['id'];

    $stmt = $pdo->prepare("INSERT INTO attendance (student_id,date,status,marked_by) VALUES (:sid,:date,:status,:marked_by)
        ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by), marked_at = CURRENT_TIMESTAMP");

    foreach ($statuses as $sid => $status) {
        $stmt->execute([':sid'=>$sid, ':date'=>$date, ':status'=>$status, ':marked_by'=>$warden_id]);
    }
    $msg = "Attendance saved.";
}

// fetch students
$students = $pdo->query("SELECT id,name,roll_no FROM users WHERE role='student' ORDER BY roll_no")->fetchAll();
?>
<!-- render form showing each student with radio or checkbox to mark present/absent -->
<?php
require_once '../../includes/config.php';
require_role('warden');

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $statuses = $_POST['status']; // array: student_id => 'present'/'absent'
    $warden_id = $_SESSION['user']['id'];

    $stmt = $pdo->prepare("INSERT INTO attendance (student_id,date,status,marked_by) VALUES (:sid,:date,:status,:marked_by)
        ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by), marked_at = CURRENT_TIMESTAMP");

    foreach ($statuses as $sid => $status) {
        $stmt->execute([':sid'=>$sid, ':date'=>$date, ':status'=>$status, ':marked_by'=>$warden_id]);
    }
    $msg = "Attendance saved.";
}

// fetch students
$students = $pdo->query("SELECT id,name,roll_no FROM users WHERE role='student' ORDER BY roll_no")->fetchAll();
?>
<!-- render form showing each student with radio or checkbox to mark present/absent -->
