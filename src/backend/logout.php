<?php
require_once __DIR__ . '/db.php';

session_unset();
session_destroy();

if (isset($_GET['admin'])) {
    $target = '../admin/admin-login.php';
} elseif (isset($_GET['club'])) {
    $target = '../club-admin/login.php';
} else {
    $target = '../student/login.php';
}

header('Location: ' . $target);
exit;
