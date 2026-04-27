<?php
require_once __DIR__ . '/../backend/db.php';
requireAdminAuth();

$message = '';
$messageType = 'success';
$editEvent = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verifyCsrfToken()) {
        $message = 'Invalid form request. Please try again.';
        $messageType = 'error';
    } elseif ($action === 'delete_event') {
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $deleteStmt = $conn->prepare('DELETE FROM events WHERE event_id = ?');
        $deleteStmt->bind_param('i', $eventId);

        if ($deleteStmt->execute()) {
            $message = 'Event deleted successfully.';
        } else {
            $message = 'Could not delete event.';
            $messageType = 'error';
        }

        $deleteStmt->close();
    }

    if ($action === 'update_event' && $messageType !== 'error') {
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $eventDate = $_POST['event_date'] ?? '';
        $eventTime = trim($_POST['event_time'] ?? '');
        $registrationDeadline = trim($_POST['registration_deadline'] ?? '');
        $venue = trim($_POST['venue'] ?? '');
        $capacity = (int) ($_POST['capacity'] ?? 0);
        $currentImagePath = trim($_POST['current_image_path'] ?? '');
        $imageFile = $_FILES['event_image'] ?? [];
        $hasImageUpload = isset($imageFile['error']) && (int) $imageFile['error'] !== UPLOAD_ERR_NO_FILE;
        $newImagePath = $hasImageUpload ? storeEventImage($imageFile, __DIR__ . '/../assets/uploads', 'assets/uploads') : null;
        $imagePath = $newImagePath ?? $currentImagePath;

        if ($title === '' || $venue === '') {
            $message = 'Title and venue are required.';
            $messageType = 'error';
        } elseif ($category === '') {
            $message = 'Category is required.';
            $messageType = 'error';
        } elseif (!isValidDate($eventDate)) {
            $message = 'Please select a valid event date.';
            $messageType = 'error';
        } elseif (!isValidTime($eventTime)) {
            $message = 'Please select a valid event time.';
            $messageType = 'error';
        } elseif ($registrationDeadline !== '' && !isValidDate($registrationDeadline)) {
            $message = 'Please select a valid registration deadline.';
            $messageType = 'error';
        } elseif ($registrationDeadline !== '' && strtotime($registrationDeadline) > strtotime($eventDate)) {
            $message = 'Registration deadline cannot be after the event date.';
            $messageType = 'error';
        } elseif ($capacity < 1 || $capacity > 500) {
            $message = 'Capacity must be between 1 and 500.';
            $messageType = 'error';
        } elseif ($hasImageUpload && $newImagePath === null) {
            $message = 'Event image must be a JPG, PNG, or WebP file up to 2 MB.';
            $messageType = 'error';
        } else {
            $updateStmt = $conn->prepare(
                'UPDATE events
                 SET title = ?, description = ?, image_path = ?, category = ?, event_date = ?, event_time = ?, registration_deadline = ?, venue = ?, capacity = ?
                 WHERE event_id = ?'
            );
            $eventTimeValue = $eventTime !== '' ? $eventTime : null;
            $deadlineValue = $registrationDeadline !== '' ? $registrationDeadline : null;
            $updateStmt->bind_param(
                'ssssssssii',
                $title,
                $description,
                $imagePath,
                $category,
                $eventDate,
                $eventTimeValue,
                $deadlineValue,
                $venue,
                $capacity,
                $eventId
            );

            if ($updateStmt->execute()) {
                $message = 'Event updated successfully.';
            } else {
                $message = 'Event update failed.';
                $messageType = 'error';
            }

            $updateStmt->close();
        }
    }
}

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editStmt = $conn->prepare('SELECT * FROM events WHERE event_id = ? LIMIT 1');
    $editStmt->bind_param('i', $editId);
    $editStmt->execute();
    $editEvent = $editStmt->get_result()->fetch_assoc();
    $editStmt->close();
}

