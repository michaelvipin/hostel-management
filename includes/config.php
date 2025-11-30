<?php
// includes/config.php
// Permanent, robust base URL + helpers + DB connection

// start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- DB config (change for prod) ----------
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
    // in dev show message; in prod log & show generic error.
    die("DB connection failed: " . $ex->getMessage());
}

// ---------- BASE_URL auto-detection (robust) ----------
// Goal: compute the URL path to the project root, e.g. "/hostel-management" or "" if in webroot.

// SCRIPT_NAME example: /hostel-management/public/login.php
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/';
$scriptDir  = rtrim(dirname($scriptName), '/\\'); // e.g. /hostel-management/public

// If your public files are inside a folder named "public", treat project root as parent of public
if (preg_match('#/public$#', $scriptDir)) {
    $projectRoot = dirname($scriptDir); // remove /public
} else {
    // fallback: assume project root is the parent directory of current script directory
    // This handles cases like direct access or virtual hosts.
    $projectRoot = dirname($scriptDir);
}

// Normalize: if projectRoot ends up as '/' or '.' -> set to empty string
$projectRoot = $projectRoot === '/' || $projectRoot === '.' ? '' : rtrim($projectRoot, '/\\');
define('BASE_URL', $projectRoot); // Example: '/hostel-management' or ''

// ---------- URL helpers ----------
/**
 * base_url() - returns BASE_URL (string)
 */
function base_url() {
    return BASE_URL;
}

/**
 * url($path) - build a URL relative to project root.
 * Examples:
 *   url('public/login.php') -> '/hostel-management/public/login.php'
 *   url('/admin/dashboard.php') -> '/hostel-management/admin/dashboard.php'
 */
function url($path = '') {
    $base = BASE_URL;
    // clean path
    $path = (string)$path;
    if ($path === '') return $base ?: '/';
    // allow both relative and absolute incoming paths
    $path = '/' . ltrim($path, '/');
    return ($base === '' ? '' : $base) . $path;
}

/**
 * redirect($path) - send Location header and exit
 * Accepts same $path as url().
 */
function redirect($path = '') {
    // no output must be sent before calling this
    header('Location: ' . url($path));
    exit;
}

// ---------- Authentication / role helpers ----------
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
        // prefer clear 403 with options rather than bouncing to login
        http_response_code(403);
        echo '<!doctype html><html><head><meta charset="utf-8"><title>403 - Access denied</title></head><body>';
        echo '<h1>403 — Access denied</h1>';
        echo '<p>You are logged in as <strong>' . e($_SESSION['user']['role']) . '</strong> and do not have permission to view this page.</p>';
        echo '<p><a href="' . e(url($_SESSION['user']['role'] . '/dashboard.php')) . '">Go to your dashboard</a> or <a href="' . e(url('public/logout.php')) . '">Logout</a></p>';
        echo '</body></html>';
        exit;
    }
}
function require_any_role(array $roles) {
    if (!is_logged_in()) {
        redirect('public/login.php');
    }
    if (!in_array($_SESSION['user']['role'], $roles)) {
        http_response_code(403);
        echo '<!doctype html><html><head><meta charset="utf-8"><title>403 - Access denied</title></head><body>';
        echo '<h1>403 — Access denied</h1>';
        echo '<p>You are logged in as <strong>' . e($_SESSION['user']['role']) . '</strong> and do not have permission to view this page.</p>';
        echo '<p><a href="' . e(url($_SESSION['user']['role'] . '/dashboard.php')) . '">Go to your dashboard</a> or <a href="' . e(url('public/logout.php')) . '">Logout</a></p>';
        echo '</body></html>';
        exit;
    }
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

// ---------- Output escape ----------
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
