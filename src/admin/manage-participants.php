<?php
require_once __DIR__ . '/../backend/db.php';
requireAdminAuth();

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registrationId = (int) ($_POST['registration_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if (!verifyCsrfToken()) {
        $message = 'Invalid form request. Please try again.';
        $messageType = 'error';
    } elseif (in_array($status, ['approved', 'rejected'], true)) {
        if ($status === 'approved') {
            $capacityStmt = $conn->prepare(
                'SELECT e.capacity, reg.status AS current_status,
                        (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id AND r.status = "approved") AS approved_count
                 FROM registrations reg
                 INNER JOIN events e ON e.event_id = reg.event_id
                 WHERE reg.registration_id = ? LIMIT 1'
            );
            $capacityStmt->bind_param('i', $registrationId);
            $capacityStmt->execute();
            $capacityData = $capacityStmt->get_result()->fetch_assoc();
            $capacityStmt->close();

            if (!$capacityData) {
                $message = 'Registration not found.';
                $messageType = 'error';
            } elseif ($capacityData['current_status'] !== 'approved' && (int) $capacityData['approved_count'] >= (int) $capacityData['capacity']) {
                $message = 'This event is already at approved capacity.';
                $messageType = 'error';
            }
        }

        if ($messageType !== 'error') {
            $stmt = $conn->prepare('UPDATE registrations SET status = ? WHERE registration_id = ?');
            $stmt->bind_param('si', $status, $registrationId);
            $stmt->execute();

            if ($stmt->affected_rows >= 0) {
                $message = 'Participant status updated.';
            } else {
                $message = 'Unable to update participant status.';
                $messageType = 'error';
            }

            $stmt->close();
        }
    } else {
        $message = 'Invalid action requested.';
        $messageType = 'error';
    }
}

$participants = $conn->query(
    'SELECT r.registration_id, r.status, s.name AS student_name, s.email, s.department, e.title AS event_title, e.event_date
     FROM registrations r
     INNER JOIN students s ON s.student_id = r.student_id
     INNER JOIN events e ON e.event_id = r.event_id
     ORDER BY e.event_date ASC, r.registered_at ASC'
);

$participantStats = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
];

$participantStatsQuery = $conn->query(
    'SELECT status, COUNT(*) AS total
     FROM registrations
     GROUP BY status'
);

if ($participantStatsQuery) {
    while ($row = $participantStatsQuery->fetch_assoc()) {
        $participantStats[$row['status']] = (int) $row['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Participants | University Club Event Management</title>
    <link rel="stylesheet" href="../student/style.css">
</head>
<body class="app-page">
    <div class="app-shell">
        <aside class="sidebar">
            <div>
                <div class="brand-row">
                    <img src="../assets/images/club_logo.svg" alt="University Club Event Management Logo" class="brand-logo">
                    <div class="brand-copy">
                        <strong>University Club Event Management</strong>
                        <span>Admin Event Control</span>
                    </div>
                </div>
            </div>

            <div>
                <nav class="nav-links">
                    <a href="admin-dashboard.php">Dashboard</a>
                    <a href="create-event.php">Create Event</a>
                    <a href="manage-events.php">Manage Events</a>
                    <a href="manage-students.php">Members</a>
                    <a href="manage-participants.php" class="active">Participants</a>
                    <a href="reports.php">Reports</a>
                    <a href="profile.php">Admin Profile</a>
                    <a href="../index.php">Home</a>
                    <a href="../backend/logout.php?admin=1">Logout</a>
                </nav>
            </div>
        </aside>

        <main class="content">
            <section class="topbar">
                <div>
                    <h1>Manage Participants</h1>
                    <p class="muted">Approve or reject student registrations for each event.</p>
                </div>
                <div class="topbar-actions">
                    <a class="button-link ghost" href="admin-dashboard.php">Dashboard</a>
                    <a class="button-link ghost" href="manage-students.php">Members</a>
                    <a class="button-link" href="create-event.php">Create Event</a>
                </div>
            </section>

            <?php if ($message !== ''): ?>
                <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
            <?php endif; ?>

            <section class="stats-grid">
                <article class="stat-card">
                    <span>Pending Requests</span>
                    <strong><?= e((string) $participantStats['pending']); ?></strong>
                    <small>Waiting for admin decision</small>
                </article>
                <article class="stat-card">
                    <span>Approved</span>
                    <strong><?= e((string) $participantStats['approved']); ?></strong>
                    <small>Confirmed participants</small>
                </article>
                <article class="stat-card">
                    <span>Rejected</span>
                    <strong><?= e((string) $participantStats['rejected']); ?></strong>
                    <small>Declined requests</small>
                </article>
                <article class="stat-card">
                    <span>Total Reviewed</span>
                    <strong><?= e((string) ($participantStats['pending'] + $participantStats['approved'] + $participantStats['rejected'])); ?></strong>
                    <small>All registration entries</small>
                </article>
            </section>

            <section class="panel">
                <div class="section-head">
                    <h2>Participant Requests</h2>
                    <p class="muted">Review requests event by event and update the current status.</p>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$participants || $participants->num_rows === 0): ?>
                                <tr>
                                    <td colspan="7">No registrations found.</td>
                                </tr>
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
                                                <form method="post">
                                                    <?= csrfField(); ?>
                                                    <input type="hidden" name="registration_id" value="<?= e((string) $participant['registration_id']); ?>">
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="small-btn">Approve</button>
                                                </form>
                                                <form method="post">
                                                    <?= csrfField(); ?>
                                                    <input type="hidden" name="registration_id" value="<?= e((string) $participant['registration_id']); ?>">
                                                    <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" class="small-btn secondary-btn">Reject</button>
                                                </form>
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
