<?php
require_once __DIR__ . '/../backend/db.php';
requireAdminAuth();

$summary = [
    'events' => 0,
    'students' => 0,
    'approved' => 0,
    'pending' => 0,
];

$summaryResult = $conn->query(
    'SELECT
        (SELECT COUNT(*) FROM events) AS events,
        (SELECT COUNT(*) FROM students) AS students,
        (SELECT COUNT(*) FROM registrations WHERE status = "approved") AS approved,
        (SELECT COUNT(*) FROM registrations WHERE status = "pending") AS pending'
);

if ($summaryResult) {
    $summary = array_map('intval', $summaryResult->fetch_assoc());
}

$categoryStats = $conn->query(
    'SELECT e.category, COUNT(DISTINCT e.event_id) AS total_events, COUNT(r.registration_id) AS total_requests
     FROM events e
     LEFT JOIN registrations r ON r.event_id = e.event_id
     GROUP BY e.category
     ORDER BY total_events DESC, e.category ASC'
);

$eventOccupancy = $conn->query(
    'SELECT e.title, e.category, e.event_date, e.capacity,
            COUNT(CASE WHEN r.status IN ("pending", "approved") THEN 1 END) AS requested_count,
            SUM(CASE WHEN r.status = "approved" THEN 1 ELSE 0 END) AS approved_count
     FROM events e
     LEFT JOIN registrations r ON r.event_id = e.event_id
     GROUP BY e.event_id, e.title, e.category, e.event_date, e.capacity
     ORDER BY e.event_date ASC'
);

$topStudents = $conn->query(
    'SELECT s.name, s.email, s.department, COUNT(r.registration_id) AS total_requests,
            SUM(CASE WHEN r.status = "approved" THEN 1 ELSE 0 END) AS approved_count
     FROM students s
     LEFT JOIN registrations r ON r.student_id = s.student_id
     GROUP BY s.student_id, s.name, s.email, s.department
     ORDER BY total_requests DESC, approved_count DESC
     LIMIT 8'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | University Event Management System</title>
    <link rel="stylesheet" href="../student/style.css">
</head>
<body class="app-page">
    <div class="app-shell">
        <aside class="sidebar">
            <div>
                <div class="brand-row">
                    <img src="../assets/images/puc_logo.png" alt="University Event Management System Logo" class="brand-logo">
                    <div class="brand-copy">
                        <strong>University Event Management System</strong>
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
                    <a href="admin-dashboard.php">Dashboard</a>
                    <a href="create-event.php">Create Event</a>
                    <a href="manage-events.php">Manage Events</a>
                    <a href="manage-students.php">Students</a>
                    <a href="manage-participants.php">Participants</a>
                    <a href="reports.php" class="active">Reports</a>
                    <a href="profile.php">Admin Profile</a>
                    <a href="../index.php">Home</a>
                    <a href="../backend/logout.php?admin=1">Logout</a>
                </nav>
            </div>
        </aside>

        <main class="content">
            <section class="topbar">
                <div>
                    <h1>Reports & Analytics</h1>
                    <p class="muted">A management view of event demand, category activity, and student engagement.</p>
                </div>
                <div class="topbar-actions">
                    <a class="button-link" href="create-event.php">Create Event</a>
                    <a class="button-link ghost" href="manage-participants.php">Review Requests</a>
                </div>
            </section>

            <section class="stats-grid">
                <article class="stat-card">
                    <span>Total Events</span>
                    <strong><?= e((string) $summary['events']); ?></strong>
                    <small>Published programs</small>
                </article>
                <article class="stat-card">
                    <span>Total Students</span>
                    <strong><?= e((string) $summary['students']); ?></strong>
                    <small>Registered accounts</small>
                </article>
                <article class="stat-card">
                    <span>Approved Seats</span>
                    <strong><?= e((string) $summary['approved']); ?></strong>
                    <small>Confirmed participants</small>
                </article>
                <article class="stat-card">
                    <span>Pending Review</span>
                    <strong><?= e((string) $summary['pending']); ?></strong>
                    <small>Needs admin decision</small>
                </article>
            </section>

            <div class="panel-grid">
                <section class="panel">
                    <div class="section-head">
                        <h2>Category Performance</h2>
                        <p class="muted">Which event types are being used most across the portal.</p>
                    </div>
                    <div class="report-list">
                        <?php if (!$categoryStats || $categoryStats->num_rows === 0): ?>
                            <div class="empty-state">No category data available.</div>
                        <?php else: ?>
                            <?php while ($category = $categoryStats->fetch_assoc()): ?>
                                <div class="report-row">
                                    <div>
                                        <strong><?= e($category['category'] ?: 'General'); ?></strong>
                                        <span><?= e((string) $category['total_events']); ?> events</span>
                                    </div>
                                    <span class="status-badge status-pending"><?= e((string) $category['total_requests']); ?> requests</span>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="panel">
                    <div class="section-head">
                        <h2>Most Active Students</h2>
                        <p class="muted">Students with the highest event request activity.</p>
                    </div>
                    <div class="list-stack">
                        <?php if (!$topStudents || $topStudents->num_rows === 0): ?>
                            <div class="empty-state">No student activity yet.</div>
                        <?php else: ?>
                            <?php while ($student = $topStudents->fetch_assoc()): ?>
                                <div class="list-item">
                                    <div>
                                        <strong><?= e($student['name']); ?></strong>
                                        <span><?= e($student['department']); ?> | <?= e($student['email']); ?></span>
                                    </div>
                                    <span><?= e((string) $student['total_requests']); ?> requests</span>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <section class="panel">
                <div class="section-head">
                    <h2>Event Seat Usage</h2>
                    <p class="muted">Track demand and approved seat usage for each event.</p>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Category</th>
                                <th>Date</th>
                                <th>Capacity</th>
                                <th>Requested</th>
                                <th>Approved</th>
                                <th>Usage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$eventOccupancy || $eventOccupancy->num_rows === 0): ?>
                                <tr>
                                    <td colspan="7">No event data available.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($event = $eventOccupancy->fetch_assoc()): ?>
                                    <?php
                                    $capacity = (int) $event['capacity'];
                                    $requested = (int) $event['requested_count'];
                                    $usage = $capacity > 0 ? min(100, round(($requested / $capacity) * 100)) : 0;
                                    ?>
                                    <tr>
                                        <td><?= e($event['title']); ?></td>
                                        <td><?= e($event['category']); ?></td>
                                        <td><?= e(date('d M Y', strtotime($event['event_date']))); ?></td>
                                        <td><?= e((string) $capacity); ?></td>
                                        <td><?= e((string) $requested); ?></td>
                                        <td><?= e((string) ((int) $event['approved_count'])); ?></td>
                                        <td>
                                            <div class="mini-meter">
                                                <span style="width: <?= e((string) $usage); ?>%;"></span>
                                            </div>
                                            <small><?= e((string) $usage); ?>%</small>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
