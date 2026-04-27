<?php
require_once __DIR__ . '/../backend/db.php';

if (isAdminLoggedIn()) {
    header('Location: admin-dashboard.php');
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
    <title>Admin Login | University Club Event Management</title>
    <link rel="stylesheet" href="../student/style.css">
</head>
<body class="auth-page">
    <main class="auth-shell admin-auth">
        <section class="auth-showcase">
            <div class="brand-lockup">
                <img src="../assets/images/club_logo.svg" alt="University Club Event Management Logo" class="brand-logo large-logo">
                <div>
                    <p class="eyebrow">Admin Workspace</p>
                    <h1>Control every club event from one dashboard.</h1>
                </div>
            </div>
            <p>Create events, review member requests, manage capacity, and read analytics for club activity.</p>
            <div class="auth-benefits">
                <span>Event publishing</span>
                <span>Participant approval</span>
                <span>Reports</span>
            </div>
        </section>

        <section class="auth-card">
            <div class="brand-block">
                <p class="eyebrow">Secure Access</p>
                <h2>Admin Login</h2>
                <p class="muted">Use your admin account to manage clubs, events, participants, and reports.</p>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
            <?php endif; ?>

            <form method="post" class="stack-form">
                <?= csrfField(); ?>
                <label>
                    <span>Admin Email</span>
                    <input type="email" name="email" placeholder="admin@club.edu" autocomplete="email" required>
                </label>

                <label>
                    <span>Password</span>
                    <input type="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
                </label>

                <button type="submit">Open Admin Panel</button>
            </form>

            <div class="auth-links">
                <span><a href="../student/login.php">Member login</a></span>
                <span><a href="../index.php">Back to website</a></span>
            </div>
        </section>
    </main>
</body>
</html>
