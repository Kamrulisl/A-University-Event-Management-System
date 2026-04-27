<?php
require_once __DIR__ . '/backend/db.php';

$search = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$searchLike = '%' . $search . '%';

$eventsStmt = $conn->prepare(
    'SELECT e.event_id, e.title, e.description, e.image_path, e.category, e.event_date, e.event_time,
            e.registration_deadline, e.venue, e.capacity,
            COUNT(CASE WHEN r.status IN ("pending", "approved") THEN 1 END) AS total_registered
     FROM events e
     LEFT JOIN registrations r ON r.event_id = e.event_id
     WHERE (? = "" OR e.title LIKE ? OR e.description LIKE ? OR e.venue LIKE ? OR e.category LIKE ?)
       AND (? = "" OR e.category = ?)
     GROUP BY e.event_id, e.title, e.description, e.image_path, e.category, e.event_date,
              e.event_time, e.registration_deadline, e.venue, e.capacity
     ORDER BY e.event_date ASC'
);
$eventsStmt->bind_param(
    'sssssss',
    $search,
    $searchLike,
    $searchLike,
    $searchLike,
    $searchLike,
    $categoryFilter,
    $categoryFilter
);
$eventsStmt->execute();
$events = $eventsStmt->get_result();

$categories = $conn->query(
    'SELECT DISTINCT category
     FROM events
     WHERE category IS NOT NULL AND category != ""
     ORDER BY category ASC'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events | University Club Event Management</title>
    <link rel="stylesheet" href="student/style.css">
</head>
<body class="website-body">
    <header class="site-header">
        <div class="site-shell site-header-inner">
            <a class="brand-row site-brand" href="index.php">
                <img src="assets/images/club_logo.svg" alt="University Club Event Management Logo" class="brand-logo">
                <div class="brand-copy">
                    <strong>University Club Event Management</strong>
                    <span>Public event catalog</span>
                </div>
            </a>
            <nav class="site-menu" aria-label="Main navigation">
                <a href="index.php">Home</a>
                <a href="events.php" class="active">Events</a>
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
            <p class="eyebrow">Event Catalog</p>
            <h1>Find club events and open details before registration.</h1>
            <p class="muted">Search by event name, venue, description, or category. Members can log in to request a seat.</p>
        </section>

        <form method="get" class="filter-bar public-filter">
            <label>
                <span>Search</span>
                <input type="search" name="search" value="<?= e($search); ?>" placeholder="Search events, venue, category">
            </label>
            <label>
                <span>Category</span>
                <select name="category">
                    <option value="">All categories</option>
                    <?php if ($categories): ?>
                        <?php while ($category = $categories->fetch_assoc()): ?>
                            <option value="<?= e($category['category']); ?>" <?= $categoryFilter === $category['category'] ? 'selected' : ''; ?>><?= e($category['category']); ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </label>
            <div class="filter-actions">
                <button type="submit">Search</button>
                <a class="button-link ghost" href="events.php">Reset</a>
            </div>
        </form>

        <section class="showcase-grid">
            <?php if (!$events || $events->num_rows === 0): ?>
                <div class="empty-state">No events matched your search.</div>
            <?php else: ?>
                <?php while ($event = $events->fetch_assoc()): ?>
                    <?php
                    $remaining = max((int) $event['capacity'] - (int) $event['total_registered'], 0);
                    $isOpen = registrationDeadlineOpen($event['registration_deadline'] ?? null);
                    ?>
                    <article class="event-media-card">
                        <div class="event-thumb">
                            <img src="<?= eventImageUrl($event['image_path']); ?>" alt="<?= e($event['title']); ?>">
                        </div>
                        <div class="event-body">
                            <span class="status-badge status-pending"><?= e($event['category']); ?></span>
                            <h3><?= e($event['title']); ?></h3>
                            <p><?= e($event['description'] ?: 'Event details will be updated soon.'); ?></p>
                            <div class="meta-list">
                                <span>Date: <?= e(date('d M Y', strtotime($event['event_date']))); ?><?= $event['event_time'] ? ' at ' . e(date('h:i A', strtotime($event['event_time']))) : ''; ?></span>
                                <span>Venue: <?= e($event['venue']); ?></span>
                                <span>Seats left: <?= e((string) $remaining); ?> / <?= e((string) $event['capacity']); ?></span>
                                <span>Status: <?= $isOpen && $remaining > 0 ? 'Open for registration' : 'Registration closed'; ?></span>
                            </div>
                            <div class="table-actions">
                                <a class="button-link small-btn" href="event-details.php?id=<?= e((string) $event['event_id']); ?>">View Details</a>
                                <a class="button-link ghost small-btn" href="student/login.php">Login to Register</a>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php endif; ?>
        </section>
    </main>

    <footer class="footer-band website-footer">
        <div class="site-shell">
            <span>University Club Event Management</span>
            <span>Public Event Catalog</span>
        </div>
    </footer>
</body>
</html>
