<?php
// scripts/seed.php
require_once __DIR__ . '/../includes/config.php';

// WARNING: Run only once on dev. This creates initial accounts with known passwords.
// Change passwords after first login in production.

$users = [
    ['name'=>'Super Admin', 'email'=>'admin@hostel.local', 'password'=>'Admin@123', 'role'=>'admin', 'roll_no'=>null],
    ['name'=>'Warden One',   'email'=>'warden1@hostel.local', 'password'=>'Warden@123', 'role'=>'warden', 'roll_no'=>null],
    ['name'=>'Student A',    'email'=>'studentA@hostel.local', 'password'=>'Student@123', 'role'=>'student', 'roll_no'=>'S101'],
    ['name'=>'Student B',    'email'=>'studentB@hostel.local', 'password'=>'Student@123', 'role'=>'student', 'roll_no'=>'S102'],
];

$inserted = 0;
foreach ($users as $u) {
    // skip if exists
    $check = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $check->execute(['email' => $u['email']]);
    if ($check->fetch()) continue;

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, roll_no) VALUES (:name, :email, :pwd, :role, :roll)");
    $stmt->execute([
        'name' => $u['name'],
        'email' => $u['email'],
        'pwd' => password_hash($u['password'], PASSWORD_DEFAULT),
        'role' => $u['role'],
        'roll' => $u['roll_no']
    ]);
    $inserted++;
}

echo "Seed complete. Inserted: {$inserted} user(s).<br>";
echo "Admin: admin@hostel.local / Admin@123<br>";
echo "Warden: warden1@hostel.local / Warden@123<br>";
echo "Students: studentA@hostel.local, studentB@hostel.local (password: Student@123)<br>";
echo "<a href='/login.php'>Go to login</a>";