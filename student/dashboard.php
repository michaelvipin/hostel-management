<?php
require_once __DIR__ . '/../includes/config.php';
require_role('student');
echo "STUDENT DASHBOARD - Logged in as " . e($_SESSION['user']['name']);
<html>
<body>
<h1>Student Dashboard</h1>
<p>leave application</p>
<a href="<?= e(url('student/apply_leave.php')) ?>">Apply for Leave</a>
</body>
</html>
