<?php
require_once __DIR__ . '/backend/db.php';

$stats = [
    'clubs' => 0,
    'events' => 0,
    'students' => 0,
    'registrations' => 0,
    'approved' => 0,
];

$statQueries = [
    'clubs' => 'SELECT COUNT(*) AS total FROM clubs WHERE status = "active"',
    'events' => 'SELECT COUNT(*) AS total FROM events',
    'students' => 'SELECT COUNT(*) AS total FROM students',
    'registrations' => 'SELECT COUNT(*) AS total FROM registrations',
    'approved' => 'SELECT COUNT(*) AS total FROM registrations WHERE status = "approved"',
];

foreach ($statQueries as $key => $query) {
    $result = $conn->query($query);
    if ($result) {
        $stats[$key] = (int) $result->fetch_assoc()['total'];
    }
}

$featuredEvents = $conn->query(
    'SELECT e.event_id, e.title, e.description, e.image_path, e.category, e.event_date, e.event_time,
            e.registration_deadline, e.venue, e.capacity, c.name AS club_name,
            COUNT(CASE WHEN r.status IN ("pending", "approved") THEN 1 END) AS total_registered
     FROM events e
     LEFT JOIN clubs c ON c.club_id = e.club_id
     LEFT JOIN registrations r ON r.event_id = e.event_id
     GROUP BY e.event_id, e.title, e.description, e.image_path, e.category, e.event_date,
              e.event_time, e.registration_deadline, e.venue, e.capacity, c.name
     ORDER BY e.event_date ASC
     LIMIT 3'
);

