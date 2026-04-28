<?php
require_once __DIR__ . '/../backend/db.php';
requireClubAdminAuth();

$clubId = (int) $_SESSION['club_admin_club_id'];
$message = '';
$messageType = 'success';
$editEvent = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verifyCsrfToken()) {
        $message = 'Invalid form request. Please try again.';
        $messageType = 'error';
    } elseif ($action === 'delete_event') {
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM events WHERE event_id = ? AND club_id = ?');
        $stmt->bind_param('ii', $eventId, $clubId);
        $stmt->execute();
        $message = $stmt->affected_rows > 0 ? 'Event deleted.' : 'Event not found for your club.';
        $messageType = $stmt->affected_rows > 0 ? 'success' : 'error';
        $stmt->close();
    } elseif ($action === 'update_event') {
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
            $eventTimeValue = $eventTime !== '' ? $eventTime : null;
            $deadlineValue = $registrationDeadline !== '' ? $registrationDeadline : null;
            $stmt = $conn->prepare(
                'UPDATE events
                 SET title = ?, description = ?, image_path = ?, category = ?, event_date = ?, event_time = ?, registration_deadline = ?, venue = ?, capacity = ?
                 WHERE event_id = ? AND club_id = ?'
            );
            $stmt->bind_param('ssssssssiii', $title, $description, $imagePath, $category, $eventDate, $eventTimeValue, $deadlineValue, $venue, $capacity, $eventId, $clubId);
            $stmt->execute();
            $message = $stmt->affected_rows >= 0 ? 'Event updated.' : 'Could not update event.';
            $messageType = $stmt->affected_rows >= 0 ? 'success' : 'error';
            $stmt->close();
        }
    }
}

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM events WHERE event_id = ? AND club_id = ? LIMIT 1');
    $stmt->bind_param('ii', $editId, $clubId);
    $stmt->execute();
    $editEvent = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$eventsStmt = $conn->prepare(
    'SELECT e.event_id, e.title, e.description, e.image_path, e.category, e.event_date, e.event_time,
            e.registration_deadline, e.venue, e.capacity,
            COUNT(r.registration_id) AS total_registrations
     FROM events e
     LEFT JOIN registrations r ON r.event_id = e.event_id
     WHERE e.club_id = ?
     GROUP BY e.event_id, e.title, e.description, e.image_path, e.category, e.event_date, e.event_time,
              e.registration_deadline, e.venue, e.capacity
     ORDER BY e.event_date ASC'
);
$eventsStmt->bind_param('i', $clubId);
$eventsStmt->execute();
$events = $eventsStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Club Events | University Club Event Management</title>
    <link rel="stylesheet" href="../student/style.css">
</head>
<body class="app-page">
    <div class="app-shell">
        <aside class="sidebar club-sidebar">
            <div class="brand-row"><img src="../assets/images/puc_logo.png" alt="PUC Logo" class="brand-logo"><div class="brand-copy"><strong>University Club Event Management</strong><span>Club Admin Panel</span></div></div>
            <nav class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="create-event.php">Create Event</a>
                <a href="manage-events.php" class="active">Manage Events</a>
                <a href="participants.php">Participants</a>
                <a href="members.php">Club Members</a>
                <a href="profile.php">Club Profile</a>
                <a href="../backend/logout.php?club=1">Logout</a>
            </nav>
        </aside>
        <main class="content">
            <section class="topbar">
                <div><h1>Manage Events</h1><p class="muted">Edit or remove only <?= e($_SESSION['club_admin_club_name']); ?> events.</p></div>
                <div class="topbar-actions"><a class="button-link" href="create-event.php">Create Event</a></div>
            </section>

            <?php if ($message !== ''): ?><div class="alert <?= e($messageType); ?>"><?= e($message); ?></div><?php endif; ?>

            <?php if ($editEvent): ?>
                <section class="panel">
                    <div class="section-head"><h2>Edit Event</h2><p class="muted">Update the selected club event.</p></div>
                    <form method="post" class="stack-form" enctype="multipart/form-data">
                        <?= csrfField(); ?>
                        <input type="hidden" name="action" value="update_event">
                        <input type="hidden" name="event_id" value="<?= e((string) $editEvent['event_id']); ?>">
                        <input type="hidden" name="current_image_path" value="<?= e((string) ($editEvent['image_path'] ?? '')); ?>">
                        <label><span>Event Title</span><input type="text" name="title" value="<?= e($editEvent['title']); ?>" required></label>
                        <label><span>Description</span><textarea name="description" rows="4"><?= e($editEvent['description'] ?? ''); ?></textarea></label>
                        <div class="inline-grid">
                            <label><span>Category</span><input type="text" name="category" value="<?= e($editEvent['category'] ?? 'General'); ?>" required></label>
                            <label><span>Change Event Image</span><input type="file" name="event_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></label>
                        </div>
                        <div class="inline-grid">
                            <label><span>Date</span><input type="date" name="event_date" value="<?= e($editEvent['event_date']); ?>" required></label>
                            <label><span>Time</span><input type="time" name="event_time" value="<?= e(substr((string) ($editEvent['event_time'] ?? ''), 0, 5)); ?>"></label>
                        </div>
                        <div class="inline-grid">
                            <label><span>Registration Deadline</span><input type="date" name="registration_deadline" value="<?= e($editEvent['registration_deadline'] ?? ''); ?>"></label>
                            <label><span>Capacity</span><input type="number" name="capacity" min="1" max="500" value="<?= e((string) $editEvent['capacity']); ?>" required></label>
                        </div>
                        <label><span>Venue</span><input type="text" name="venue" value="<?= e($editEvent['venue']); ?>" required></label>
                        <div class="table-actions"><button type="submit">Save Changes</button><a class="button-link ghost" href="manage-events.php">Cancel</a></div>
                    </form>
                </section>
            <?php endif; ?>

            <section class="panel">
                <div class="section-head"><h2>All Club Events</h2><p class="muted">Club-wise event list with capacity and registration count.</p></div>
                <div class="showcase-grid">
                    <?php if (!$events || $events->num_rows === 0): ?>
                        <div class="empty-state">No events found for this club.</div>
                    <?php else: ?>
                        <?php while ($event = $events->fetch_assoc()): ?>
                            <article class="event-media-card">
                                <div class="event-thumb"><img src="<?= eventImageUrl($event['image_path'], '../'); ?>" alt="<?= e($event['title']); ?>"></div>
                                <div class="event-body">
                                    <h3><?= e($event['title']); ?></h3>
                                    <p><?= e($event['description'] ?: 'No description available yet.'); ?></p>
                                    <div class="meta-list">
                                        <span><?= e($event['category']); ?> | <?= e(date('d M Y', strtotime($event['event_date']))); ?><?= $event['event_time'] ? ' at ' . e(date('h:i A', strtotime($event['event_time']))) : ''; ?></span>
                                        <span>Venue: <?= e($event['venue']); ?></span>
                                        <span>Capacity: <?= e((string) $event['capacity']); ?></span>
                                        <span>Registrations: <?= e((string) $event['total_registrations']); ?></span>
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
