<?php
// public/change_password.php
require_once __DIR__ . '/../includes/config.php';
require_login();

// optional: show errors for dev
ini_set('display_errors', 1);
error_reporting(E_ALL);

$userId = (int)$_SESSION['user']['id'];

// fetch current user data (password hash, flag)
$stmt = $pdo->prepare("SELECT password, force_password_reset FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $userId]);
$u = $stmt->fetch();

if (!$u) {
    // user record missing for some reason — logout and redirect to login
    redirect('public/logout.php');
}

$forceReset = (int)($u['force_password_reset'] ?? 0);
$errors = [];
$success = '';

// handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Basic validations
    if (!$forceReset) {
        if (trim($current) === '') $errors[] = 'Current password is required.';
        else if (!password_verify($current, $u['password'])) {
            $errors[] = 'Current password is incorrect.';
        }
    }

    if (strlen($new) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    }
    // optional: enforce stronger rules (uppercase, number, symbol)
    // if (!preg_match('/[A-Z]/', $new)) $errors[] = 'Password must include an uppercase letter.';
    if ($new !== $confirm) {
        $errors[] = 'New password and confirmation do not match.';
    }

    if (empty($errors)) {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = :pw, force_password_reset = 0 WHERE id = :id");
        $update->execute([':pw' => $newHash, ':id' => $userId]);

        // regenerate session id for security
        session_regenerate_id(true);

        // optional: update session user info (no password stored here)
        $_SESSION['user']['updated_at'] = time();

        $success = 'Password changed successfully.';
        // redirect to dashboard after short message (or immediately)
        redirect($_SESSION['user']['role'] . '/dashboard.php');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Change Password</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f6f8fa;padding:24px}
    .card{max-width:480px;margin:36px auto;padding:18px;background:#fff;border-radius:8px;box-shadow:0 6px 20px rgba(10,20,30,.06)}
    input{width:100%;padding:10px;margin:8px 0;border:1px solid #ddd;border-radius:4px}
    button{padding:10px 14px;border:none;background:#0b5ed7;color:#fff;border-radius:5px;cursor:pointer}
    .error{background:#fff0f0;color:#a70000;padding:8px;border-radius:6px}
    .success{background:#e6ffed;color:#0a7a3a;padding:8px;border-radius:6px}
    label{font-weight:600}
    .hint{font-size:13px;color:#666}
  </style>
</head>
<body>
  <div class="card">
    <h2>Change Password</h2>

    <?php if (!empty($errors)): ?>
      <div class="error">
        <?php foreach ($errors as $err): ?>
          <div><?= e($err) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success"><?= e($success) ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <?= csrf_field() ?>

      <?php if ($forceReset): ?>
        <p class="hint">Administrator has requested you to change your password. Please choose a new password.</p>
      <?php else: ?>
        <label>Current password</label>
        <input type="password" name="current_password" autocomplete="current-password">
      <?php endif; ?>

      <label>New password</label>
      <input type="password" name="new_password" autocomplete="new-password" required>
      <div class="hint">Use at least 8 characters. Include letters and numbers for better security.</div>

      <label>Confirm new password</label>
      <input type="password" name="confirm_password" autocomplete="new-password" required>

      <div style="display:flex;gap:8px;margin-top:12px">
        <button type="submit">Change password</button>
        <a href="<?= e(url($_SESSION['user']['role'] . '/dashboard.php')) ?>" style="margin-left:auto;text-decoration:none;padding:10px 12px;border-radius:6px;background:#f1f3f5;color:#333">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>
