require_role('warden');
// Approve/reject:
if($_SERVER['POST']) {
  $id = intval($_POST['leave_id']);
  $action = $_POST['action']; // approved or rejected
  $stmt = $pdo->prepare("UPDATE leaves SET status=:status, decided_by=:db, decided_at=NOW() WHERE id=:id");
  $stmt->execute(['status'=>$action,'db'=>$_SESSION['user']['id'],'id'=>$id]);
}
$pending = $pdo->query("SELECT l.*, u.name, u.roll_no FROM leaves l JOIN users u ON l.student_id=u.id WHERE l.status='pending'")->fetchAll();