<?php
// public/login.php
require_once __DIR__ . '/../includes/config.php';

$errors = [];

// If already logged in — redirect by role
if (is_logged_in()) {
    $role = $_SESSION['user']['role'];
    if ($role === 'admin') header('Location: /admin/dashboard.php');
    if ($role === 'warden') header('Location: /warden/dashboard.php');
    if ($role === 'student') header('Location: /student/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
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
            // successful login - store minimal info in session
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ];

            // regenerate CSRF token after login
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));

            // redirect to role dashboard
            if ($user['role'] === 'admin') {
                header('Location: /admin/dashboard.php');
            } elseif ($user['role'] === 'warden') {
                header('Location: /warden/dashboard.php');
            } else {
                header('Location: /student/dashboard.php');
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
    /* tiny inline styles for quick dev */
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
        <a href="/" style="margin-left:auto;color:#666;text-decoration:none">Home</a>
      </div>
    </form>
    <p style="color:#666;font-size:13px;margin-top:12px">Use the seed script to create initial users (scripts/seed.php).</p>
  </div>
</body>
</html>
