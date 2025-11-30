<?php
// public/login.php
// Enable error display temporarily for debugging (remove in prod)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// include config (use correct relative path)
require_once __DIR__ . '/../includes/config.php';

// If already logged in, send to role dashboard
if (is_logged_in()) {
    $role = $_SESSION['user']['role'];
    if ($role === 'admin') header('Location: ' . BASE_URL . '/admin/dashboard.php');
    if ($role === 'warden') header('Location: ' . BASE_URL . '/warden/dashboard.php');
    if ($role === 'student') header('Location: ' . BASE_URL . '/student/dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // verify CSRF token
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    } else {
        $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // store minimal session
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ];

            // regenerate CSRF token after login
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));

            // redirect to dashboard by role using BASE_URL
            if ($user['role'] === 'admin') {
                header('Location: ' . BASE_URL . '/admin/dashboard.php');
            } elseif ($user['role'] === 'warden') {
                header('Location: ' . BASE_URL . '/warden/dashboard.php');
            } else {
                header('Location: ' . BASE_URL . '/student/dashboard.php');
            }
            exit;
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login - Hostel Management</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f7f7f9;padding:24px}
    .card{max-width:420px;margin:48px auto;padding:20px;background:#fff;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,.06)}
    input{width:100%;padding:10px;margin:8px 0;border:1px solid #ddd;border-radius:4px}
    button{padding:10px 14px;border:none;background:#0b5ed7;color:#fff;border-radius:5px;cursor:pointer}
    .errors{color:#c00;margin-bottom:12px}
  </style>
</head>
<body>
  <div class="card">
    <h2 style="margin-top:0">Hostel Management — Login</h2>

    <?php if (!empty($errors)): ?>
      <div class="errors">
        <?php foreach ($errors as $err): ?>
          <div><?= e($err) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <?= csrf_field() ?>
      <label>
        Email
        <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
      </label>
      <label>
        Password
        <input type="password" name="password" required>
      </label>
      <div style="display:flex;gap:8px;align-items:center;margin-top:10px">
        <button type="submit">Login</button>
        <a href="<?= BASE_URL ?: '/' ?>" style="margin-left:auto;color:#666;text-decoration:none">Home</a>
      </div>
    </form>
    <p style="color:#666;font-size:13px;margin-top:12px">If you used the seed script, use the seeded credentials to test.</p>
  </div>
</body>
</html>
