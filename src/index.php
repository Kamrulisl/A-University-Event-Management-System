<?php
require_once __DIR__ . '/backend/db.php';

$stats = [
    'events' => 0,
    'students' => 0,
    'registrations' => 0,
];

$statQueries = [
    'events' => 'SELECT COUNT(*) AS total FROM events',
    'students' => 'SELECT COUNT(*) AS total FROM students',
    'registrations' => 'SELECT COUNT(*) AS total FROM registrations',
];

foreach ($statQueries as $key => $query) {
    $result = $conn->query($query);
    if ($result) {
        $stats[$key] = (int) $result->fetch_assoc()['total'];
    }
}

$featuredEvents = $conn->query(
    'SELECT title, description, event_date, venue, capacity
     FROM events
     ORDER BY event_date ASC
     LIMIT 3'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premier University Event Management System</title>
    <link rel="stylesheet" href="student/style.css">
</head>
<body>
    <header class="top-nav">
        <div class="site-shell">
            <div class="brand-row">
                <img src="assets/images/puc_logo.png" alt="Premier University Logo" class="brand-logo">
                <div class="brand-copy">
                    <strong>Premier University</strong>
                    <span>Event Management System</span>
                </div>
            </div>

            <div class="nav-actions">
                <a class="button-link ghost" href="student/login.php">Student Login</a>
                <a class="button-link" href="student/register.php">Student Registration</a>
                <a class="button-link secondary" href="admin/admin-login.php">Admin Panel</a>
            </div>
        </div>
    </header>

    <main>
        <section class="hero-section">
            <div class="site-shell">
                <div class="hero-layout">
                    <div class="hero-copy">
                        <p class="eyebrow">Premier University</p>
                        <h1>University events, registrations, and approvals in one place.</h1>
                        <p>
                            This platform helps Premier University students discover events, register quickly,
                            and track approval status while admins create programs and manage participants smoothly.
                        </p>

                        <div class="hero-actions">
                            <a class="button-link" href="student/register.php">Join as Student</a>
                            <a class="button-link ghost" href="student/login.php">Browse from Dashboard</a>
                            <a class="button-link secondary" href="admin/admin-login.php">Admin Login</a>
                        </div>

                        <div class="hero-metrics">
                            <div class="metric-card">
                                <strong><?= e((string) $stats['events']); ?></strong>
                                <span>Published Events</span>
                            </div>
                            <div class="metric-card">
                                <strong><?= e((string) $stats['students']); ?></strong>
                                <span>Registered Students</span>
                            </div>
                            <div class="metric-card">
                                <strong><?= e((string) $stats['registrations']); ?></strong>
                                <span>Total Registrations</span>
                            </div>
                        </div>
                    </div>

                    <aside class="hero-panel">
                        <div class="hero-panel-header">
                            <img src="assets/images/puc_logo.png" alt="Premier University Logo" class="brand-logo small">
                            <div>
                                <h2>Upcoming Highlights</h2>
                                <p class="muted">A quick view of current university activities.</p>
                            </div>
                        </div>

                        <div class="hero-events">
                            <?php if (!$featuredEvents || $featuredEvents->num_rows === 0): ?>
                                <div class="empty-state">No events have been published yet.</div>
                            <?php else: ?>
                                <?php while ($event = $featuredEvents->fetch_assoc()): ?>
                                    <article class="hero-event-item">
                                        <h3><?= e($event['title']); ?></h3>
                                        <div class="hero-event-meta">
                                            <span><?= e(date('d M Y', strtotime($event['event_date']))); ?></span>
                                            <span><?= e($event['venue']); ?></span>
                                            <span>Capacity: <?= e((string) $event['capacity']); ?></span>
                                        </div>
                                    </article>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                    </aside>
                </div>
            </div>
        </section>

        <section class="section-block">
            <div class="site-shell">
                <div class="section-head">
                    <h2>Built for Students and Admins</h2>
                    <p class="muted">Everything important stays close to the work: events, registrations, and approvals.</p>
                </div>
                <div class="feature-grid">
                    <article class="feature-card">
                        <h3>Student Registration</h3>
                        <p>Create an account, view upcoming programs, and submit event registrations from one dashboard.</p>
                    </article>
                    <article class="feature-card">
                        <h3>Event Publishing</h3>
                        <p>Admins can add contests, workshops, seminars, and cultural events with venue and seat limits.</p>
                    </article>
                    <article class="feature-card">
                        <h3>Participant Control</h3>
                        <p>Registration requests can be approved or rejected with clear status tracking for everyone involved.</p>
                    </article>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer-band">
        <div class="site-shell">
            <span>Premier University Event Management System</span>
            <span>Raw PHP and MySQL Project</span>
        </div>
    </footer>
</body>
</html>
