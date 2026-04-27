<?php
require_once __DIR__ . '/db.php';

session_unset();
session_destroy();

$target = isset($_GET['admin']) ? '../admin/admin-login.php' : '../student/login.php';

header('Location: ' . $target);
exit;
