<?php
require_once __DIR__ . '/../backend/db.php';
requireStudentAuth();

$studentId = (int) $_SESSION['student_id'];
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $registrationId = (int) ($_POST['registration_id'] ?? 0);

    if ($action === 'cancel_registration') {
        $cancelStmt = $conn->prepare(
            'DELETE FROM registrations
             WHERE registration_id = ? AND student_id = ? AND status = "pending"'
        );
        $cancelStmt->bind_param('ii', $registrationId, $studentId);
        $cancelStmt->execute();

        if ($cancelStmt->affected_rows > 0) {
            $message = 'Pending registration cancelled successfully.';
        } else {
            $message = 'Only pending registrations can be cancelled.';
            $messageType = 'error';
        }

        $cancelStmt->close();
    }
}

$registrationsStmt = $conn->prepare(
    'SELECT r.registration_id, r.status, r.registered_at,
            e.title, e.description, e.event_date, e.venue, e.capacity
     FROM registrations r
     INNER JOIN events e ON e.event_id = r.event_id
     WHERE r.student_id = ?
     ORDER BY e.event_date ASC, r.registered_at DESC'
);
$registrationsStmt->bind_param('i', $studentId);
$registrationsStmt->execute();
$registrations = $registrationsStmt->get_result();

$studentSummaryStmt = $conn->prepare(
    'SELECT
        COUNT(*) AS total_registered,
        SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) AS rejected_count
     FROM registrations
     WHERE student_id = ?'
);
$studentSummaryStmt->bind_param('i', $studentId);
$studentSummaryStmt->execute();
$summary = $studentSummaryStmt->get_result()->fetch_assoc() ?: [
    'total_registered' => 0,
    'approved_count' => 0,
    'pending_count' => 0,
    'rejected_count' => 0,
];
$studentSummaryStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Events | Premier University</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-page">
    <div class="app-shell">
        <aside class="sidebar">
            <div>
                <div class="brand-row">
                    <img src="../assets/images/puc_logo.png" alt="Premier University Logo" class="brand-logo">
                    <div class="brand-copy">
                        <strong>Premier University</strong>
                        <span>Student Event Portal</span>
                    </div>
                </div>
                <div class="sidebar-card" style="margin-top: 22px;">
                    <p class="eyebrow">Student Account</p>
                    <div class="profile-meta">
                        <strong><?= e($_SESSION['student_name']); ?></strong>
                        <span><?= e($_SESSION['student_email']); ?></span>
                    </div>
                </div>
            </div>

            <div>
                <nav class="nav-links">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="my-events.php" class="active">My Events</a>
                    <a href="profile.php">Profile Settings</a>
                    <a href="../index.php">Home</a>
                    <a href="../backend/logout.php">Logout</a>
                </nav>
            </div>
        </aside>

        <main class="content">
            <section class="topbar">
                <div>
                    <h1>My Events</h1>
                    <p class="muted">Track all of your event requests and manage pending registrations from one page.</p>
                </div>
                <div class="topbar-actions">
                    <a class="button-link ghost" href="dashboard.php">Back to Dashboard</a>
                </div>
            </section>

            <?php if ($message !== ''): ?>
                <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
            <?php endif; ?>

            <section class="stats-grid">
                <article class="stat-card">
                    <span>Total Registered</span>
                    <strong><?= e((string) ((int) $summary['total_registered'])); ?></strong>
                    <small>All submitted registrations</small>
                </article>
                <article class="stat-card">
                    <span>Approved</span>
                    <strong><?= e((string) ((int) $summary['approved_count'])); ?></strong>
                    <small>Ready to attend</small>
                </article>
                <article class="stat-card">
                    <span>Pending</span>
                    <strong><?= e((string) ((int) $summary['pending_count'])); ?></strong>
                    <small>Waiting for admin review</small>
                </article>
                <article class="stat-card">
                    <span>Rejected</span>
                    <strong><?= e((string) ((int) $summary['rejected_count'])); ?></strong>
                    <small>Not approved</small>
                </article>
            </section>

            <section class="panel">
                <div class="section-head">
                    <h2>Registration Overview</h2>
                    <p class="muted">A cleaner picture of where you stand across all university events.</p>
                </div>
                <div class="pill-row">
                    <span class="pill">Approved events are ready for participation</span>
                    <span class="pill">Pending registrations can still be cancelled</span>
                    <span class="pill">Rejected entries stay visible for reference</span>
                </div>
            </section>

            <section class="panel">
                <div class="section-head">
                    <h2>Registered Events</h2>
                    <p class="muted">Your complete registration list with action support for pending items.</p>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Venue</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$registrations || $registrations->num_rows === 0): ?>
                                <tr>
                                    <td colspan="6">You have not registered for any event yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($registration = $registrations->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($registration['title']); ?></strong><br>
                                            <span class="muted"><?= e($registration['description'] ?: 'No description available.'); ?></span>
                                        </td>
                                        <td><?= e(date('d M Y', strtotime($registration['event_date']))); ?></td>
                                        <td><?= e($registration['venue']); ?></td>
                                        <td><span class="status-badge status-<?= e($registration['status']); ?>"><?= e(ucfirst($registration['status'])); ?></span></td>
                                        <td><?= e(date('d M Y', strtotime($registration['registered_at']))); ?></td>
                                        <td>
                                            <?php if ($registration['status'] === 'pending'): ?>
                                                <form method="post" class="mini-form">
                                                    <input type="hidden" name="action" value="cancel_registration">
                                                    <input type="hidden" name="registration_id" value="<?= e((string) $registration['registration_id']); ?>">
                                                    <button type="submit" class="small-btn danger-btn">Cancel</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="muted">No action</span>
                                            <?php endif; ?>
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
