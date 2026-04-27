<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbHost = 'localhost';
$dbUser = 'event_app';
$dbPass = 'event_app_2026';
$dbName = 'premier_university_events';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

function isStudentLoggedIn(): bool
{
    return isset($_SESSION['student_id']);
}

function isAdminLoggedIn(): bool
{
    return isset($_SESSION['admin_id']);
}

function requireStudentAuth(): void
{
    if (!isStudentLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdminAuth(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: admin-login.php');
        exit;
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
