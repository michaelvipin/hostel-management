<?php
// includes/config.php
// Purpose: DB connection, session, basic helpers (role checks + CSRF)

// Start session (only once per request)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB credentials - change for production
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'hostel_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP default: empty

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        $options
    );
} catch (PDOException $ex) {
    // In dev: show error. In prod: log and show generic error.
    die("Database connection failed: " . $ex->getMessage());
}

// ---------- Authentication / role helpers ----------
function current_user() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function is_logged_in() {
    return !empty($_SESSION['user']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

function require_role($role) {
    if (!is_logged_in() || ($_SESSION['user']['role'] ?? '') !== $role) {
        header('Location: /login.php');
        exit;
    }
}

function require_any_role(array $roles) {
    if (!is_logged_in() || !in_array($_SESSION['user']['role'], $roles)) {
        header('Location: /login.php');
        exit;
    }
}

// ---------- Simple CSRF helpers ----------
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $t . '">';
}

function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            // simple failure handling
            http_response_code(400);
            die('Invalid CSRF token.');
        }
    }
}

// ---------- Small helper to escape output ----------
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}