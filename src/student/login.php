<?php
require_once __DIR__ . '/../backend/db.php';

if (isStudentLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!verifyCsrfToken()) {
        $message = 'Invalid form request. Please try again.';
    } elseif (!isValidEmail($email)) {
        $message = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare('SELECT student_id, name, email, password FROM students WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();

        if ($student && password_verify($password, $student['password'])) {
            session_regenerate_id(true);
            $_SESSION['student_id'] = (int) $student['student_id'];
            $_SESSION['student_name'] = $student['name'];
            $_SESSION['student_email'] = $student['email'];

            header('Location: dashboard.php');
            exit;
        }

        $message = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login | University Event Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <div class="brand-block">
            <div class="brand-lockup">
                <img src="../assets/images/puc_logo.png" alt="University Event Management System Logo" class="brand-logo">
                <div>
                    <p class="eyebrow">University Event Management System</p>
                    <h1>Student Login</h1>
                </div>
            </div>
            <p class="muted">Sign in to view upcoming events, register quickly, and track approval updates.</p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
        <?php endif; ?>

        <form method="post" class="stack-form">
            <?= csrfField(); ?>
            <label>
                <span>Email Address</span>
                <input type="email" name="email" placeholder="student@puc.ac.bd" required>
            </label>

            <label>
                <span>Password</span>
                <input type="password" name="password" placeholder="Enter password" required>
            </label>

            <button type="submit">Login</button>
        </form>

        <p class="switch-link">New here? <a href="register.php">Create a student account</a></p>
        <p class="switch-link"><a href="../index.php">Back to home</a></p>
        <p class="switch-link"><a href="../admin/admin-login.php">Admin login</a></p>
    </div>
</body>
</html>
