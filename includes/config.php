 <?php
session_start();

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = ''; // XAMPP default
$DB_NAME = 'hostel_db';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

function is_logged_in() {
    return isset($_SESSION['user']);
}

function require_role($role) {
    if (!is_logged_in() || $_SESSION['user']['role'] !== $role) {
        header('Location: /login.php');
        exit;
    }
}

function require_any_role($roles = []) {
    if (!is_logged_in() || !in_array($_SESSION['user']['role'], $roles)) {
        header('Location: /login.php');
        exit;
    }
}
