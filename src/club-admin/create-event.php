<?php
require_once __DIR__ . '/../backend/db.php';
requireClubAdminAuth();

$clubId = (int) $_SESSION['club_admin_club_id'];
$clubAdminId = (int) $_SESSION['club_admin_id'];
$message = '';
$messageType = 'error';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $eventDate = $_POST['event_date'] ?? '';
    $eventTime = trim($_POST['event_time'] ?? '');
    $registrationDeadline = trim($_POST['registration_deadline'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $capacity = (int) ($_POST['capacity'] ?? 0);
    $imagePath = null;
    $imageFile = $_FILES['event_image'] ?? [];
    $hasImageUpload = isset($imageFile['error']) && (int) $imageFile['error'] !== UPLOAD_ERR_NO_FILE;

    if (!verifyCsrfToken()) {
        $message = 'Invalid form request. Please try again.';
    } elseif ($title === '' || $venue === '') {
        $message = 'Title and venue are required.';
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
            'INSERT INTO events (club_id, title, description, image_path, category, event_date, event_time, registration_deadline, venue, capacity, created_by_club_admin)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $eventTimeValue = $eventTime !== '' ? $eventTime : null;
        $deadlineValue = $registrationDeadline !== '' ? $registrationDeadline : null;
        $stmt->bind_param('issssssssii', $clubId, $title, $description, $imagePath, $category, $eventDate, $eventTimeValue, $deadlineValue, $venue, $capacity, $clubAdminId);

        if ($stmt->execute()) {
            $message = 'Event created for your club.';
            $messageType = 'success';
        } else {
            $message = 'Could not create event.';
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Club Event | University Club Event Management</title>
    <link rel="stylesheet" href="../student/style.css">
</head>
<body class="app-page">
    <div class="app-shell">
        <aside class="sidebar club-sidebar">
            <div class="brand-row">
                <img src="../assets/images/puc_logo.png" alt="PUC Logo" class="brand-logo">
                <div class="brand-copy"><strong>University Club Event Management</strong><span>Club Admin Panel</span></div>
            </div>
            <nav class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="create-event.php" class="active">Create Event</a>
                <a href="manage-events.php">Manage Events</a>
                <a href="participants.php">Participants</a>
                <a href="members.php">Club Members</a>
                <a href="profile.php">Club Profile</a>
                <a href="../backend/logout.php?club=1">Logout</a>
            </nav>
        </aside>

        <main class="content">
            <section class="topbar">
                <div>
                    <h1>Create Event</h1>
                    <p class="muted">Publish an event directly under <?= e($_SESSION['club_admin_club_name']); ?>.</p>
                </div>
                <div class="topbar-actions">
                    <a class="button-link ghost" href="manage-events.php">Manage Events</a>
                </div>
            </section>

            <?php if ($message !== ''): ?>
                <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
            <?php endif; ?>

            <section class="panel form-panel">
                <div class="section-head">
                    <h2>Event Details</h2>
                    <p class="muted">Members will see these details before requesting a seat.</p>
                </div>
                <form method="post" class="stack-form" enctype="multipart/form-data">
                    <?= csrfField(); ?>
                    <label><span>Event Title</span><input type="text" name="title" placeholder="Club Workshop" required></label>
                    <label><span>Description</span><textarea name="description" rows="5" placeholder="Write event details"></textarea></label>
                    <div class="inline-grid">
                        <label>
                            <span>Category</span>
                            <select name="category" required>
                                <option value="Workshop">Workshop</option>
                                <option value="Competition">Competition</option>
                                <option value="Seminar">Seminar</option>
                                <option value="Cultural">Cultural</option>
                                <option value="Sports">Sports</option>
                                <option value="General">General</option>
                            </select>
                        </label>
                        <label><span>Event Image</span><input type="file" name="event_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></label>
                    </div>
                    <div class="inline-grid">
                        <label><span>Event Date</span><input type="date" name="event_date" required></label>
                        <label><span>Event Time</span><input type="time" name="event_time"></label>
                    </div>
                    <div class="inline-grid">
                        <label><span>Registration Deadline</span><input type="date" name="registration_deadline"></label>
                        <label><span>Capacity</span><input type="number" name="capacity" min="1" max="500" placeholder="120" required></label>
                    </div>
                    <label><span>Venue</span><input type="text" name="venue" placeholder="Main Auditorium" required></label>
                    <button type="submit">Create Event</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
