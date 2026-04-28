<?php
require_once __DIR__ . '/../backend/db.php';
requireClubAdminAuth();

$clubId = (int) $_SESSION['club_admin_club_id'];

$stats = [
    'events' => 0,
    'members' => 0,
    'registrations' => 0,
    'pending_registrations' => 0,
];

$statsQueries = [
    'events' => 'SELECT COUNT(*) AS total FROM events WHERE club_id = ?',
    'members' => 'SELECT COUNT(*) AS total FROM club_memberships WHERE club_id = ? AND status = "approved"',
    'registrations' => 'SELECT COUNT(r.registration_id) AS total FROM registrations r INNER JOIN events e ON e.event_id = r.event_id WHERE e.club_id = ?',
    'pending_registrations' => 'SELECT COUNT(r.registration_id) AS total FROM registrations r INNER JOIN events e ON e.event_id = r.event_id WHERE e.club_id = ? AND r.status = "pending"',
];

foreach ($statsQueries as $key => $query) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $clubId);
    $stmt->execute();
    $stats[$key] = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();
}

$clubStmt = $conn->prepare('SELECT name, category, advisor_name, advisor_email, description, logo_path, status FROM clubs WHERE club_id = ? LIMIT 1');
$clubStmt->bind_param('i', $clubId);
$clubStmt->execute();
$club = $clubStmt->get_result()->fetch_assoc();
$clubStmt->close();

$eventsStmt = $conn->prepare(
    'SELECT e.event_id, e.title, e.image_path, e.category, e.event_date, e.event_time, e.venue, e.capacity,
            COUNT(CASE WHEN r.status IN ("pending", "approved") THEN 1 END) AS total_registered
     FROM events e
     LEFT JOIN registrations r ON r.event_id = e.event_id
     WHERE e.club_id = ?
     GROUP BY e.event_id, e.title, e.image_path, e.category, e.event_date, e.event_time, e.venue, e.capacity
     ORDER BY e.event_date ASC
     LIMIT 6'
);
$eventsStmt->bind_param('i', $clubId);
$eventsStmt->execute();
$events = $eventsStmt->get_result();

$requestsStmt = $conn->prepare(
    'SELECT r.status, s.name AS student_name, e.title AS event_title, r.registered_at
     FROM registrations r
     INNER JOIN students s ON s.student_id = r.student_id
     INNER JOIN events e ON e.event_id = r.event_id
     WHERE e.club_id = ?
     ORDER BY r.registered_at DESC
     LIMIT 6'
);
$requestsStmt->bind_param('i', $clubId);
$requestsStmt->execute();
$recentRequests = $requestsStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Admin Dashboard | University Club Event Management</title>
    <link rel="stylesheet" href="../student/style.css">
