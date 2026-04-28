<?php
require_once __DIR__ . '/../backend/db.php';
requireClubAdminAuth();

$clubId = (int) $_SESSION['club_admin_club_id'];
$message = '';
$messageType = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $membershipId = (int) ($_POST['membership_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if (!verifyCsrfToken()) {
        $message = 'Invalid form request. Please try again.';
        $messageType = 'error';
    } elseif (!in_array($status, ['approved', 'rejected'], true)) {
        $message = 'Invalid action requested.';
        $messageType = 'error';
    } else {
        $stmt = $conn->prepare('UPDATE club_memberships SET status = ?, decided_at = NOW() WHERE membership_id = ? AND club_id = ?');
        $stmt->bind_param('sii', $status, $membershipId, $clubId);
        $stmt->execute();
        $message = $stmt->affected_rows >= 0 ? 'Membership status updated.' : 'Could not update membership.';
        $messageType = $stmt->affected_rows >= 0 ? 'success' : 'error';
        $stmt->close();
    }
}

$stats = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
];

$statsStmt = $conn->prepare('SELECT status, COUNT(*) AS total FROM club_memberships WHERE club_id = ? GROUP BY status');
$statsStmt->bind_param('i', $clubId);
$statsStmt->execute();
$statsResult = $statsStmt->get_result();
while ($row = $statsResult->fetch_assoc()) {
    $stats[$row['status']] = (int) $row['total'];
}
$statsStmt->close();

$membersStmt = $conn->prepare(
    'SELECT cm.membership_id, cm.status, cm.requested_at, s.name, s.email, s.department
     FROM club_memberships cm
     INNER JOIN students s ON s.student_id = cm.student_id
     WHERE cm.club_id = ?
     ORDER BY FIELD(cm.status, "pending", "approved", "rejected"), cm.requested_at DESC'
);
$membersStmt->bind_param('i', $clubId);
$membersStmt->execute();
$members = $membersStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Members | University Club Event Management</title>
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
                <a href="members.php" class="active">Club Members</a>
                <a href="profile.php">Club Profile</a>
                <a href="../backend/logout.php?club=1">Logout</a>
            </nav>
        </aside>
        <main class="content">
            <section class="topbar">
                <div><h1>Club Members</h1><p class="muted">Review join requests and maintain your club member list.</p></div>
            </section>

            <?php if ($message !== ''): ?><div class="alert <?= e($messageType); ?>"><?= e($message); ?></div><?php endif; ?>

            <section class="stats-grid">
                <article class="stat-card"><span>Pending</span><strong><?= e((string) $stats['pending']); ?></strong><small>Need approval</small></article>
                <article class="stat-card"><span>Approved</span><strong><?= e((string) $stats['approved']); ?></strong><small>Official members</small></article>
                <article class="stat-card"><span>Rejected</span><strong><?= e((string) $stats['rejected']); ?></strong><small>Declined requests</small></article>
                <article class="stat-card"><span>Total Requests</span><strong><?= e((string) array_sum($stats)); ?></strong><small>All membership activity</small></article>
            </section>

            <section class="panel">
                <div class="section-head"><h2>Membership Requests</h2><p class="muted">Students request club membership from their member panel.</p></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Student</th><th>Email</th><th>Department</th><th>Requested</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if (!$members || $members->num_rows === 0): ?>
                                <tr><td colspan="6">No membership requests found.</td></tr>
                            <?php else: ?>
                                <?php while ($member = $members->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= e($member['name']); ?></td>
                                        <td><?= e($member['email']); ?></td>
                                        <td><?= e($member['department']); ?></td>
                                        <td><?= e(date('d M Y', strtotime($member['requested_at']))); ?></td>
                                        <td><span class="status-badge status-<?= e($member['status']); ?>"><?= e(ucfirst($member['status'])); ?></span></td>
                                        <td>
                                            <div class="action-group">
                                                <form method="post"><?= csrfField(); ?><input type="hidden" name="membership_id" value="<?= e((string) $member['membership_id']); ?>"><input type="hidden" name="status" value="approved"><button type="submit" class="small-btn">Approve</button></form>
                                                <form method="post"><?= csrfField(); ?><input type="hidden" name="membership_id" value="<?= e((string) $member['membership_id']); ?>"><input type="hidden" name="status" value="rejected"><button type="submit" class="small-btn secondary-btn">Reject</button></form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
