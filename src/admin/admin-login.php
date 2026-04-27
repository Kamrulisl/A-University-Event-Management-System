<?php
require_once __DIR__ . '/../backend/db.php';

if (isAdminLoggedIn()) {
    header('Location: admin-dashboard.php');
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
        $stmt = $conn->prepare('SELECT admin_id, name, email, password FROM admins WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];

            header('Location: admin-dashboard.php');
            exit;
        }

        $message = 'Invalid admin credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | University Event Management System</title>
    <link rel="stylesheet" href="../student/style.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <div class="brand-block">
            <div class="brand-lockup">
                <img src="../assets/images/puc_logo.png" alt="University Event Management System Logo" class="brand-logo">
                <div>
                    <p class="eyebrow">University Event Management System</p>
                    <h1>Admin Login</h1>
                </div>
            </div>
            <p class="muted">Manage events, registrations, and participation approvals from a single admin workspace.</p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
        <?php endif; ?>

        <form method="post" class="stack-form">
            <?= csrfField(); ?>
            <label>
                <span>Admin Email</span>
                <input type="email" name="email" placeholder="admin@puc.ac.bd" required>
            </label>

            <label>
                <span>Password</span>
                <input type="password" name="password" placeholder="Enter password" required>
            </label>

            <button type="submit">Login</button>
        </form>

        <p class="switch-link"><a href="../student/login.php">Student login</a></p>
        <p class="switch-link"><a href="../index.php">Back to home</a></p>
    </div>
</body>
</html>
