<?php
require_once __DIR__ . '/../includes/config.php';
require_role('warden');
echo "WARDEN DASHBOARD - Logged in as " . e($_SESSION['user']['name']);
<a href="<?= e(url('warden/approve_leave.php')) ?>">Approve Leaves</a>