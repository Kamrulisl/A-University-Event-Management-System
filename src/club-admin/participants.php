<?php
require_once __DIR__ . '/../backend/db.php';
requireClubAdminAuth();

$clubId = (int) $_SESSION['club_admin_club_id'];
$message = '';
$messageType = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $registrationId = (int) ($_POST['registration_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if (!verifyCsrfToken()) {
        $message = 'Invalid form request. Please try again.';
        $messageType = 'error';
    } elseif (!in_array($status, ['approved', 'rejected'], true)) {
        $message = 'Invalid action requested.';
        $messageType = 'error';
    } else {
        if ($status === 'approved') {
            $capacityStmt = $conn->prepare(
                'SELECT e.capacity, reg.status AS current_status,
                        (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id AND r.status = "approved") AS approved_count
                 FROM registrations reg
                 INNER JOIN events e ON e.event_id = reg.event_id
                 WHERE reg.registration_id = ? AND e.club_id = ? LIMIT 1'
            );
            $capacityStmt->bind_param('ii', $registrationId, $clubId);
            $capacityStmt->execute();
            $capacityData = $capacityStmt->get_result()->fetch_assoc();
            $capacityStmt->close();

            if (!$capacityData) {
                $message = 'Registration not found for your club.';
                $messageType = 'error';
            } elseif ($capacityData['current_status'] !== 'approved' && (int) $capacityData['approved_count'] >= (int) $capacityData['capacity']) {
                $message = 'This event is already at approved capacity.';
                $messageType = 'error';
            }
        }

        if ($messageType !== 'error') {
            $stmt = $conn->prepare(
                'UPDATE registrations r
                 INNER JOIN events e ON e.event_id = r.event_id
                 SET r.status = ?
                 WHERE r.registration_id = ? AND e.club_id = ?'
            );
            $stmt->bind_param('sii', $status, $registrationId, $clubId);
            $stmt->execute();
            $message = $stmt->affected_rows >= 0 ? 'Participant status updated.' : 'Unable to update participant status.';
            $messageType = $stmt->affected_rows >= 0 ? 'success' : 'error';
            $stmt->close();
        }
    }
}

$participantsStmt = $conn->prepare(
    'SELECT r.registration_id, r.status, r.registered_at, s.name AS student_name, s.email, s.department,
            e.title AS event_title, e.event_date
     FROM registrations r
     INNER JOIN students s ON s.student_id = r.student_id
     INNER JOIN events e ON e.event_id = r.event_id
     WHERE e.club_id = ?
     ORDER BY e.event_date ASC, r.registered_at ASC'
);
$participantsStmt->bind_param('i', $clubId);
$participantsStmt->execute();
$participants = $participantsStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Participants | University Club Event Management</title>
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
                <a href="participants.php" class="active">Participants</a>
                <a href="members.php">Club Members</a>
                <a href="profile.php">Club Profile</a>
                <a href="../backend/logout.php?club=1">Logout</a>
            </nav>
        </aside>
        <main class="content">
            <section class="topbar">
                <div><h1>Participant Requests</h1><p class="muted">Approve or reject registrations for this club's events.</p></div>
            </section>

            <?php if ($message !== ''): ?><div class="alert <?= e($messageType); ?>"><?= e($message); ?></div><?php endif; ?>

            <section class="panel">
                <div class="section-head"><h2>Event Registrations</h2><p class="muted">Only <?= e($_SESSION['club_admin_club_name']); ?> registrations appear here.</p></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Member</th><th>Email</th><th>Department</th><th>Event</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if (!$participants || $participants->num_rows === 0): ?>
                                <tr><td colspan="7">No registrations found.</td></tr>
                            <?php else: ?>
                                <?php while ($participant = $participants->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= e($participant['student_name']); ?></td>
                                        <td><?= e($participant['email']); ?></td>
                                        <td><?= e($participant['department']); ?></td>
                                        <td><?= e($participant['event_title']); ?></td>
                                        <td><?= e(date('d M Y', strtotime($participant['event_date']))); ?></td>
                                        <td><span class="status-badge status-<?= e($participant['status']); ?>"><?= e(ucfirst($participant['status'])); ?></span></td>
                                        <td>
                                            <div class="action-group">
                                                <form method="post"><?= csrfField(); ?><input type="hidden" name="registration_id" value="<?= e((string) $participant['registration_id']); ?>"><input type="hidden" name="status" value="approved"><button type="submit" class="small-btn">Approve</button></form>
                                                <form method="post"><?= csrfField(); ?><input type="hidden" name="registration_id" value="<?= e((string) $participant['registration_id']); ?>"><input type="hidden" name="status" value="rejected"><button type="submit" class="small-btn secondary-btn">Reject</button></form>
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
