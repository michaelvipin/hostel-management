<?php
// includes/config.php  (safe, minimal, memory-friendly)
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- DB config ----------
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'hostel_db');
define('DB_USER', 'root');
define('DB_PASS', '');

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
    // dev: show error
    die("DB connection failed: " . $ex->getMessage());
}

// ---------- BASE_URL detection (very simple and robust) ----------
$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot = realpath(__DIR__ . '/..'); // includes/ is inside project
$baseUrl = '';

if ($docRoot !== false && $projectRoot !== false) {
    // convert backslashes to slashes then remove docRoot part
    $docRoot = str_replace('\\', '/', $docRoot);
    $projectRoot = str_replace('\\', '/', $projectRoot);
    if (strpos($projectRoot, $docRoot) === 0) {
        $baseUrl = substr($projectRoot, strlen($docRoot));
        $baseUrl = rtrim(str_replace('\\', '/', $baseUrl), '/');
    }
}
define('BASE_URL', $baseUrl === '' ? '' : $baseUrl); // e.g. "/hostel-management" or ""

// ---------- helpers ----------
function url($path = '') {
    $path = ($path === '' ? '' : '/' . ltrim($path, '/'));
    return (BASE_URL === '' ? '' : BASE_URL) . $path;
}
function redirect($path = '') {
    header('Location: ' . url($path));
    exit;
}

// ---------- auth helpers ----------
function current_user() {
    return $_SESSION['user'] ?? null;
}
function is_logged_in() {
    return !empty($_SESSION['user']);
}
function require_login() {
    if (!is_logged_in()) {
        redirect('public/login.php');
    }
}
function require_role($role) {
    if (!is_logged_in()) {
        redirect('public/login.php');
    }
    if (($_SESSION['user']['role'] ?? '') !== $role) {
        http_response_code(403);
        echo '<h1>403 — Access denied</h1>';
        echo '<p>You are logged in as <strong>' . htmlspecialchars($_SESSION['user']['role'] ?? '', ENT_QUOTES, 'UTF-8') . '</strong>.</p>';
        echo '<p><a href="' . htmlspecialchars(url(($_SESSION['user']['role'] ?? '') . '/dashboard.php'), ENT_QUOTES, 'UTF-8') . '">Go to your dashboard</a> or <a href="' . htmlspecialchars(url('public/logout.php'), ENT_QUOTES, 'UTF-8') . '">Logout</a></p>';
        exit;
    }
}
function require_any_role(array $roles) {
    if (!is_logged_in()) redirect('public/login.php');
    if (!in_array($_SESSION['user']['role'] ?? '', $roles)) {
        http_response_code(403);
        echo '<h1>403 — Access denied</h1>';
        exit;
    }
}

// ---------- session setter ----------
function set_user_session(array $user) {
    $_SESSION['user'] = [
        'id'    => (int)($user['id'] ?? 0),
        'name'  => $user['name'] ?? '',
        'email' => $user['email'] ?? '',
        'role'  => $user['role'] ?? 'student'
    ];
    session_regenerate_id(true);
}

// ---------- CSRF helpers ----------
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}
function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(400);
            die('Invalid CSRF token.');
        }
    }
}

// ---------- HTML escape ----------
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
