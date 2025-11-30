<?php
// public/login.php — force-auth, clears old session, robust debug
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/config.php';

// debug file
$debugFile = __DIR__ . '/../tmp_debug_login.txt';

// safe session setter (ensure exists)
if (!function_exists('set_user_session')) {
    function set_user_session(array $user) {
        // fully replace user session explicitly
        $_SESSION['user'] = [
            'id'    => (int)($user['id'] ?? 0),
            'name'  => $user['name'] ?? '',
            'email' => $user['email'] ?? '',
            'role'  => $user['role'] ?? 'student'
        ];
        // regenerate id
        session_regenerate_id(true);
    }
}

// log helper
function dbg($msg) {
    global $debugFile;
    file_put_contents($debugFile, "[".date('c')."] " . $msg . PHP_EOL, FILE_APPEND);
}

// Log request and current session (before processing)
dbg("REQUEST " . ($_SERVER['REQUEST_METHOD'] ?? '') . " URL=" . ($_SERVER['REQUEST_URI'] ?? ''));
dbg("SESSION BEFORE: " . print_r($_SESSION['user'] ?? null, true));

// If POST -> attempt login (even if already logged in)
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    dbg("POST email: {$email}");

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
        dbg("ERROR: empty email/password");
    } else {
        // fetch user by email
        $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        dbg("DB user row: " . print_r($user, true));

        if ($user && password_verify($password, $user['password'])) {
            // Clear any old session user explicitly first
            unset($_SESSION['user']);
            dbg("Cleared previous session user.");

            // Set new session from DB row
            set_user_session($user);
            dbg("SESSION AFTER SET: " . print_r($_SESSION['user'], true));

            // clear CSRF token and redirect by DB role
            unset($_SESSION['csrf_token']);
            dbg("Redirecting to role dashboard: " . $user['role'] . '/dashboard.php');
            redirect($user['role'] . '/dashboard.php');
            exit;
        } else {
            $errors[] = 'Invalid email or password.';
            dbg("LOGIN FAILED for {$email}");
        }
    }
} else {
    // GET: if logged in, do NOT auto-redirect before letting user post — show login page
    // (we will still redirect normally to dashboard if they want)
    // But for safety, log current session
    dbg("GET - serving login form. Session user: " . print_r($_SESSION['user'] ?? null, true));
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Login</title></head>
<body>
  <h2>Login</h2>

  <?php if (!empty($errors)): ?>
    <div style="color:red">
      <?php foreach($errors as $er) echo htmlspecialchars($er) . "<br>"; ?>
    </div>
  <?php endif; ?>

  <form method="post" action="">
    <?= csrf_field() ?>
    <label>Email <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>"></label><br>
    <label>Password <input type="password" name="password" required></label><br>
    <button type="submit">Login</button>
  </form>

  <p><a href="<?= e(url('public/logout.php')) ?>">Logout (clear session)</a></p>
  <p style="font-size:12px;color:#666">Debug file: tmp_debug_login.txt (server-side)</p>
</body>
</html>
