<?php
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/../storage/sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0775, true);
    }
    session_save_path($sessionPath);
    session_start();
}

$dbHost = 'localhost';
$dbUser = 'event_app';
$dbPass = 'event_app_2026';
$dbName = 'university_club_events';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

function ensureColumn(mysqli $conn, string $dbName, string $tableName, string $columnName, string $definition): void
{
    $stmt = $conn->prepare(
        'SELECT COUNT(*) AS total
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ?
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?'
    );
    $stmt->bind_param('sss', $dbName, $tableName, $columnName);
    $stmt->execute();
    $columnData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int) ($columnData['total'] ?? 0) === 0) {
        $conn->query("ALTER TABLE {$tableName} ADD COLUMN {$columnName} {$definition}");
    }
}

// Keep lightweight schema compatibility for older local databases.
ensureColumn($conn, $dbName, 'events', 'image_path', 'VARCHAR(255) DEFAULT NULL AFTER description');
ensureColumn($conn, $dbName, 'events', 'category', "VARCHAR(80) DEFAULT 'General' AFTER image_path");
ensureColumn($conn, $dbName, 'events', 'event_time', 'TIME DEFAULT NULL AFTER event_date');
ensureColumn($conn, $dbName, 'events', 'registration_deadline', 'DATE DEFAULT NULL AFTER event_time');

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

function isValidTime(?string $time): bool
{
    if ($time === null || $time === '') {
        return true;
    }

    $parsedTime = DateTime::createFromFormat('H:i', $time);
    return $parsedTime !== false && $parsedTime->format('H:i') === $time;
}

function registrationDeadlineOpen(?string $deadline): bool
{
    if ($deadline === null || $deadline === '') {
        return true;
    }

    return strtotime($deadline . ' 23:59:59') >= time();
}

function eventImageUrl(?string $path, string $prefix = ''): string
{
    $cleanPath = trim((string) $path);
    if ($cleanPath !== '') {
        return e($prefix . ltrim($cleanPath, '/'));
    }

    return e($prefix . 'assets/images/club_logo.svg');
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
