<?php
require_once __DIR__ . '/../backend/db.php';

if (isStudentLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? 'General');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!verifyCsrfToken()) {
        $message = 'Invalid form request. Please try again.';
    } elseif ($name === '' || $department === '') {
        $message = 'Name and department are required.';
    } elseif (!isValidEmail($email)) {
        $message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
    } else {
        $checkStmt = $conn->prepare('SELECT student_id FROM students WHERE email = ? LIMIT 1');
        $checkStmt->bind_param('s', $email);
        $checkStmt->execute();
        $existingStudent = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($existingStudent) {
            $message = 'This email is already registered.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare('INSERT INTO students (name, email, password, department) VALUES (?, ?, ?, ?)');
            $insertStmt->bind_param('ssss', $name, $email, $hashedPassword, $department);

            if ($insertStmt->execute()) {
                $message = 'Registration successful. Please login.';
                $messageType = 'success';
            } else {
                $message = 'Registration failed. Please try again.';
            }

            $insertStmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration | University Event Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <div class="brand-block">
            <div class="brand-lockup">
                <img src="../assets/images/puc_logo.png" alt="University Event Management System Logo" class="brand-logo">
                <div>
                    <p class="eyebrow">University Event Management System</p>
                    <h1>Student Registration</h1>
                </div>
            </div>
            <p class="muted">Create your account to register for university events and monitor your participation status.</p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
        <?php endif; ?>

        <form method="post" class="stack-form">
            <?= csrfField(); ?>
            <div class="inline-grid">
                <label>
                    <span>Full Name</span>
                    <input type="text" name="name" placeholder="Your full name" required>
                </label>

                <label>
                    <span>Department</span>
                    <input type="text" name="department" placeholder="CSE / EEE / BBA" required>
                </label>
            </div>

            <label>
                <span>Email Address</span>
                <input type="email" name="email" placeholder="student@puc.ac.bd" required>
            </label>

            <div class="inline-grid">
                <label>
                    <span>Password</span>
                    <input type="password" name="password" placeholder="Create password" minlength="6" required>
                </label>

                <label>
                    <span>Confirm Password</span>
                    <input type="password" name="confirm_password" placeholder="Repeat password" minlength="6" required>
                </label>
            </div>

            <button type="submit">Register</button>
        </form>

        <p class="switch-link">Already registered? <a href="login.php">Back to login</a></p>
        <p class="switch-link"><a href="../index.php">Back to home</a></p>
    </div>
</body>
</html>