$events = $conn->query(
    'SELECT e.event_id, e.title, e.description, e.image_path, e.category, e.event_date, e.event_time,
            e.registration_deadline, e.venue, e.capacity,
            COUNT(r.registration_id) AS total_registrations
     FROM events e
     LEFT JOIN registrations r ON r.event_id = e.event_id
     GROUP BY e.event_id, e.title, e.description, e.image_path, e.category, e.event_date,
              e.event_time, e.registration_deadline, e.venue, e.capacity
     ORDER BY e.event_date ASC'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events | Premier University</title>
    <link rel="stylesheet" href="../student/style.css">
</head>
<body class="app-page">
    <div class="app-shell">
        <aside class="sidebar">
            <div>
                <div class="brand-row">
                    <img src="../assets/images/puc_logo.png" alt="Premier University Logo" class="brand-logo">
                    <div class="brand-copy">
                        <strong>Premier University</strong>
                        <span>Admin Event Control</span>
                    </div>
                </div>
            </div>
            <div>
                <nav class="nav-links">
                    <a href="admin-dashboard.php">Dashboard</a>
                    <a href="create-event.php">Create Event</a>
                    <a href="manage-events.php" class="active">Manage Events</a>
                    <a href="manage-students.php">Students</a>
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
                    <h1>Manage Events</h1>
                    <p class="muted">Edit event details, review seat usage, or remove old events from the system.</p>
                </div>
                <div class="topbar-actions">
                    <a class="button-link" href="create-event.php">Create Event</a>
                </div>
            </section>

            <?php if ($message !== ''): ?>
                <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
            <?php endif; ?>

            <?php if ($editEvent): ?>
                <section class="panel">
                    <div class="section-head">
                        <h2>Edit Event</h2>
                        <p class="muted">Update the selected event and save the revised information.</p>
                    </div>
                    <form method="post" class="stack-form" enctype="multipart/form-data">
                        <?= csrfField(); ?>
                        <input type="hidden" name="action" value="update_event">
                        <input type="hidden" name="event_id" value="<?= e((string) $editEvent['event_id']); ?>">
                        <input type="hidden" name="current_image_path" value="<?= e((string) ($editEvent['image_path'] ?? '')); ?>">
                        <label>
                            <span>Event Title</span>
                            <input type="text" name="title" value="<?= e($editEvent['title']); ?>" required>
                        </label>
                        <label>
                            <span>Description</span>
                            <textarea name="description" rows="4"><?= e($editEvent['description'] ?? ''); ?></textarea>
                        </label>
                        <label>
                            <span>Category</span>
                            <select name="category" required>
                                <?php foreach (['General', 'Workshop', 'Competition', 'Seminar', 'Cultural', 'Sports'] as $categoryOption): ?>
                                    <option value="<?= e($categoryOption); ?>" <?= ($editEvent['category'] ?? 'General') === $categoryOption ? 'selected' : ''; ?>><?= e($categoryOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <div class="inline-grid">
                            <label>
                                <span>Date</span>
                                <input type="date" name="event_date" value="<?= e($editEvent['event_date']); ?>" required>
                            </label>
                            <label>
                                <span>Time</span>
                                <input type="time" name="event_time" value="<?= e(substr((string) ($editEvent['event_time'] ?? ''), 0, 5)); ?>">
                            </label>
                        </div>
                        <div class="inline-grid">
                            <label>
                                <span>Registration Deadline</span>
                                <input type="date" name="registration_deadline" value="<?= e($editEvent['registration_deadline'] ?? ''); ?>">
                            </label>
                            <label>
                                <span>Capacity</span>
                                <input type="number" name="capacity" min="1" max="500" value="<?= e((string) $editEvent['capacity']); ?>" required>
                            </label>
                        </div>
                        <label>
                            <span>Venue</span>
                            <input type="text" name="venue" value="<?= e($editEvent['venue']); ?>" required>
                        </label>
                        <label>
                            <span>Change Event Image</span>
                            <input type="file" name="event_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        </label>
                        <div class="event-thumb medium-thumb">
                            <img src="<?= eventImageUrl($editEvent['image_path'] ?? null, '../'); ?>" alt="<?= e($editEvent['title']); ?>">
                        </div>
                        <div class="table-actions">
                            <button type="submit">Save Changes</button>
                            <a class="button-link ghost" href="manage-events.php">Cancel</a>
                        </div>
                    </form>
                </section>
            <?php endif; ?>

            <section class="panel">
                <div class="section-head">
                    <h2>All Events</h2>
                    <p class="muted">Full event list with schedule, capacity, and registration volume.</p>
                </div>
                <div class="card-grid">
                    <?php if (!$events || $events->num_rows === 0): ?>
                        <div class="empty-state">No events found.</div>
                    <?php else: ?>
                        <?php while ($event = $events->fetch_assoc()): ?>
                            <article class="event-media-card">
                                <div class="event-thumb">
                                    <img src="<?= eventImageUrl($event['image_path'], '../'); ?>" alt="<?= e($event['title']); ?>">
                                </div>
                                <div class="event-body">
                                    <h3><?= e($event['title']); ?></h3>
                                    <p><?= e($event['description'] ?: 'No description available yet.'); ?></p>
                                    <div class="meta-list">
                                        <span>Category: <?= e($event['category']); ?></span>
                                        <span>Date: <?= e(date('d M Y', strtotime($event['event_date']))); ?><?= $event['event_time'] ? ' at ' . e(date('h:i A', strtotime($event['event_time']))) : ''; ?></span>
                                        <span>Venue: <?= e($event['venue']); ?></span>
                                        <span>Capacity: <?= e((string) $event['capacity']); ?></span>
                                        <span>Registrations: <?= e((string) $event['total_registrations']); ?></span>
                                        <?php if ($event['registration_deadline']): ?>
                                            <span>Deadline: <?= e(date('d M Y', strtotime($event['registration_deadline']))); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="table-actions">
                                        <a class="button-link ghost small-btn" href="manage-events.php?edit=<?= e((string) $event['event_id']); ?>">Edit</a>
                                        <form method="post" class="mini-form">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="action" value="delete_event">
                                            <input type="hidden" name="event_id" value="<?= e((string) $event['event_id']); ?>">
                                            <button type="submit" class="small-btn danger-btn">Delete</button>
                                        </form>
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
