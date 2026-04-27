<?php
require_once __DIR__ . '/backend/db.php';

$clubs = $conn->query(
    'SELECT c.club_id, c.name, c.category, c.advisor_name, c.advisor_email, c.description, c.logo_path,
            COUNT(DISTINCT e.event_id) AS total_events,
            COUNT(DISTINCT CASE WHEN e.event_date >= CURDATE() THEN e.event_id END) AS upcoming_events
     FROM clubs c
     LEFT JOIN events e ON e.club_id = c.club_id
     WHERE c.status = "active"
     GROUP BY c.club_id, c.name, c.category, c.advisor_name, c.advisor_email, c.description, c.logo_path
     ORDER BY c.name ASC'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clubs | University Club Event Management</title>
    <link rel="stylesheet" href="student/style.css">
</head>
<body class="website-body">
    <header class="site-header">
        <div class="site-shell site-header-inner">
            <a class="brand-row site-brand" href="index.php">
                <img src="assets/images/puc_logo.png" alt="PUC Logo" class="brand-logo">
                <div class="brand-copy">
                    <strong>University Club Event Management</strong>
                    <span>Official clubs and activities</span>
                </div>
            </a>
            <nav class="site-menu" aria-label="Main navigation">
                <a href="index.php">Home</a>
                <a href="clubs.php" class="active">Clubs</a>
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

    <main class="site-shell page-main">
        <section class="page-title">
            <p class="eyebrow">University Clubs</p>
            <h1>Explore active clubs before joining their events.</h1>
            <p class="muted">Each club profile includes its category, advisor, purpose, and event activity.</p>
        </section>

        <section class="showcase-grid">
            <?php if (!$clubs || $clubs->num_rows === 0): ?>
                <div class="empty-state">No active clubs have been published yet.</div>
            <?php else: ?>
                <?php while ($club = $clubs->fetch_assoc()): ?>
                    <article class="club-card">
                        <div class="club-card-head">
                            <img src="<?= eventImageUrl($club['logo_path']); ?>" alt="<?= e($club['name']); ?>">
                            <div>
                                <span class="status-badge status-pending"><?= e($club['category']); ?></span>
                                <h3><?= e($club['name']); ?></h3>
                                <p><?= e($club['advisor_name'] ?: 'Advisor not assigned'); ?></p>
                            </div>
                        </div>
                        <p><?= e($club['description'] ?: 'Club description will be added soon.'); ?></p>
                        <div class="meta-list">
                            <span>Total events: <?= e((string) $club['total_events']); ?></span>
                            <span>Upcoming events: <?= e((string) $club['upcoming_events']); ?></span>
                            <span>Contact: <?= e($club['advisor_email'] ?: 'Not available'); ?></span>
                        </div>
                        <a class="button-link ghost small-btn" href="events.php?club_id=<?= e((string) $club['club_id']); ?>">View Club Events</a>
                    </article>
                <?php endwhile; ?>
            <?php endif; ?>
        </section>
    </main>

    <footer class="footer-band website-footer">
        <div class="site-shell">
            <span>University Club Event Management</span>
            <span>Clubs</span>
        </div>
    </footer>
</body>
</html>
