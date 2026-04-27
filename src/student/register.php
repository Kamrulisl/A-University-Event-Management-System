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
    $department = trim($_POST['department'] ?? 'Premier University');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($password !== $confirmPassword) {
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
    <title>Student Registration | Premier University</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <div class="brand-block">
            <div class="brand-lockup">
                <img src="../assets/images/puc_logo.png" alt="Premier University Logo" class="brand-logo">
                <div>
                    <p class="eyebrow">Premier University</p>
                    <h1>Student Registration</h1>
                </div>
            </div>
            <p class="muted">Create your account to register for university events and monitor your participation status.</p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
        <?php endif; ?>

        <form method="post" class="stack-form">
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
                    <input type="password" name="password" placeholder="Create password" required>
                </label>

                <label>
                    <span>Confirm Password</span>
                    <input type="password" name="confirm_password" placeholder="Repeat password" required>
                </label>
            </div>

            <button type="submit">Register</button>
        </form>

        <p class="switch-link">Already registered? <a href="login.php">Back to login</a></p>
        <p class="switch-link"><a href="../index.php">Back to home</a></p>
    </div>
</body>
</html>
