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
    // GET logic remains same
    dbg("GET - serving login form. Session user: " . print_r($_SESSION['user'] ?? null, true));
}
?>
<!doctype html>
<html class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <title>Login - HostelMgmt</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">

    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="flex justify-center">
            <div class="bg-indigo-600 text-white p-2 rounded-lg shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Sign in to your account
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Or <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">contact admin</a> if you forgot access.
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow-lg sm:rounded-lg sm:px-10 border border-gray-100">

            <?php if (!empty($errors)): ?>
                <div class="rounded-md bg-red-50 p-4 mb-6 border-l-4 border-red-500">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Login Failed</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <?php foreach($errors as $er) echo htmlspecialchars($er) . "<br>"; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form class="space-y-6" action="" method="POST">
                <?= csrf_field() ?>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700"> Email address </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required 
                               value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>"
                               class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md py-2 border">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700"> Password </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="current-password" required 
                               class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md py-2 border">
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        Sign in
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500"> Developer Tools </span>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-3 text-center">
                   <a href="<?= e(url('public/logout.php')) ?>" class="text-xs font-medium text-gray-500 hover:text-red-600 transition-colors">
                       Force Clear Session (Logout)
                   </a>
                   <span class="text-[10px] text-gray-400 font-mono">Debug: tmp_debug_login.txt</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>