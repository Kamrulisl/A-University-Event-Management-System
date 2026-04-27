<?php
require_once __DIR__ . '/../backend/db.php';
requireAdminAuth();

$message = '';
$messageType = 'error';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $clubId = (int) ($_POST['club_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $eventDate = $_POST['event_date'] ?? '';
    $eventTime = trim($_POST['event_time'] ?? '');
    $registrationDeadline = trim($_POST['registration_deadline'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $capacity = (int) ($_POST['capacity'] ?? 0);
    $adminId = (int) $_SESSION['admin_id'];
    $imagePath = null;
    $imageFile = $_FILES['event_image'] ?? [];
    $hasImageUpload = isset($imageFile['error']) && (int) $imageFile['error'] !== UPLOAD_ERR_NO_FILE;

    if (!verifyCsrfToken()) {
        $message = 'Invalid form request. Please try again.';
    } elseif ($title === '' || $venue === '') {
        $message = 'Title and venue are required.';
    } elseif ($clubId < 1) {
        $message = 'Please select the club responsible for this event.';
    } elseif ($category === '') {
        $message = 'Category is required.';
    } elseif (!isValidDate($eventDate)) {
        $message = 'Please select a valid event date.';
    } elseif (!isValidTime($eventTime)) {
        $message = 'Please select a valid event time.';
    } elseif ($registrationDeadline !== '' && !isValidDate($registrationDeadline)) {
        $message = 'Please select a valid registration deadline.';
    } elseif ($registrationDeadline !== '' && strtotime($registrationDeadline) > strtotime($eventDate)) {
        $message = 'Registration deadline cannot be after the event date.';
    } elseif ($capacity < 1 || $capacity > 500) {
        $message = 'Capacity must be between 1 and 500.';
    } elseif ($hasImageUpload && ($imagePath = storeEventImage($imageFile, __DIR__ . '/../assets/uploads', 'assets/uploads')) === null) {
        $message = 'Event image must be a JPG, PNG, or WebP file up to 2 MB.';
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO events (club_id, title, description, image_path, category, event_date, event_time, registration_deadline, venue, capacity, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $eventTimeValue = $eventTime !== '' ? $eventTime : null;
        $deadlineValue = $registrationDeadline !== '' ? $registrationDeadline : null;
        $stmt->bind_param(
            'issssssssii',
            $clubId,
            $title,
            $description,
            $imagePath,
            $category,
            $eventDate,
            $eventTimeValue,
            $deadlineValue,
            $venue,
            $capacity,
            $adminId
        );

        if ($stmt->execute()) {
            $message = 'Event created successfully.';
            $messageType = 'success';
        } else {
            $message = 'Could not create event.';
        }

        $stmt->close();
    }
}

$eventList = $conn->query(
    'SELECT e.title, e.image_path, e.category, e.event_date, e.event_time, e.registration_deadline,
            e.venue, e.capacity, c.name AS club_name
     FROM events e
     LEFT JOIN clubs c ON c.club_id = e.club_id
     ORDER BY e.event_date DESC'
);

$clubs = $conn->query(
    'SELECT club_id, name
     FROM clubs
     WHERE status = "active"
     ORDER BY name ASC'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event | University Club Event Management</title>
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
            </div>

            <div>
                <nav class="nav-links">
                    <a href="admin-dashboard.php">Dashboard</a>
                    <a href="manage-clubs.php">Clubs</a>
                    <a href="create-event.php" class="active">Create Event</a>
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
                    <h1>Create a New Event</h1>
                    <p class="muted">Publish workshops, contests, seminars, and cultural programs for club members.</p>
                </div>
                <div class="topbar-actions">
                    <a class="button-link ghost" href="admin-dashboard.php">Back to Dashboard</a>
                    <a class="button-link ghost" href="manage-events.php">Manage Events</a>
                    <a class="button-link secondary" href="manage-participants.php">Participants</a>
                </div>
            </section>

            <?php if ($message !== ''): ?>
                <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
            <?php endif; ?>

            <div class="panel-grid">
                <section class="panel form-panel">
                    <div class="section-head">
                        <h2>Event Form</h2>
                        <p class="muted">Provide the core event details members need before registration.</p>
                    </div>
                    <form method="post" class="stack-form" enctype="multipart/form-data">
                        <?= csrfField(); ?>
                        <label>
                            <span>Event Title</span>
                            <input type="text" name="title" placeholder="Programming Contest" required>
                        </label>

                        <label>
                            <span>Organizing Club</span>
                            <select name="club_id" required>
                                <option value="">Select club</option>
                                <?php if ($clubs): ?>
                                    <?php while ($club = $clubs->fetch_assoc()): ?>
                                        <option value="<?= e((string) $club['club_id']); ?>"><?= e($club['name']); ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </label>

                        <label>
                            <span>Description</span>
                            <textarea name="description" rows="5" placeholder="Add a short event description"></textarea>
                        </label>

                        <label>
                            <span>Category</span>
                            <select name="category" required>
                                <option value="General">General</option>
                                <option value="Workshop">Workshop</option>
                                <option value="Competition">Competition</option>
                                <option value="Seminar">Seminar</option>
                                <option value="Cultural">Cultural</option>
                                <option value="Sports">Sports</option>
                            </select>
                        </label>

                        <label>
                            <span>Event Image</span>
                            <input type="file" name="event_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        </label>

                        <div class="inline-grid">
                            <label>
                                <span>Event Date</span>
                                <input type="date" name="event_date" required>
                            </label>

                            <label>
                                <span>Event Time</span>
                                <input type="time" name="event_time">
                            </label>
                        </div>

                        <div class="inline-grid">
                            <label>
                                <span>Registration Deadline</span>
                                <input type="date" name="registration_deadline">
                            </label>

                            <label>
                                <span>Capacity</span>
                                <input type="number" name="capacity" min="1" max="500" placeholder="120" required>
                            </label>
                        </div>

                        <label>
                            <span>Venue</span>
                            <input type="text" name="venue" placeholder="Main Auditorium" required>
                        </label>

                        <button type="submit">Create Event</button>
                    </form>
                </section>

                <section class="panel">
                    <div class="section-head">
                        <h2>Published Events</h2>
                        <p class="muted">A quick overview of events already available in the system.</p>
                    </div>
                    <div class="card-grid">
                        <?php if (!$eventList || $eventList->num_rows === 0): ?>
                            <div class="empty-state">No events have been created yet.</div>
                        <?php else: ?>
                            <?php while ($event = $eventList->fetch_assoc()): ?>
                                <article class="event-media-card compact-card">
                                    <div class="event-thumb small-thumb">
                                        <img src="<?= eventImageUrl($event['image_path'], '../'); ?>" alt="<?= e($event['title']); ?>">
                                    </div>
                                    <div class="event-body">
                                        <strong><?= e($event['title']); ?></strong>
                                        <span>Club: <?= e($event['club_name'] ?: 'Unassigned'); ?></span>
                                        <span><?= e($event['category']); ?> | <?= e(date('d M Y', strtotime($event['event_date']))); ?><?= $event['event_time'] ? ' at ' . e(date('h:i A', strtotime($event['event_time']))) : ''; ?></span>
                                        <span><?= e($event['venue']); ?><?= $event['registration_deadline'] ? ' | Deadline: ' . e(date('d M Y', strtotime($event['registration_deadline']))) : ''; ?></span>
                                        <span class="status-badge status-pending">Seats: <?= e((string) $event['capacity']); ?></span>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
