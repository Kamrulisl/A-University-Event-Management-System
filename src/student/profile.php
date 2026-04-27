<?php
require_once __DIR__ . '/../backend/db.php';
requireStudentAuth();

$studentId = (int) $_SESSION['student_id'];
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
        $department = trim($_POST['department'] ?? '');

        if ($name === '' || $department === '') {
            $message = 'Name and department are required.';
            $messageType = 'error';
        } elseif (!isValidEmail($email)) {
            $message = 'Please enter a valid email address.';
            $messageType = 'error';
        } else {
            $checkStmt = $conn->prepare('SELECT student_id FROM students WHERE email = ? AND student_id != ? LIMIT 1');
            $checkStmt->bind_param('si', $email, $studentId);
            $checkStmt->execute();
            $existing = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();

            if ($existing) {
                $message = 'This email is already being used by another student.';
                $messageType = 'error';
            } else {
                $updateStmt = $conn->prepare('UPDATE students SET name = ?, email = ?, department = ? WHERE student_id = ?');
                $updateStmt->bind_param('sssi', $name, $email, $department, $studentId);

                if ($updateStmt->execute()) {
                    $_SESSION['student_name'] = $name;
                    $_SESSION['student_email'] = $email;
                    $message = 'Profile updated successfully.';
                } else {
                    $message = 'Profile update failed.';
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
            $passwordStmt = $conn->prepare('SELECT password FROM students WHERE student_id = ? LIMIT 1');
            $passwordStmt->bind_param('i', $studentId);
            $passwordStmt->execute();
            $currentHash = $passwordStmt->get_result()->fetch_assoc();
            $passwordStmt->close();

            if (!$currentHash || !password_verify($currentPassword, $currentHash['password'])) {
                $message = 'Current password is incorrect.';
                $messageType = 'error';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatePasswordStmt = $conn->prepare('UPDATE students SET password = ? WHERE student_id = ?');
                $updatePasswordStmt->bind_param('si', $newHash, $studentId);

                if ($updatePasswordStmt->execute()) {
                    $message = 'Password changed successfully.';
                } else {
                    $message = 'Password change failed.';
                    $messageType = 'error';
                }

                $updatePasswordStmt->close();
            }
        }
    }
}

$studentStmt = $conn->prepare(
    'SELECT s.name, s.email, s.department, s.created_at,
            COUNT(r.registration_id) AS total_registered,
            SUM(CASE WHEN r.status = "approved" THEN 1 ELSE 0 END) AS approved_count
     FROM students s
     LEFT JOIN registrations r ON r.student_id = s.student_id
     WHERE s.student_id = ?
     GROUP BY s.student_id, s.name, s.email, s.department, s.created_at'
);
$studentStmt->bind_param('i', $studentId);
$studentStmt->execute();
$student = $studentStmt->get_result()->fetch_assoc();
$studentStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Profile | University Club Event Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-page">
    <div class="app-shell">
        <aside class="sidebar">
            <div>
                <div class="brand-row">
                    <img src="../assets/images/club_logo.svg" alt="University Club Event Management Logo" class="brand-logo">
                    <div class="brand-copy">
                        <strong>University Club Event Management</strong>
                        <span>Member Event Portal</span>
                    </div>
                </div>
                <div class="sidebar-card" style="margin-top: 22px;">
                    <p class="eyebrow">Member Account</p>
                    <div class="profile-meta">
                        <strong><?= e($_SESSION['student_name']); ?></strong>
                        <span><?= e($_SESSION['student_email']); ?></span>
                    </div>
                </div>
            </div>
            <div>
                <nav class="nav-links">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="my-events.php">My Events</a>
                    <a href="profile.php" class="active">Profile Settings</a>
                    <a href="../index.php">Home</a>
                    <a href="../backend/logout.php">Logout</a>
                </nav>
            </div>
        </aside>

        <main class="content">
            <section class="topbar">
                <div>
                    <h1>My Profile</h1>
                    <p class="muted">Update your personal information and keep your account secure.</p>
                </div>
                <div class="topbar-actions">
                    <a class="button-link" href="my-events.php">My Events</a>
                    <a class="button-link ghost" href="dashboard.php">Back to Dashboard</a>
                </div>
            </section>

            <?php if ($message !== ''): ?>
                <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
            <?php endif; ?>

            <section class="stats-grid">
                <article class="stat-card">
                    <span>Total Registrations</span>
                    <strong><?= e((string) ((int) ($student['total_registered'] ?? 0))); ?></strong>
                    <small>All event requests by you</small>
                </article>
                <article class="stat-card">
                    <span>Approved Events</span>
                    <strong><?= e((string) ((int) ($student['approved_count'] ?? 0))); ?></strong>
                    <small>Confirmed participation</small>
                </article>
                <article class="stat-card">
                    <span>Department</span>
                    <strong><?= e($student['department'] ?? 'N/A'); ?></strong>
                    <small>Current academic unit</small>
                </article>
                <article class="stat-card">
                    <span>Joined</span>
                    <strong><?= e(date('d M Y', strtotime($student['created_at'] ?? 'now'))); ?></strong>
                    <small>Account creation date</small>
                </article>
            </section>

            <div class="panel-grid">
                <section class="panel">
                    <div class="section-head">
                        <h2>Profile Information</h2>
                        <p class="muted">Keep your basic student information up to date.</p>
                    </div>
                    <form method="post" class="stack-form">
                        <?= csrfField(); ?>
                        <input type="hidden" name="action" value="profile">
                        <div class="inline-grid">
                            <label>
                                <span>Full Name</span>
                                <input type="text" name="name" value="<?= e($student['name'] ?? ''); ?>" required>
                            </label>
                            <label>
                                <span>Department</span>
                                <input type="text" name="department" value="<?= e($student['department'] ?? ''); ?>" required>
                            </label>
                        </div>
                        <label>
                            <span>Email Address</span>
                            <input type="email" name="email" value="<?= e($student['email'] ?? ''); ?>" required>
                        </label>
                        <button type="submit">Save Profile</button>
                    </form>
                </section>

                <section class="panel">
                    <div class="section-head">
                        <h2>Change Password</h2>
                        <p class="muted">Use your current password to set a new one.</p>
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
