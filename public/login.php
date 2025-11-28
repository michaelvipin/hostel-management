<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pw = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id,name,email,password,role FROM users WHERE email = :email");
    $stmt->execute(['email'=>$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($pw, $user['password'])) {
        // set session
        $_SESSION['user'] = [
            'id'=>$user['id'],
            'name'=>$user['name'],
            'email'=>$user['email'],
            'role'=>$user['role']
        ];
        // redirect by role
        if ($user['role'] === 'student') header('Location: student/dashboard.php');
        elseif ($user['role'] === 'warden') header('Location: warden/dashboard.php');
        else header('Location: admin/dashboard.php');
        exit;
    } else {
        $error = "Invalid credentials.";
    }
}
?>
<!-- HTML form below -->
<!doctype html>
<html><head><title>Login</title></head><body>
  <?php if(!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
  <form method="post" action="">
    <input name="email" type="email" required placeholder="Email">
    <input name="password" type="password" required placeholder="Password">
    <button type="submit">Login</button>
  </form>
</body></html>
