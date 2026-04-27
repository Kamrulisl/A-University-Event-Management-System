<?php
require_once __DIR__ . '/../backend/db.php';
requireStudentAuth();

$studentId = (int) $_SESSION['student_id'];
$eventId = (int) ($_GET['id'] ?? $_POST['event_id'] ?? 0);
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $eventId > 0) {
    if (!verifyCsrfToken()) {
        $message = 'Invalid form request. Please try again.';
        $messageType = 'error';
    } else {
        $capacityStmt = $conn->prepare(
            'SELECT e.capacity, e.registration_deadline,
                    (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id AND r.status IN ("pending", "approved")) AS total_registered
             FROM events e
             WHERE e.event_id = ? LIMIT 1'
        );
        $capacityStmt->bind_param('i', $eventId);
        $capacityStmt->execute();
        $capacityData = $capacityStmt->get_result()->fetch_assoc();
        $capacityStmt->close();

        if (!$capacityData) {
            $message = 'Event not found.';
            $messageType = 'error';
        } elseif ((int) $capacityData['total_registered'] >= (int) $capacityData['capacity']) {
            $message = 'This event is already full.';
            $messageType = 'error';
        } elseif (!registrationDeadlineOpen($capacityData['registration_deadline'] ?? null)) {
            $message = 'Registration deadline for this event has passed.';
            $messageType = 'error';
        } else {
            $registerStmt = $conn->prepare('INSERT IGNORE INTO registrations (student_id, event_id) VALUES (?, ?)');
            $registerStmt->bind_param('ii', $studentId, $eventId);
            $registerStmt->execute();

            if ($registerStmt->affected_rows > 0) {
                $message = 'Registration request submitted.';
            } else {
                $message = 'You already registered for this event.';
                $messageType = 'error';
            }

            $registerStmt->close();
        }
    }
}

$eventStmt = $conn->prepare(
    'SELECT e.event_id, e.title, e.description, e.image_path, e.category, e.event_date, e.event_time,
            e.registration_deadline, e.venue, e.capacity, e.created_at,
            COUNT(CASE WHEN r.status IN ("pending", "approved") THEN 1 END) AS total_registered,
            SUM(CASE WHEN r.status = "approved" THEN 1 ELSE 0 END) AS approved_count,
            SUM(CASE WHEN r.status = "pending" THEN 1 ELSE 0 END) AS pending_count,
            MAX(CASE WHEN my.student_id IS NOT NULL THEN my.status ELSE NULL END) AS my_status
     FROM events e
     LEFT JOIN registrations r ON r.event_id = e.event_id
     LEFT JOIN registrations my ON my.event_id = e.event_id AND my.student_id = ?
     WHERE e.event_id = ?
     GROUP BY e.event_id, e.title, e.description, e.image_path, e.category, e.event_date,
              e.event_time, e.registration_deadline, e.venue, e.capacity, e.created_at
     LIMIT 1'
);
$eventStmt->bind_param('ii', $studentId, $eventId);
$eventStmt->execute();
$event = $eventStmt->get_result()->fetch_assoc();
$eventStmt->close();

if (!$event) {
    http_response_code(404);
}

$capacity = (int) ($event['capacity'] ?? 0);
$totalRegistered = (int) ($event['total_registered'] ?? 0);
$remaining = max($capacity - $totalRegistered, 0);
$seatPercent = $capacity > 0 ? min(100, round(($totalRegistered / $capacity) * 100)) : 0;
$isDeadlineOpen = $event ? registrationDeadlineOpen($event['registration_deadline'] ?? null) : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $event ? e($event['title']) : 'Event Not Found'; ?> | University Club Event Management</title>
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
                    <a href="profile.php">Profile Settings</a>
                    <a href="../index.php">Home</a>
                    <a href="../backend/logout.php">Logout</a>
                </nav>
            </div>
        </aside>

        <main class="content">
            <?php if (!$event): ?>
                <section class="panel">
                    <h1>Event not found</h1>
                    <p class="muted">The event may have been removed or the link is incorrect.</p>
                    <div class="hero-actions">
                        <a class="button-link" href="dashboard.php">Back to Dashboard</a>
                    </div>
                </section>
            <?php else: ?>
                <section class="detail-hero">
                    <div class="detail-media">
                        <img src="<?= eventImageUrl($event['image_path'], '../'); ?>" alt="<?= e($event['title']); ?>">
                    </div>
                    <div class="detail-copy">
                        <span class="status-badge status-pending"><?= e($event['category']); ?></span>
                        <h1><?= e($event['title']); ?></h1>
                        <p><?= e($event['description'] ?: 'No description has been added for this event yet.'); ?></p>
                        <div class="detail-facts">
                            <span>Date: <?= e(date('d M Y', strtotime($event['event_date']))); ?></span>
                            <span>Time: <?= $event['event_time'] ? e(date('h:i A', strtotime($event['event_time']))) : 'TBA'; ?></span>
                            <span>Venue: <?= e($event['venue']); ?></span>
                            <span>Deadline: <?= $event['registration_deadline'] ? e(date('d M Y', strtotime($event['registration_deadline']))) : 'Open until seats fill'; ?></span>
                        </div>

                        <?php if ($message !== ''): ?>
                            <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
                        <?php endif; ?>

                        <div class="seat-meter">
                            <div class="seat-meter-top">
                                <strong><?= e((string) $remaining); ?> seats left</strong>
                                <span><?= e((string) $totalRegistered); ?> / <?= e((string) $capacity); ?> requested</span>
                            </div>
                            <div class="progress-track">
                                <span style="width: <?= e((string) $seatPercent); ?>%;"></span>
                            </div>
                        </div>

                        <div class="hero-actions">
                            <?php if ($event['my_status'] !== null): ?>
                                <span class="status-badge status-<?= e($event['my_status']); ?>"><?= e(ucfirst($event['my_status'])); ?></span>
                            <?php elseif (!$isDeadlineOpen): ?>
                                <span class="status-badge status-rejected">Registration Closed</span>
                            <?php elseif ($remaining > 0): ?>
                                <form method="post">
                                    <?= csrfField(); ?>
                                    <input type="hidden" name="event_id" value="<?= e((string) $event['event_id']); ?>">
                                    <button type="submit">Register for this Event</button>
                                </form>
                            <?php else: ?>
                                <span class="status-badge status-rejected">Seats Full</span>
                            <?php endif; ?>
                            <a class="button-link ghost" href="dashboard.php">Back to Events</a>
                        </div>
                    </div>
                </section>

                <section class="stats-grid">
                    <article class="stat-card">
                        <span>Approved</span>
                        <strong><?= e((string) ((int) $event['approved_count'])); ?></strong>
                        <small>Confirmed participants</small>
                    </article>
                    <article class="stat-card">
                        <span>Pending</span>
                        <strong><?= e((string) ((int) $event['pending_count'])); ?></strong>
                        <small>Awaiting admin review</small>
                    </article>
                    <article class="stat-card">
                        <span>Capacity</span>
                        <strong><?= e((string) $capacity); ?></strong>
                        <small>Total allowed seats</small>
                    </article>
                    <article class="stat-card">
                        <span>Status</span>
                        <strong><?= $isDeadlineOpen ? 'Open' : 'Closed'; ?></strong>
                        <small>Registration window</small>
                    </article>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
