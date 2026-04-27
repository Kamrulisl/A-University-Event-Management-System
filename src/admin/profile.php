<?php
require_once __DIR__ . '/../backend/db.php';
requireAdminAuth();

$adminId = (int) $_SESSION['admin_id'];
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verifyCsrfToken()) {
        $message = 'Invalid form request. Please try again.';
        $messageType = 'error';
    } elseif ($action === 'profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($name === '') {
            $message = 'Admin name is required.';
            $messageType = 'error';
        } elseif (!isValidEmail($email)) {
            $message = 'Please enter a valid email address.';
            $messageType = 'error';
        } else {
            $checkStmt = $conn->prepare('SELECT admin_id FROM admins WHERE email = ? AND admin_id != ? LIMIT 1');
            $checkStmt->bind_param('si', $email, $adminId);
            $checkStmt->execute();
            $existing = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();

            if ($existing) {
                $message = 'This email is already being used by another admin.';
                $messageType = 'error';
            } else {
                $updateStmt = $conn->prepare('UPDATE admins SET name = ?, email = ? WHERE admin_id = ?');
                $updateStmt->bind_param('ssi', $name, $email, $adminId);

                if ($updateStmt->execute()) {
                    $_SESSION['admin_name'] = $name;
                    $_SESSION['admin_email'] = $email;
                    $message = 'Admin profile updated successfully.';
                } else {
                    $message = 'Admin profile update failed.';
                    $messageType = 'error';
                }

                $updateStmt->close();
            }
        }
    }

    if ($action === 'password' && $messageType !== 'error') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($newPassword) < 6) {
            $message = 'New password must be at least 6 characters long.';
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'New password and confirm password do not match.';
            $messageType = 'error';
        } else {
            $passwordStmt = $conn->prepare('SELECT password FROM admins WHERE admin_id = ? LIMIT 1');
            $passwordStmt->bind_param('i', $adminId);
            $passwordStmt->execute();
            $adminData = $passwordStmt->get_result()->fetch_assoc();
            $passwordStmt->close();

            if (!$adminData || !password_verify($currentPassword, $adminData['password'])) {
                $message = 'Current password is incorrect.';
                $messageType = 'error';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatePasswordStmt = $conn->prepare('UPDATE admins SET password = ? WHERE admin_id = ?');
                $updatePasswordStmt->bind_param('si', $newHash, $adminId);

                if ($updatePasswordStmt->execute()) {
                    $message = 'Admin password changed successfully.';
                } else {
                    $message = 'Password update failed.';
                    $messageType = 'error';
                }

                $updatePasswordStmt->close();
            }
        }
    }
}

$adminStmt = $conn->prepare('SELECT name, email, created_at FROM admins WHERE admin_id = ? LIMIT 1');
$adminStmt->bind_param('i', $adminId);
$adminStmt->execute();
$admin = $adminStmt->get_result()->fetch_assoc();
$adminStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile | Premier University</title>
    <link rel="stylesheet" href="../student/style.css">
</head>
<body class="app-page">
    <div class="app-shell">
        <aside class="sidebar">
            <div>
                <div class="brand-row">
                    <img src="../assets/images/puc_logo.png" alt="Premier University Logo" class="brand-logo">
                    <div class="brand-copy">
                        <strong>Premier University</strong>
                        <span>Admin Event Control</span>
                    </div>
                </div>
                <div class="sidebar-card" style="margin-top: 22px;">
                    <p class="eyebrow">Administrator</p>
                    <div class="profile-meta">
                        <strong><?= e($_SESSION['admin_name']); ?></strong>
                        <span><?= e($_SESSION['admin_email']); ?></span>
                    </div>
                </div>
            </div>
            <div>
                <nav class="nav-links">
                    <a href="admin-dashboard.php">Dashboard</a>
                    <a href="create-event.php">Create Event</a>
                    <a href="manage-events.php">Manage Events</a>
                    <a href="manage-students.php">Students</a>
                    <a href="manage-participants.php">Participants</a>
                    <a href="reports.php">Reports</a>
                    <a href="profile.php" class="active">Admin Profile</a>
                    <a href="../index.php">Home</a>
                    <a href="../backend/logout.php?admin=1">Logout</a>
                </nav>
            </div>
        </aside>

        <main class="content">
            <section class="topbar">
                <div>
                    <h1>Admin Profile</h1>
                    <p class="muted">Update admin account information and change password from one place.</p>
                </div>
            </section>

            <?php if ($message !== ''): ?>
                <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
            <?php endif; ?>

            <section class="stats-grid">
                <article class="stat-card">
                    <span>Admin Name</span>
                    <strong><?= e($admin['name'] ?? ''); ?></strong>
                    <small>Current administrator</small>
                </article>
                <article class="stat-card">
                    <span>Email</span>
                    <strong><?= e($admin['email'] ?? ''); ?></strong>
                    <small>Login email address</small>
                </article>
                <article class="stat-card">
                    <span>Access Level</span>
                    <strong>Full</strong>
                    <small>Event and user management</small>
                </article>
                <article class="stat-card">
                    <span>Created</span>
                    <strong><?= e(date('d M Y', strtotime($admin['created_at'] ?? 'now'))); ?></strong>
                    <small>Admin account age</small>
                </article>
            </section>

            <div class="panel-grid">
                <section class="panel">
                    <div class="section-head">
                        <h2>Profile Information</h2>
                    </div>
                    <form method="post" class="stack-form">
                        <?= csrfField(); ?>
                        <input type="hidden" name="action" value="profile">
                        <label>
                            <span>Admin Name</span>
                            <input type="text" name="name" value="<?= e($admin['name'] ?? ''); ?>" required>
                        </label>
                        <label>
                            <span>Email Address</span>
                            <input type="email" name="email" value="<?= e($admin['email'] ?? ''); ?>" required>
                        </label>
                        <button type="submit">Save Profile</button>
                    </form>
                </section>

                <section class="panel">
                    <div class="section-head">
                        <h2>Change Password</h2>
                    </div>
                    <form method="post" class="stack-form">
                        <?= csrfField(); ?>
                        <input type="hidden" name="action" value="password">
                        <label>
                            <span>Current Password</span>
                            <input type="password" name="current_password" required>
                        </label>
                        <label>
                            <span>New Password</span>
                            <input type="password" name="new_password" minlength="6" required>
                        </label>
                        <label>
                            <span>Confirm New Password</span>
                            <input type="password" name="confirm_password" minlength="6" required>
                        </label>
                        <button type="submit">Update Password</button>
                    </form>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
