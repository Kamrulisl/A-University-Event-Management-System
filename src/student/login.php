<?php
require_once __DIR__ . '/../backend/db.php';

if (isStudentLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = 'error';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
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
    <title>Member Login | University Club Event Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-showcase">
            <div class="brand-lockup">
                <img src="../assets/images/puc_logo.png" alt="PUC Logo" class="brand-logo large-logo">
                <div>
                    <p class="eyebrow">Member Portal</p>
                    <h1>University Club Event Management</h1>
                </div>
            </div>
            <p>Discover club events, request seats, follow approvals, and keep your participation history in one place.</p>
            <div class="auth-benefits">
                <span>Live event catalog</span>
                <span>Seat tracking</span>
                <span>Approval updates</span>
            </div>
        </section>

        <section class="auth-card">
            <div class="brand-block">
                <p class="eyebrow">Welcome Back</p>
                <h2>Member Login</h2>
                <p class="muted">Sign in to browse club events and manage your registration requests.</p>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
            <?php endif; ?>

            <form method="post" class="stack-form">
                <?= csrfField(); ?>
                <label>
                    <span>Email Address</span>
                    <input type="email" name="email" placeholder="member@club.edu" autocomplete="email" required>
                </label>

                <label>
                    <span>Password</span>
                    <input type="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
                </label>

                <button type="submit">Login to Portal</button>
            </form>

            <div class="auth-links">
                <span>New member? <a href="register.php">Create account</a></span>
                <span><a href="../index.php">Back to website</a></span>
                <span><a href="../admin/admin-login.php">Admin login</a></span>
            </div>
        </section>
    </main>
</body>
</html>
