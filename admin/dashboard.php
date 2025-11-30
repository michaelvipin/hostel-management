<?php
require_once __DIR__ . '/../includes/config.php';
require_role('admin');
echo "ADMIN DASHBOARD - Logged in as " . e($_SESSION['user']['name']);