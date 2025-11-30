<?php
// admin/reset_password.php
require_once __DIR__ . '/../includes/config.php';
require_role('admin');

ini_set('display_errors', 1);
error_reporting(E_ALL);

$msg = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $user_email = trim($_POST['email'] ?? '');
    if ($user_email === '') $errors[] = 'Email is required.';
    else {
        // find user
        $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $user_email]);
        $u = $stmt->fetch();
        if (!$u) {
            $errors[] = 'User not found.';
        } else {
            // generate temp password
            $temp = bin2hex(random_bytes(5)); // 10 hex chars
            $hash = password_hash($temp, PASSWORD_DEFAULT);
            // update password and set force reset flag
            $up = $pdo->prepare("UPDATE users SET password = :pw, force_password_reset = 1 WHERE id = :id");
            $up->execute([':pw' => $hash, ':id' => $u['id']]);
            $msg = "Password reset for " . e($u['email']) . ". Temporary password (copy now): " . e($temp);
            // IMPORTANT: temp password shown once — admin must deliver it securely.
        }
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Admin - Reset Password</title></head><body>
  <h1>Admin: Reset User Password</h1>
  <?php if (!empty($errors)): foreach($errors as $er) echo "<div style='color:red;'>".e($er)."</div>"; endforeach; ?>
  <?php if ($msg) echo "<div style='background:#e6ffed;color:#0a7a3a;padding:8px;border-radius:6px;'>". $msg ."</div>"; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Email of user to reset: <input name="email" type="email" required></label>
    <button type="submit">Reset Password</button>
  </form>
  <p><a href="<?= e(url('admin/dashboard.php')) ?>">Back to admin dashboard</a></p>
</body></html>
