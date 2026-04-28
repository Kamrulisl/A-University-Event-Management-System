<?php
require_once __DIR__ . '/../backend/db.php';
requireStudentAuth();

$message = '';
$messageType = 'success';
$studentId = (int) $_SESSION['student_id'];
$search = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['event_id'])) {
    $eventId = (int) $_POST['event_id'];

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

$eventsQuery = '
    SELECT e.event_id, e.title, e.description, e.image_path, e.category, e.event_date, e.event_time,
           e.registration_deadline, e.venue, e.capacity, c.name AS club_name,
           COUNT(r.registration_id) AS total_registered,
           MAX(CASE WHEN my.student_id IS NOT NULL THEN my.status ELSE NULL END) AS my_status
    FROM events e
    LEFT JOIN clubs c
        ON c.club_id = e.club_id
    LEFT JOIN registrations r
        ON r.event_id = e.event_id
        AND r.status IN ("pending", "approved")
    LEFT JOIN registrations my
        ON my.event_id = e.event_id
        AND my.student_id = ?
    WHERE (? = "" OR e.title LIKE ? OR e.description LIKE ? OR e.venue LIKE ? OR e.category LIKE ? OR c.name LIKE ?)
      AND (? = "" OR e.category = ?)
    GROUP BY e.event_id, e.title, e.description, e.image_path, e.category, e.event_date,
             e.event_time, e.registration_deadline, e.venue, e.capacity, c.name
    ORDER BY e.event_date ASC
';

$eventsStmt = $conn->prepare($eventsQuery);
$searchLike = '%' . $search . '%';
$eventsStmt->bind_param(
    'issssssss',
    $studentId,
    $search,
    $searchLike,
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

$myRegistrationsStmt = $conn->prepare(
    'SELECT e.title, e.event_date, e.venue, r.status
     FROM registrations r
     INNER JOIN events e ON e.event_id = r.event_id
     WHERE r.student_id = ?
     ORDER BY e.event_date ASC'
);
$myRegistrationsStmt->bind_param('i', $studentId);
$myRegistrationsStmt->execute();
$myRegistrations = $myRegistrationsStmt->get_result();

$studentStatsStmt = $conn->prepare(
    'SELECT
        COUNT(*) AS total_registered,
        SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_count
     FROM registrations
     WHERE student_id = ?'
);
$studentStatsStmt->bind_param('i', $studentId);
$studentStatsStmt->execute();
$studentStats = $studentStatsStmt->get_result()->fetch_assoc() ?: [
    'total_registered' => 0,
    'approved_count' => 0,
    'pending_count' => 0,
];
$studentStatsStmt->close();

$upcomingSummaryStmt = $conn->prepare(
    'SELECT
        COUNT(*) AS total_events,
        SUM(CASE WHEN reg.registration_id IS NULL THEN 1 ELSE 0 END) AS available_events
     FROM events e
     LEFT JOIN registrations reg
        ON reg.event_id = e.event_id
        AND reg.student_id = ?'
);
$upcomingSummaryStmt->bind_param('i', $studentId);
$upcomingSummaryStmt->execute();
$upcomingSummary = $upcomingSummaryStmt->get_result()->fetch_assoc() ?: [
    'total_events' => 0,
    'available_events' => 0,
];
$upcomingSummaryStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard | University Club Event Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-page">
    <div class="app-shell">
        <aside class="sidebar">
            <div>
                <div class="brand-row">
                    <img src="../assets/images/puc_logo.png" alt="PUC Logo" class="brand-logo">
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
                    <a href="dashboard.php" class="active">Dashboard</a>
                    <a href="my-clubs.php">My Clubs</a>
                    <a href="my-events.php">My Events</a>
                    <a href="profile.php">Profile Settings</a>
                    <a href="../index.php">Home</a>
                    <a href="../backend/logout.php">Logout</a>
                </nav>
            </div>
        </aside>

        <main class="content">
            <section class="topbar">
                <div>
                    <h1>Welcome, <?= e($_SESSION['student_name']); ?></h1>
                    <p class="muted">Explore university programs, apply for seats, and follow your event approvals.</p>
                </div>
                <div class="topbar-actions">
                    <a class="button-link" href="my-events.php">My Events</a>
                    <a class="button-link ghost" href="my-clubs.php">My Clubs</a>
                    <a class="button-link" href="profile.php">My Profile</a>
                    <a class="button-link ghost" href="../index.php">Public Home</a>
                    <a class="button-link secondary" href="../backend/logout.php">Logout</a>
                </div>
            </section>

            <?php if ($message !== ''): ?>
                <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
            <?php endif; ?>

            <section class="stats-grid">
                <article class="stat-card">
                    <span>My Registrations</span>
                    <strong><?= e((string) ((int) $studentStats['total_registered'])); ?></strong>
                    <small>All submitted event requests</small>
                </article>
                <article class="stat-card">
                    <span>Approved</span>
                    <strong><?= e((string) ((int) $studentStats['approved_count'])); ?></strong>
                    <small>Confirmed participation</small>
                </article>
                <article class="stat-card">
                    <span>Pending</span>
                    <strong><?= e((string) ((int) $studentStats['pending_count'])); ?></strong>
                    <small>Waiting for admin review</small>
                </article>
                <article class="stat-card">
                    <span>Available Events</span>
                    <strong><?= e((string) ((int) $upcomingSummary['available_events'])); ?></strong>
                    <small>Events you have not registered yet</small>
                </article>
            </section>

            <section class="highlight-grid">
                <article class="highlight-card">
                    <strong>Upcoming University Activities</strong>
                    <span><?= e((string) ((int) $upcomingSummary['total_events'])); ?> active events are currently listed in the portal.</span>
                </article>
                <article class="highlight-card">
                    <strong>Fast Registration Flow</strong>
                    <span>Register from the dashboard, then track every request from the new My Events section.</span>
                </article>
                <article class="highlight-card">
                    <strong>Better Member Control</strong>
                    <span>Update your account details, change password, and cancel pending requests when needed.</span>
                </article>
            </section>

            <div class="panel-grid">
                <section class="panel">
                    <div class="section-head">
                    <h2>Upcoming Events</h2>
                        <p class="muted">Search events by title, venue, or category and open the full event page before registering.</p>
                    </div>

                    <form method="get" class="filter-bar">
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
                            <button type="submit">Apply</button>
                            <a class="button-link ghost" href="dashboard.php">Reset</a>
                        </div>
                    </form>

                    <div class="showcase-grid">
                        <?php if (!$events || $events->num_rows === 0): ?>
                            <div class="empty-state">No events matched your search.</div>
                        <?php else: ?>
                        <?php while ($event = $events->fetch_assoc()): ?>
                            <?php
                            $totalRegistered = (int) $event['total_registered'];
                            $capacity = (int) $event['capacity'];
                            $remaining = max($capacity - $totalRegistered, 0);
                            $myStatus = $event['my_status'];
                            $isDeadlineOpen = registrationDeadlineOpen($event['registration_deadline'] ?? null);
                            ?>
                            <article class="event-media-card">
                                <div class="event-thumb">
                                    <img src="<?= eventImageUrl($event['image_path'], '../'); ?>" alt="<?= e($event['title']); ?>">
                                </div>
                                <div class="event-body">
                                    <h3><?= e($event['title']); ?></h3>
                                    <p><?= e($event['description'] ?? 'No description added yet.'); ?></p>
                                    <div class="meta-list">
                                        <span>Club: <?= e($event['club_name'] ?: 'Unassigned'); ?></span>
                                        <span>Category: <?= e($event['category']); ?></span>
                                        <span>Date: <?= e(date('d M Y', strtotime($event['event_date']))); ?><?= $event['event_time'] ? ' at ' . e(date('h:i A', strtotime($event['event_time']))) : ''; ?></span>
                                        <span>Venue: <?= e($event['venue']); ?></span>
                                        <span>Seats Left: <?= e((string) $remaining); ?> / <?= e((string) $capacity); ?></span>
                                        <?php if ($event['registration_deadline']): ?>
                                            <span>Deadline: <?= e(date('d M Y', strtotime($event['registration_deadline']))); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="table-actions">
                                        <a class="button-link ghost small-btn" href="event-details.php?id=<?= e((string) $event['event_id']); ?>">Details</a>
                                        <?php if ($myStatus !== null): ?>
                                            <span class="status-badge status-<?= e($myStatus); ?>"><?= e(ucfirst($myStatus)); ?></span>
                                        <?php elseif (!$isDeadlineOpen): ?>
                                            <span class="status-badge status-rejected">Closed</span>
                                        <?php elseif ($remaining > 0): ?>
                                            <form method="post">
                                                <?= csrfField(); ?>
                                                <input type="hidden" name="event_id" value="<?= e((string) $event['event_id']); ?>">
                                                <button type="submit" class="small-btn">Register</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="status-badge status-rejected">Full</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="glass-panel">
                    <div class="section-head">
                        <h2>My Activity</h2>
                        <p class="muted">A quick summary of your event participation record.</p>
                    </div>
                    <div class="list-stack">
                        <?php if ($myRegistrations->num_rows === 0): ?>
                            <div class="empty-state">You have not registered for any event yet.</div>
                        <?php else: ?>
                            <?php while ($registration = $myRegistrations->fetch_assoc()): ?>
                                <div class="list-item">
                                    <div>
                                        <strong><?= e($registration['title']); ?></strong>
                                        <span><?= e(date('d M Y', strtotime($registration['event_date']))); ?> | <?= e($registration['venue']); ?></span>
                                    </div>
                                    <span class="status-badge status-<?= e($registration['status']); ?>"><?= e(ucfirst($registration['status'])); ?></span>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                    <div class="hero-actions">
                        <a class="button-link ghost" href="my-events.php">View Full Event History</a>
                    </div>
                </section>
            </div>

            <section class="quick-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                <article class="quick-card">
                    <h3>Profile & Security</h3>
                    <p>Update your name, email, department, and password from your personal profile settings page.</p>
                    <div class="hero-actions">
                        <a class="button-link" href="profile.php">Open Profile</a>
                    </div>
                </article>
                <article class="quick-card">
                    <h3>Registration Management</h3>
                    <p>Use the My Events page to review all statuses and cancel pending registrations without contacting admin first.</p>
                    <div class="hero-actions">
                        <a class="button-link ghost" href="my-events.php">Open My Events</a>
                    </div>
                </article>
            </section>
        </main>
    </div>
</body>
</html>
