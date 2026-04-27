<?php
require_once __DIR__ . '/backend/db.php';

$eventId = (int) ($_GET['id'] ?? 0);
$eventStmt = $conn->prepare(
    'SELECT e.event_id, e.title, e.description, e.image_path, e.category, e.event_date, e.event_time,
            e.registration_deadline, e.venue, e.capacity, c.name AS club_name,
            COUNT(CASE WHEN r.status IN ("pending", "approved") THEN 1 END) AS total_registered
     FROM events e
     LEFT JOIN clubs c ON c.club_id = e.club_id
     LEFT JOIN registrations r ON r.event_id = e.event_id
     WHERE e.event_id = ?
     GROUP BY e.event_id, e.title, e.description, e.image_path, e.category, e.event_date,
              e.event_time, e.registration_deadline, e.venue, e.capacity, c.name
     LIMIT 1'
);
$eventStmt->bind_param('i', $eventId);
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
    <link rel="stylesheet" href="student/style.css">
</head>
<body class="website-body">
    <header class="site-header">
        <div class="site-shell site-header-inner">
            <a class="brand-row site-brand" href="index.php">
                <img src="assets/images/puc_logo.png" alt="PUC Logo" class="brand-logo">
                <div class="brand-copy">
                    <strong>University Club Event Management</strong>
                    <span>Event details</span>
                </div>
            </a>
            <nav class="site-menu" aria-label="Main navigation">
                <a href="index.php">Home</a>
                <a href="clubs.php">Clubs</a>
                <a href="events.php">Events</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
            </nav>
            <div class="nav-actions">
                <a class="button-link ghost" href="student/login.php">Member Login</a>
                <a class="button-link secondary" href="admin/admin-login.php">Admin Panel</a>
            </div>
        </div>
    </header>

    <main class="site-shell detail-page">
        <?php if (!$event): ?>
            <section class="panel">
                <h1>Event not found</h1>
                <p class="muted">The event may have been removed or the link is incorrect.</p>
                <div class="hero-actions">
                    <a class="button-link" href="index.php">Back to Home</a>
                </div>
            </section>
        <?php else: ?>
            <section class="detail-hero public-detail">
                <div class="detail-media">
                    <img src="<?= eventImageUrl($event['image_path']); ?>" alt="<?= e($event['title']); ?>">
                </div>
                <div class="detail-copy">
                    <span class="status-badge status-pending"><?= e($event['category']); ?></span>
                    <h1><?= e($event['title']); ?></h1>
                    <p><?= e($event['description'] ?: 'No description has been added for this event yet.'); ?></p>
                    <div class="detail-facts">
                        <span>Club: <?= e($event['club_name'] ?: 'Unassigned'); ?></span>
                        <span>Date: <?= e(date('d M Y', strtotime($event['event_date']))); ?></span>
                        <span>Time: <?= $event['event_time'] ? e(date('h:i A', strtotime($event['event_time']))) : 'TBA'; ?></span>
                        <span>Venue: <?= e($event['venue']); ?></span>
                        <span>Deadline: <?= $event['registration_deadline'] ? e(date('d M Y', strtotime($event['registration_deadline']))) : 'Open until seats fill'; ?></span>
                    </div>
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
                        <?php if ($isDeadlineOpen && $remaining > 0): ?>
                            <a class="button-link" href="student/login.php">Login to Register</a>
                        <?php else: ?>
                            <span class="status-badge status-rejected"><?= $remaining <= 0 ? 'Seats Full' : 'Registration Closed'; ?></span>
                        <?php endif; ?>
                        <a class="button-link ghost" href="index.php">Back to Home</a>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
