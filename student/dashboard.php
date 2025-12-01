<?php
require_once __DIR__ . '/../includes/config.php';
require_role('student');
echo "STUDENT DASHBOARD - Logged in as " . e($_SESSION['user']['name']);