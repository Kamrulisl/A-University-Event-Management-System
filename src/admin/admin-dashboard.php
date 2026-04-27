<?php
require_once __DIR__ . '/../backend/db.php';
requireAdminAuth();

$stats = [
    'clubs' => 0,
    'events' => 0,
    'students' => 0,
    'registrations' => 0,
    'pending' => 0,
];

$statQueries = [
    'clubs' => 'SELECT COUNT(*) AS total FROM clubs WHERE status = "active"',
    'events' => 'SELECT COUNT(*) AS total FROM events',
    'students' => 'SELECT COUNT(*) AS total FROM students',
    'registrations' => 'SELECT COUNT(*) AS total FROM registrations',
    'pending' => 'SELECT COUNT(*) AS total FROM registrations WHERE status = "pending"',
];

foreach ($statQueries as $key => $query) {
    $result = $conn->query($query);
    if ($result) {
        $stats[$key] = (int) $result->fetch_assoc()['total'];
    }
}

$recentEvents = $conn->query(
    'SELECT e.event_id, e.title, e.image_path, e.category, e.event_date, e.event_time, e.registration_deadline,
            e.venue, e.capacity, c.name AS club_name,
            COUNT(CASE WHEN r.status IN ("pending", "approved") THEN 1 END) AS total_registered
     FROM events e
     LEFT JOIN clubs c ON c.club_id = e.club_id
     LEFT JOIN registrations r ON r.event_id = e.event_id
     GROUP BY e.event_id, e.title, e.image_path, e.category, e.event_date, e.event_time,
              e.registration_deadline, e.venue, e.capacity, c.name
     ORDER BY event_date ASC
     LIMIT 5'
);

$recentRegistrations = $conn->query(
    'SELECT s.name AS student_name, e.title AS event_title, r.status
     FROM registrations r
     INNER JOIN students s ON s.student_id = r.student_id
     INNER JOIN events e ON e.event_id = r.event_id
     ORDER BY r.registered_at DESC
     LIMIT 5'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | University Club Event Management</title>
    <link rel="stylesheet" href="../student/style.css">
</head>
<body class="app-page">
    <div class="app-shell">
        <aside class="sidebar">
            <div>
                <div class="brand-row">
                    <img src="../assets/images/puc_logo.png" alt="PUC Logo" class="brand-logo">
                    <div class="brand-copy">
                        <strong>University Club Event Management</strong>
                        <span>Admin Event Control</span>
                    </div>
                </div>
                <div class="sidebar-card" style="margin-top: 22px;">
                    <p class="eyebrow">Administrator</p>
                    <div class="profile-meta">
                        <strong><?= e($_SESSION['admin_name']); ?></strong>
                        <span><?= e($_SESSION['admin_email']); ?></span>
                    </div>
                </div>
            </div>

            <div>
                <nav class="nav-links">
                    <a href="admin-dashboard.php" class="active">Dashboard</a>
                    <a href="manage-clubs.php">Clubs</a>
                    <a href="create-event.php">Create Event</a>
                    <a href="manage-events.php">Manage Events</a>
                    <a href="manage-students.php">Members</a>
                    <a href="manage-participants.php">Participants</a>
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
                    <h1>Hello, <?= e($_SESSION['admin_name']); ?></h1>
                    <p class="muted">Track event activity, student participation, and pending approvals from one admin workspace.</p>
                </div>
                <div class="topbar-actions">
                    <a class="button-link" href="create-event.php">Create Event</a>
                    <a class="button-link ghost" href="manage-participants.php">Review Participants</a>
                </div>
            </section>

            <section class="stats-grid">
                <article class="stat-card">
                    <span>Active Clubs</span>
                    <strong><?= e((string) $stats['clubs']); ?></strong>
                </article>
                <article class="stat-card">
                    <span>Total Events</span>
                    <strong><?= e((string) $stats['events']); ?></strong>
                </article>
                <article class="stat-card">
                    <span>Registrations</span>
                    <strong><?= e((string) $stats['registrations']); ?></strong>
                </article>
                <article class="stat-card">
                    <span>Pending Requests</span>
                    <strong><?= e((string) $stats['pending']); ?></strong>
                </article>
            </section>

            <section class="panel">
                <div class="section-head">
                    <h2>Recent Events</h2>
                    <p class="muted">Current event list with schedule and seat capacity.</p>
                </div>
                <div class="showcase-grid">
                    <?php if (!$recentEvents || $recentEvents->num_rows === 0): ?>
                        <div class="empty-state">No events available yet.</div>
                    <?php else: ?>
                        <?php while ($event = $recentEvents->fetch_assoc()): ?>
                            <article class="event-media-card">
                                <div class="event-thumb">
                                    <img src="<?= eventImageUrl($event['image_path'], '../'); ?>" alt="<?= e($event['title']); ?>">
                                </div>
                                <div class="event-body">
                                    <h3><?= e($event['title']); ?></h3>
                                    <div class="meta-list">
                                        <span>Club: <?= e($event['club_name'] ?: 'Unassigned'); ?></span>
                                        <span>Category: <?= e($event['category']); ?></span>
                                        <span>Date: <?= e(date('d M Y', strtotime($event['event_date']))); ?><?= $event['event_time'] ? ' at ' . e(date('h:i A', strtotime($event['event_time']))) : ''; ?></span>
                                        <span>Venue: <?= e($event['venue']); ?></span>
                                        <span>Seats: <?= e((string) $event['total_registered']); ?> / <?= e((string) $event['capacity']); ?></span>
                                        <?php if ($event['registration_deadline']): ?>
                                            <span>Deadline: <?= e(date('d M Y', strtotime($event['registration_deadline']))); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </section>

            <div class="quick-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                <section class="quick-card">
                    <div class="section-head">
                        <h2>Quick Actions</h2>
                        <p class="muted">Common admin work starts here.</p>
                    </div>
                    <div class="list-stack">
                        <div class="list-item">
                            <div>
                                <strong>Create and publish events</strong>
                                <span>Add title, venue, date, capacity, and description.</span>
                            </div>
                            <a class="button-link" href="create-event.php">Open</a>
                        </div>
                        <div class="list-item">
                            <div>
                                <strong>Review registration requests</strong>
                                <span>Approve or reject members for each event.</span>
                            </div>
                            <a class="button-link ghost" href="manage-participants.php">Open</a>
                        </div>
                        <div class="list-item">
                            <div>
                                <strong>Edit events and manage members</strong>
                                <span>Keep event data and student records organized.</span>
                            </div>
                            <a class="button-link secondary" href="manage-events.php">Open</a>
                        </div>
                    </div>
                </section>

                <section class="quick-card">
                    <div class="section-head">
                        <h2>Recent Registrations</h2>
                        <p class="muted">Latest member activity in the system.</p>
                    </div>
                    <div class="list-stack">
                        <?php if (!$recentRegistrations || $recentRegistrations->num_rows === 0): ?>
                            <div class="empty-state">No registration activity yet.</div>
                        <?php else: ?>
                            <?php while ($row = $recentRegistrations->fetch_assoc()): ?>
                                <div class="list-item">
                                    <div>
                                        <strong><?= e($row['student_name']); ?></strong>
                                        <span><?= e($row['event_title']); ?></span>
                                    </div>
                                    <span class="status-badge status-<?= e($row['status']); ?>"><?= e(ucfirst($row['status'])); ?></span>
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
