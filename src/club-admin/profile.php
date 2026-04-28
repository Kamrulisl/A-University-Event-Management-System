<?php
require_once __DIR__ . '/../backend/db.php';
requireClubAdminAuth();

$clubId = (int) $_SESSION['club_admin_club_id'];
$clubAdminId = (int) $_SESSION['club_admin_id'];
$message = '';
$messageType = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verifyCsrfToken()) {
        $message = 'Invalid form request. Please try again.';
        $messageType = 'error';
    } elseif ($action === 'update_account') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || !isValidEmail($email)) {
            $message = 'Name and valid email are required.';
            $messageType = 'error';
        } else {
            if ($password !== '') {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE club_admins SET name = ?, email = ?, password = ? WHERE club_admin_id = ?');
                $stmt->bind_param('sssi', $name, $email, $passwordHash, $clubAdminId);
            } else {
                $stmt = $conn->prepare('UPDATE club_admins SET name = ?, email = ? WHERE club_admin_id = ?');
                $stmt->bind_param('ssi', $name, $email, $clubAdminId);
            }

            if ($stmt->execute()) {
                $_SESSION['club_admin_name'] = $name;
                $_SESSION['club_admin_email'] = $email;
                $message = 'Club admin account updated.';
            } else {
                $message = 'Could not update account. Email may already exist.';
                $messageType = 'error';
            }
            $stmt->close();
        }
    } elseif ($action === 'update_club') {
        $name = trim($_POST['club_name'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $advisorName = trim($_POST['advisor_name'] ?? '');
        $advisorEmail = trim($_POST['advisor_email'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            $message = 'Club name is required.';
            $messageType = 'error';
        } elseif ($advisorEmail !== '' && !isValidEmail($advisorEmail)) {
            $message = 'Advisor email is not valid.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare(
                'UPDATE clubs
                 SET name = ?, category = ?, advisor_name = ?, advisor_email = ?, description = ?
                 WHERE club_id = ?'
            );
            $stmt->bind_param('sssssi', $name, $category, $advisorName, $advisorEmail, $description, $clubId);

            if ($stmt->execute()) {
                $_SESSION['club_admin_club_name'] = $name;
                $message = 'Club profile updated.';
            } else {
                $message = 'Could not update club profile.';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}

$accountStmt = $conn->prepare('SELECT name, email FROM club_admins WHERE club_admin_id = ? LIMIT 1');
$accountStmt->bind_param('i', $clubAdminId);
$accountStmt->execute();
$account = $accountStmt->get_result()->fetch_assoc();
$accountStmt->close();

$clubStmt = $conn->prepare('SELECT name, category, advisor_name, advisor_email, description, status FROM clubs WHERE club_id = ? LIMIT 1');
$clubStmt->bind_param('i', $clubId);
$clubStmt->execute();
$club = $clubStmt->get_result()->fetch_assoc();
$clubStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Profile | University Club Event Management</title>
    <link rel="stylesheet" href="../student/style.css">
</head>
<body class="app-page">
    <div class="app-shell">
        <aside class="sidebar club-sidebar">
            <div class="brand-row"><img src="../assets/images/puc_logo.png" alt="PUC Logo" class="brand-logo"><div class="brand-copy"><strong>University Club Event Management</strong><span>Club Admin Panel</span></div></div>
            <nav class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="create-event.php">Create Event</a>
                <a href="manage-events.php">Manage Events</a>
                <a href="participants.php">Participants</a>
                <a href="members.php">Club Members</a>
                <a href="profile.php" class="active">Club Profile</a>
                <a href="../backend/logout.php?club=1">Logout</a>
            </nav>
        </aside>
        <main class="content">
            <section class="topbar">
                <div><h1>Club Profile</h1><p class="muted">Update account security and public club information.</p></div>
            </section>

            <?php if ($message !== ''): ?><div class="alert <?= e($messageType); ?>"><?= e($message); ?></div><?php endif; ?>

            <div class="panel-grid">
                <section class="panel">
                    <div class="section-head"><h2>Admin Account</h2><p class="muted">Change your login identity or password.</p></div>
                    <form method="post" class="stack-form">
                        <?= csrfField(); ?>
                        <input type="hidden" name="action" value="update_account">
                        <label><span>Name</span><input type="text" name="name" value="<?= e($account['name'] ?? ''); ?>" required></label>
                        <label><span>Email</span><input type="email" name="email" value="<?= e($account['email'] ?? ''); ?>" required></label>
                        <label><span>New Password</span><input type="password" name="password" placeholder="Leave blank to keep current password"></label>
                        <button type="submit">Update Account</button>
                    </form>
                </section>

                <section class="panel">
                    <div class="section-head"><h2>Public Club Info</h2><p class="muted">This appears on the public club page and member dashboard.</p></div>
                    <form method="post" class="stack-form">
                        <?= csrfField(); ?>
                        <input type="hidden" name="action" value="update_club">
                        <label><span>Club Name</span><input type="text" name="club_name" value="<?= e($club['name'] ?? ''); ?>" required></label>
                        <label><span>Category</span><input type="text" name="category" value="<?= e($club['category'] ?? 'General'); ?>"></label>
                        <label><span>Advisor Name</span><input type="text" name="advisor_name" value="<?= e($club['advisor_name'] ?? ''); ?>"></label>
                        <label><span>Advisor Email</span><input type="email" name="advisor_email" value="<?= e($club['advisor_email'] ?? ''); ?>"></label>
                        <label><span>Description</span><textarea name="description" rows="5"><?= e($club['description'] ?? ''); ?></textarea></label>
                        <button type="submit">Update Club</button>
                    </form>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
