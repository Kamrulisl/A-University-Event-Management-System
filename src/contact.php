<?php
require_once __DIR__ . '/backend/db.php';

$message = '';
$messageType = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['message'] ?? '');

    if (!verifyCsrfToken()) {
        $message = 'Invalid form request. Please try again.';
        $messageType = 'error';
    } elseif ($name === '' || $subject === '' || $body === '') {
        $message = 'Please fill in all required fields.';
        $messageType = 'error';
    } elseif (!isValidEmail($email)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        $message = 'Message received. The club event office can review this inquiry.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact | University Club Event Management</title>
    <link rel="stylesheet" href="student/style.css">
</head>
<body class="website-body">
    <header class="site-header">
        <div class="site-shell site-header-inner">
            <a class="brand-row site-brand" href="index.php">
                <img src="assets/images/club_logo.svg" alt="University Club Event Management Logo" class="brand-logo">
                <div class="brand-copy">
                    <strong>University Club Event Management</strong>
                    <span>Contact event office</span>
                </div>
            </a>
            <nav class="site-menu" aria-label="Main navigation">
                <a href="index.php">Home</a>
                <a href="events.php">Events</a>
                <a href="about.php">About</a>
                <a href="contact.php" class="active">Contact</a>
            </nav>
            <div class="nav-actions">
                <a class="button-link ghost" href="student/login.php">Member Login</a>
                <a class="button-link secondary" href="admin/admin-login.php">Admin Panel</a>
            </div>
        </div>
    </header>

    <main class="site-shell page-main">
        <section class="page-title">
            <p class="eyebrow">Contact</p>
            <h1>Need help with club events or registration?</h1>
            <p class="muted">Members can ask about registration, event schedules, participant approval, or account access.</p>
        </section>

        <div class="panel-grid">
            <section class="panel">
                <div class="section-head">
                    <h2>Send Inquiry</h2>
                    <p class="muted">This demo stores no email, but it validates the form like a real contact workflow.</p>
                </div>
                <?php if ($message !== ''): ?>
                    <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
                <?php endif; ?>
                <form method="post" class="stack-form">
                    <?= csrfField(); ?>
                    <div class="inline-grid">
                        <label>
                            <span>Name</span>
                            <input type="text" name="name" required>
                        </label>
                        <label>
                            <span>Email</span>
                            <input type="email" name="email" required>
                        </label>
                    </div>
                    <label>
                        <span>Subject</span>
                        <input type="text" name="subject" required>
                    </label>
                    <label>
                        <span>Message</span>
                        <textarea name="message" rows="6" required></textarea>
                    </label>
                    <button type="submit">Send Message</button>
                </form>
            </section>

            <aside class="panel">
                <div class="section-head">
                    <h2>Event Office</h2>
                    <p class="muted">Use this section in your presentation to explain support channels.</p>
                </div>
                <div class="contact-list">
                    <div>
                        <strong>Support Email</strong>
                        <span>events@university.edu</span>
                    </div>
                    <div>
                        <strong>Office Hours</strong>
                        <span>Sunday to Thursday, 9:00 AM - 5:00 PM</span>
                    </div>
                    <div>
                        <strong>Member Help</strong>
                        <span>Login issues, registration status, event seat availability</span>
                    </div>
                    <div>
                        <strong>Admin Help</strong>
                        <span>Event publishing, participant approval, reports</span>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <footer class="footer-band website-footer">
        <div class="site-shell">
            <span>University Club Event Management</span>
            <span>Contact</span>
        </div>
    </footer>
</body>
</html>
