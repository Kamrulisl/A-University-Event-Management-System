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

// Keep lightweight schema compatibility for older local databases.
$columnCheck = $conn->query(
    "SELECT COUNT(*) AS total
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = '{$dbName}'
       AND TABLE_NAME = 'events'
       AND COLUMN_NAME = 'image_path'"
);

if ($columnCheck) {
    $columnData = $columnCheck->fetch_assoc();
    if ((int) ($columnData['total'] ?? 0) === 0) {
        $conn->query("ALTER TABLE events ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER description");
    }
}

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

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrfToken(): bool
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    return is_string($submittedToken) && hash_equals(csrfToken(), $submittedToken);
}

function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidDate(string $date): bool
{
    $parsedDate = DateTime::createFromFormat('Y-m-d', $date);
    return $parsedDate !== false && $parsedDate->format('Y-m-d') === $date;
}

function eventImageUrl(?string $path, string $prefix = ''): string
{
    $cleanPath = trim((string) $path);
    if ($cleanPath !== '') {
        return e($prefix . ltrim($cleanPath, '/'));
    }

    return e($prefix . 'assets/images/puc_logo.png');
}

function storeEventImage(array $file, string $uploadDir, string $publicPrefix): ?string
{
    if (!isset($file['tmp_name']) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return null;
    }

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $detectedMime = mime_content_type($file['tmp_name']);

    if (!isset($allowedMimeTypes[$detectedMime])) {
        return null;
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $originalName = $file['name'] ?? '';
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        return null;
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $safeExtension = $allowedMimeTypes[$detectedMime];
    $fileName = 'event_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $safeExtension;
    $targetPath = rtrim($uploadDir, '/') . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return null;
    }

    return rtrim($publicPrefix, '/') . '/' . $fileName;
}
