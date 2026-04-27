<?php
require_once __DIR__ . '/../backend/db.php';
requireStudentAuth();

$studentId = (int) $_SESSION['student_id'];
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $registrationId = (int) ($_POST['registration_id'] ?? 0);

    if (!verifyCsrfToken()) {
        $message = 'Invalid form request. Please try again.';
        $messageType = 'error';
    } elseif ($action === 'cancel_registration') {
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
            e.event_id, e.title, e.description, e.image_path, e.category, e.event_date, e.event_time,
            e.registration_deadline, e.venue, e.capacity
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
    <title>My Events | University Club Event Management</title>
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
                    <p class="muted">A cleaner picture of where you stand across all club events.</p>
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
                <div class="showcase-grid">
                    <?php if (!$registrations || $registrations->num_rows === 0): ?>
                        <div class="empty-state">You have not registered for any event yet.</div>
                    <?php else: ?>
                        <?php while ($registration = $registrations->fetch_assoc()): ?>
                            <article class="event-media-card">
                                <div class="event-thumb">
                                    <img src="<?= eventImageUrl($registration['image_path'], '../'); ?>" alt="<?= e($registration['title']); ?>">
                                </div>
                                <div class="event-body">
                                    <h3><?= e($registration['title']); ?></h3>
                                    <p><?= e($registration['description'] ?: 'No description available.'); ?></p>
                                    <div class="meta-list">
                                        <span>Date: <?= e(date('d M Y', strtotime($registration['event_date']))); ?></span>
                                        <span>Category: <?= e($registration['category']); ?></span>
                                        <span>Time: <?= $registration['event_time'] ? e(date('h:i A', strtotime($registration['event_time']))) : 'TBA'; ?></span>
                                        <span>Venue: <?= e($registration['venue']); ?></span>
                                        <span>Requested: <?= e(date('d M Y', strtotime($registration['registered_at']))); ?></span>
                                    </div>
                                    <div class="table-actions">
                                        <a class="button-link ghost small-btn" href="event-details.php?id=<?= e((string) $registration['event_id']); ?>">Details</a>
                                        <span class="status-badge status-<?= e($registration['status']); ?>"><?= e(ucfirst($registration['status'])); ?></span>
                                        <?php if ($registration['status'] === 'pending'): ?>
                                            <form method="post" class="mini-form">
                                                <?= csrfField(); ?>
                                                <input type="hidden" name="action" value="cancel_registration">
                                                <input type="hidden" name="registration_id" value="<?= e((string) $registration['registration_id']); ?>">
                                                <button type="submit" class="small-btn danger-btn">Cancel Request</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
