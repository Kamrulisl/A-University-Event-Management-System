<?php
require_once __DIR__ . '/../backend/db.php';

if (isClubAdminLoggedIn()) {
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
        $stmt = $conn->prepare(
            'SELECT ca.club_admin_id, ca.club_id, ca.name, ca.email, ca.password, ca.status, c.name AS club_name
             FROM club_admins ca
             INNER JOIN clubs c ON c.club_id = ca.club_id
             WHERE ca.email = ? LIMIT 1'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $clubAdmin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($clubAdmin && $clubAdmin['status'] === 'active' && password_verify($password, $clubAdmin['password'])) {
            session_regenerate_id(true);
            $_SESSION['club_admin_id'] = (int) $clubAdmin['club_admin_id'];
            $_SESSION['club_admin_club_id'] = (int) $clubAdmin['club_id'];
            $_SESSION['club_admin_name'] = $clubAdmin['name'];
            $_SESSION['club_admin_email'] = $clubAdmin['email'];
            $_SESSION['club_admin_club_name'] = $clubAdmin['club_name'];

            header('Location: dashboard.php');
            exit;
        }

        $message = 'Invalid club admin credentials or inactive account.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Admin Login | University Club Event Management</title>
    <link rel="stylesheet" href="../student/style.css">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-showcase">
            <div class="brand-lockup">
                <img src="../assets/images/puc_logo.png" alt="PUC Logo" class="brand-logo large-logo">
                <div>
                    <p class="eyebrow">Club Admin Panel</p>
                    <h1>Manage your club, events, members, and approvals.</h1>
                </div>
            </div>
            <p>Club admins can publish events for their own club, review event registrations, approve club memberships, and keep club profile information updated.</p>
            <div class="auth-benefits">
                <span>Club events</span>
                <span>Member approvals</span>
                <span>Participant control</span>
            </div>
        </section>

        <section class="auth-card">
            <div class="brand-block">
                <p class="eyebrow">Club Workspace</p>
                <h2>Club Admin Login</h2>
                <p class="muted">Default demo: programming.admin@club.edu / club12345</p>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
            <?php endif; ?>

            <form method="post" class="stack-form">
                <?= csrfField(); ?>
                <label>
                    <span>Email Address</span>
                    <input type="email" name="email" placeholder="programming.admin@club.edu" autocomplete="email" required>
                </label>

                <label>
                    <span>Password</span>
                    <input type="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
                </label>

                <button type="submit">Open Club Panel</button>
            </form>

            <div class="auth-links">
                <span><a href="../student/login.php">Member login</a></span>
                <span><a href="../admin/admin-login.php">Super admin login</a></span>
                <span><a href="../index.php">Back to website</a></span>
            </div>
        </section>
    </main>
</body>
</html>