$categoryStats = $conn->query(
    'SELECT category, COUNT(*) AS total
     FROM events
     GROUP BY category
     ORDER BY total DESC, category ASC
     LIMIT 6'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Club Event Management</title>
    <link rel="stylesheet" href="student/style.css">
</head>
<body class="website-body">
    <header class="site-header">
        <div class="site-shell site-header-inner">
            <a class="brand-row site-brand" href="index.php">
                <img src="assets/images/puc_logo.png" alt="PUC Logo" class="brand-logo">
                <div class="brand-copy">
                    <strong>University Club Event Management</strong>
                    <span>Events, registration, approvals, reports</span>
                </div>
            </a>

            <nav class="site-menu" aria-label="Main navigation">
                <a href="index.php" class="active">Home</a>
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

    <main>
        <section class="website-hero">
            <div class="site-shell website-hero-grid">
                <div class="website-hero-copy">
                    <p class="eyebrow">Academic Event Portal</p>
                    <h1>A complete University Club Event Management platform.</h1>
                    <p>
                        Publish campus events, manage registrations, approve participants, track seats,
                        and give members a clean portal for every workshop, seminar, contest, sports,
                        and cultural program.
                    </p>

                    <div class="hero-actions">
                        <a class="button-link" href="events.php">Explore Events</a>
                        <a class="button-link ghost" href="student/register.php">Create Member Account</a>
                        <a class="button-link secondary" href="admin/admin-login.php">Manage as Admin</a>
                    </div>
                </div>

                <div class="hero-stage" aria-label="System preview">
                    <div class="stage-card primary-stage">
                        <span>Live Dashboard</span>
                        <strong><?= e((string) $stats['events']); ?> Events</strong>
                        <p><?= e((string) $stats['registrations']); ?> registration requests tracked in the system.</p>
                    </div>
                    <div class="stage-card">
                        <span>Member Portal</span>
                        <strong>Search, register, track</strong>
                        <p>Members can browse events, submit requests, and monitor approval status.</p>
                    </div>
                    <div class="stage-card">
                        <span>Admin Control</span>
                        <strong><?= e((string) $stats['approved']); ?> Approved Seats</strong>
                        <p>Admins can review participants and analyze event performance.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-block">
            <div class="site-shell">
                <div class="section-head split-head">
                    <div>
                        <p class="eyebrow">Upcoming Events</p>
                        <h2>Browse active university programs</h2>
                    </div>
                    <a class="button-link ghost" href="events.php">View All Events</a>
                </div>

                <div class="showcase-grid">
                    <?php if (!$featuredEvents || $featuredEvents->num_rows === 0): ?>
                        <div class="empty-state">No events have been published yet.</div>
                    <?php else: ?>
                        <?php while ($event = $featuredEvents->fetch_assoc()): ?>
                            <?php $remaining = max((int) $event['capacity'] - (int) $event['total_registered'], 0); ?>
                            <article class="event-media-card">
                                <div class="event-thumb">
                                    <img src="<?= eventImageUrl($event['image_path']); ?>" alt="<?= e($event['title']); ?>">
                                </div>
                                <div class="event-body">
                                    <span class="status-badge status-pending"><?= e($event['category']); ?></span>
                                    <h3><?= e($event['title']); ?></h3>
                                    <p><?= e($event['description'] ?: 'University event details will appear here.'); ?></p>
                                    <div class="meta-list">
                                        <span><?= e($event['club_name'] ?: 'Unassigned Club'); ?></span>
                                        <span><?= e(date('d M Y', strtotime($event['event_date']))); ?><?= $event['event_time'] ? ' at ' . e(date('h:i A', strtotime($event['event_time']))) : ''; ?></span>
                                        <span><?= e($event['venue']); ?></span>
                                        <span><?= e((string) $remaining); ?> seats left</span>
                                    </div>
                                    <a class="button-link ghost small-btn" href="event-details.php?id=<?= e((string) $event['event_id']); ?>">View Details</a>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="section-band">
            <div class="site-shell">
                <div class="section-head center-head">
                    <p class="eyebrow">System Modules</p>
                    <h2>Everything a club event workflow needs</h2>
                    <p class="muted">The project now has a real public website, student portal, and admin workspace.</p>
                </div>
                <div class="feature-grid module-grid">
                    <article class="feature-card">
                        <span class="feature-icon">01</span>
                        <h3>Public Event Website</h3>
                        <p>Visitors can browse events, open detail pages, and understand how the system works.</p>
                    </article>
                    <article class="feature-card">
                        <span class="feature-icon">02</span>
                        <h3>Member Portal</h3>
                        <p>Members can register, search by category, apply for seats, and track approval history.</p>
                    </article>
                    <article class="feature-card">
                        <span class="feature-icon">03</span>
                        <h3>Admin Management</h3>
                        <p>Admins can create events, upload images, set deadlines, approve participants, and edit data.</p>
                    </article>
                    <article class="feature-card">
                        <span class="feature-icon">04</span>
                        <h3>Reports & Analytics</h3>
                        <p>Category performance, seat usage, active members, and pending requests are visible.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section-block">
            <div class="site-shell">
                <div class="home-dashboard-strip">
                    <div>
                        <p class="eyebrow">At a Glance</p>
                        <h2>Current system activity</h2>
                    </div>
                    <div class="hero-metrics website-metrics">
                        <div class="metric-card">
                            <strong><?= e((string) $stats['clubs']); ?></strong>
                            <span>Active Clubs</span>
                        </div>
                        <div class="metric-card">
                            <strong><?= e((string) $stats['events']); ?></strong>
                            <span>Events</span>
                        </div>
                        <div class="metric-card">
                            <strong><?= e((string) $stats['registrations']); ?></strong>
                            <span>Requests</span>
                        </div>
                        <div class="metric-card">
                            <strong><?= e((string) $stats['approved']); ?></strong>
                            <span>Approved</span>
                        </div>
                    </div>
                </div>

                <div class="category-strip">
                    <?php if (!$categoryStats || $categoryStats->num_rows === 0): ?>
                        <span class="pill">No categories yet</span>
                    <?php else: ?>
                        <?php while ($category = $categoryStats->fetch_assoc()): ?>
                            <span class="pill"><?= e($category['category'] ?: 'General'); ?>: <?= e((string) $category['total']); ?></span>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer-band website-footer">
        <div class="site-shell">
            <span>University Club Event Management</span>
            <span>PHP, MySQL, HTML, CSS</span>
        </div>
    </footer>
</body>
</html>
