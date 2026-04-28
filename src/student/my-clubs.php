<?php
require_once __DIR__ . '/../backend/db.php';
requireStudentAuth();

$studentId = (int) $_SESSION['student_id'];
$message = '';
$messageType = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    $clubId = (int) ($_POST['club_id'] ?? 0);

    if (!verifyCsrfToken()) {
        $message = 'Invalid form request. Please try again.';
        $messageType = 'error';
    } elseif ($clubId < 1) {
        $message = 'Invalid club selected.';
        $messageType = 'error';
    } elseif ($action === 'join_club') {
        $stmt = $conn->prepare('INSERT IGNORE INTO club_memberships (student_id, club_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $studentId, $clubId);
        $stmt->execute();
        $message = $stmt->affected_rows > 0 ? 'Club membership request submitted.' : 'You already requested this club.';
        $messageType = $stmt->affected_rows > 0 ? 'success' : 'error';
        $stmt->close();
    } elseif ($action === 'cancel_request') {
        $stmt = $conn->prepare('DELETE FROM club_memberships WHERE student_id = ? AND club_id = ? AND status = "pending"');
        $stmt->bind_param('ii', $studentId, $clubId);
        $stmt->execute();
        $message = $stmt->affected_rows > 0 ? 'Pending club request cancelled.' : 'Only pending requests can be cancelled.';
        $messageType = $stmt->affected_rows > 0 ? 'success' : 'error';
        $stmt->close();
    }
}

$clubsStmt = $conn->prepare(
    'SELECT c.club_id, c.name, c.category, c.advisor_name, c.advisor_email, c.description, c.logo_path,
            cm.status AS my_status,
            COUNT(DISTINCT e.event_id) AS total_events,
            COUNT(DISTINCT approved.membership_id) AS approved_members
     FROM clubs c
     LEFT JOIN club_memberships cm
        ON cm.club_id = c.club_id
        AND cm.student_id = ?
     LEFT JOIN events e
        ON e.club_id = c.club_id
     LEFT JOIN club_memberships approved
        ON approved.club_id = c.club_id
        AND approved.status = "approved"
     WHERE c.status = "active"
     GROUP BY c.club_id, c.name, c.category, c.advisor_name, c.advisor_email, c.description, c.logo_path, cm.status
     ORDER BY c.name ASC'
);
$clubsStmt->bind_param('i', $studentId);
$clubsStmt->execute();
$clubs = $clubsStmt->get_result();

$myMembershipsStmt = $conn->prepare(
    'SELECT c.name, c.category, cm.status, cm.requested_at
     FROM club_memberships cm
     INNER JOIN clubs c ON c.club_id = cm.club_id
     WHERE cm.student_id = ?
     ORDER BY cm.requested_at DESC'
);
$myMembershipsStmt->bind_param('i', $studentId);
$myMembershipsStmt->execute();
$myMemberships = $myMembershipsStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Clubs | University Club Event Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-page">
    <div class="app-shell">
        <aside class="sidebar">
            <div>
                <div class="brand-row">
                    <img src="../assets/images/puc_logo.png" alt="PUC Logo" class="brand-logo">
                    <div class="brand-copy">
                        <strong>University Club Event Management</strong>
                        <span>Member Club Portal</span>
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

            <nav class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="my-clubs.php" class="active">My Clubs</a>
                <a href="my-events.php">My Events</a>
                <a href="profile.php">Profile Settings</a>
                <a href="../index.php">Home</a>
                <a href="../backend/logout.php">Logout</a>
            </nav>
        </aside>

        <main class="content">
            <section class="topbar">
                <div>
                    <h1>My Clubs</h1>
                    <p class="muted">Join university clubs, track membership approval, and discover club activity.</p>
                </div>
                <div class="topbar-actions">
                    <a class="button-link ghost" href="dashboard.php">Events Dashboard</a>
                    <a class="button-link" href="my-events.php">My Events</a>
                </div>
            </section>

            <?php if ($message !== ''): ?>
                <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
            <?php endif; ?>

            <div class="panel-grid">
                <section class="panel">
                    <div class="section-head">
                        <h2>Available Clubs</h2>
                        <p class="muted">Request membership to become part of a club.</p>
                    </div>
                    <div class="showcase-grid">
                        <?php if (!$clubs || $clubs->num_rows === 0): ?>
                            <div class="empty-state">No active clubs found.</div>
                        <?php else: ?>
                            <?php while ($club = $clubs->fetch_assoc()): ?>
                                <article class="club-card">
                                    <div class="club-card-head">
                                        <img src="<?= eventImageUrl($club['logo_path'], '../'); ?>" alt="<?= e($club['name']); ?>">
                                        <div>
                                            <?php if ($club['my_status']): ?>
                                                <span class="status-badge status-<?= e($club['my_status']); ?>"><?= e(ucfirst($club['my_status'])); ?></span>
                                            <?php else: ?>
                                                <span class="status-badge status-success">Open</span>
                                            <?php endif; ?>
                                            <h3><?= e($club['name']); ?></h3>
                                            <p><?= e($club['category'] ?: 'General'); ?></p>
                                        </div>
                                    </div>
                                    <p><?= e($club['description'] ?: 'No club description added yet.'); ?></p>
                                    <div class="meta-list">
                                        <span>Advisor: <?= e($club['advisor_name'] ?: 'Not assigned'); ?></span>
                                        <span>Events: <?= e((string) $club['total_events']); ?></span>
                                        <span>Approved Members: <?= e((string) $club['approved_members']); ?></span>
                                    </div>
                                    <div class="table-actions">
                                        <?php if (!$club['my_status']): ?>
                                            <form method="post">
                                                <?= csrfField(); ?>
                                                <input type="hidden" name="action" value="join_club">
                                                <input type="hidden" name="club_id" value="<?= e((string) $club['club_id']); ?>">
                                                <button type="submit" class="small-btn">Request Join</button>
                                            </form>
                                        <?php elseif ($club['my_status'] === 'pending'): ?>
                                            <form method="post">
                                                <?= csrfField(); ?>
                                                <input type="hidden" name="action" value="cancel_request">
                                                <input type="hidden" name="club_id" value="<?= e((string) $club['club_id']); ?>">
                                                <button type="submit" class="small-btn secondary-btn">Cancel Request</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="glass-panel">
                    <div class="section-head">
                        <h2>Membership Status</h2>
                        <p class="muted">Your current club membership requests.</p>
                    </div>
                    <div class="list-stack">
                        <?php if (!$myMemberships || $myMemberships->num_rows === 0): ?>
                            <div class="empty-state">You have not requested any club membership yet.</div>
                        <?php else: ?>
                            <?php while ($membership = $myMemberships->fetch_assoc()): ?>
                                <div class="list-item">
                                    <div>
                                        <strong><?= e($membership['name']); ?></strong>
                                        <span><?= e($membership['category'] ?: 'General'); ?> | <?= e(date('d M Y', strtotime($membership['requested_at']))); ?></span>
                                    </div>
                                    <span class="status-badge status-<?= e($membership['status']); ?>"><?= e(ucfirst($membership['status'])); ?></span>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