</head>
<body class="app-page">
    <div class="app-shell">
        <aside class="sidebar club-sidebar">
            <div>
                <div class="brand-row">
                    <img src="../assets/images/puc_logo.png" alt="PUC Logo" class="brand-logo">
                    <div class="brand-copy">
                        <strong>University Club Event Management</strong>
                        <span>Club Admin Panel</span>
                    </div>
                </div>
                <div class="sidebar-card" style="margin-top: 22px;">
                    <p class="eyebrow">Club Admin</p>
                    <div class="profile-meta">
                        <strong><?= e($_SESSION['club_admin_name']); ?></strong>
                        <span><?= e($_SESSION['club_admin_club_name']); ?></span>
                    </div>
                </div>
            </div>
            <nav class="nav-links">
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="create-event.php">Create Event</a>
                <a href="manage-events.php">Manage Events</a>
                <a href="participants.php">Participants</a>
                <a href="members.php">Club Members</a>
                <a href="profile.php">Club Profile</a>
                <a href="../index.php">Home</a>
                <a href="../backend/logout.php?club=1">Logout</a>
            </nav>
        </aside>

        <main class="content">
            <section class="topbar">
                <div>
                    <h1><?= e($_SESSION['club_admin_club_name']); ?> Dashboard</h1>
                    <p class="muted">Manage only this club's events, participants, members, and club information.</p>
                </div>
                <div class="topbar-actions">
                    <a class="button-link" href="create-event.php">Create Event</a>
                    <a class="button-link ghost" href="participants.php">Review Requests</a>
                </div>
            </section>

            <section class="stats-grid">
                <article class="stat-card"><span>Club Events</span><strong><?= e((string) $stats['events']); ?></strong><small>Created under this club</small></article>
                <article class="stat-card"><span>Approved Members</span><strong><?= e((string) $stats['members']); ?></strong><small>Official club members</small></article>
                <article class="stat-card"><span>Event Registrations</span><strong><?= e((string) $stats['registrations']); ?></strong><small>All event requests</small></article>
                <article class="stat-card"><span>Pending Requests</span><strong><?= e((string) $stats['pending_registrations']); ?></strong><small>Need review</small></article>
            </section>

            <div class="panel-grid">
                <section class="panel">
                    <div class="section-head">
                        <h2>Club Profile</h2>
                        <p class="muted">This information is visible to members and public visitors.</p>
                    </div>
                    <article class="club-card">
                        <div class="club-card-head">
                            <img src="<?= eventImageUrl($club['logo_path'] ?? null, '../'); ?>" alt="<?= e($club['name'] ?? 'Club'); ?>">
                            <div>
                                <span class="status-badge status-<?= ($club['status'] ?? 'active') === 'active' ? 'success' : 'rejected'; ?>"><?= e(ucfirst($club['status'] ?? 'active')); ?></span>
                                <h3><?= e($club['name'] ?? $_SESSION['club_admin_club_name']); ?></h3>
                                <p><?= e($club['category'] ?? 'General'); ?></p>
                            </div>
                        </div>
                        <p><?= e($club['description'] ?: 'No club description added yet.'); ?></p>
                        <div class="meta-list">
                            <span>Advisor: <?= e($club['advisor_name'] ?: 'Not assigned'); ?></span>
                            <span>Email: <?= e($club['advisor_email'] ?: 'Not added'); ?></span>
                        </div>
                    </article>
                </section>

                <section class="panel">
                    <div class="section-head">
                        <h2>Recent Requests</h2>
                        <p class="muted">Latest participant activity for this club.</p>
                    </div>
                    <div class="list-stack">
                        <?php if (!$recentRequests || $recentRequests->num_rows === 0): ?>
                            <div class="empty-state">No participant requests yet.</div>
                        <?php else: ?>
                            <?php while ($request = $recentRequests->fetch_assoc()): ?>
                                <div class="list-item">
                                    <div>
                                        <strong><?= e($request['student_name']); ?></strong>
                                        <span><?= e($request['event_title']); ?></span>
                                    </div>
                                    <span class="status-badge status-<?= e($request['status']); ?>"><?= e(ucfirst($request['status'])); ?></span>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <section class="panel">
                <div class="section-head">
                    <h2>Club Events</h2>
                    <p class="muted">Upcoming and published events from this club.</p>
                </div>
                <div class="showcase-grid">
                    <?php if (!$events || $events->num_rows === 0): ?>
                        <div class="empty-state">No events created for this club yet.</div>
                    <?php else: ?>
                        <?php while ($event = $events->fetch_assoc()): ?>
                            <article class="event-media-card">
                                <div class="event-thumb">
                                    <img src="<?= eventImageUrl($event['image_path'], '../'); ?>" alt="<?= e($event['title']); ?>">
                                </div>
                                <div class="event-body">
                                    <h3><?= e($event['title']); ?></h3>
                                    <div class="meta-list">
                                        <span><?= e($event['category']); ?> | <?= e(date('d M Y', strtotime($event['event_date']))); ?><?= $event['event_time'] ? ' at ' . e(date('h:i A', strtotime($event['event_time']))) : ''; ?></span>
                                        <span>Venue: <?= e($event['venue']); ?></span>
                                        <span>Seats: <?= e((string) $event['total_registered']); ?> / <?= e((string) $event['capacity']); ?></span>
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
