<?php
require_once __DIR__ . '/backend/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About | University Club Event Management</title>
    <link rel="stylesheet" href="student/style.css">
</head>
<body class="website-body">
    <header class="site-header">
        <div class="site-shell site-header-inner">
            <a class="brand-row site-brand" href="index.php">
                <img src="assets/images/puc_logo.png" alt="PUC Logo" class="brand-logo">
                <div class="brand-copy">
                    <strong>University Club Event Management</strong>
                    <span>Project overview</span>
                </div>
            </a>
            <nav class="site-menu" aria-label="Main navigation">
                <a href="index.php">Home</a>
                <a href="clubs.php">Clubs</a>
                <a href="events.php">Events</a>
                <a href="about.php" class="active">About</a>
                <a href="contact.php">Contact</a>
            </nav>
            <div class="nav-actions">
                <a class="button-link ghost" href="student/login.php">Member Login</a>
                <a class="button-link ghost" href="club-admin/login.php">Club Admin</a>
                <a class="button-link secondary" href="admin/admin-login.php">Admin Panel</a>
            </div>
        </div>
    </header>

    <main class="site-shell page-main">
        <section class="page-title">
            <p class="eyebrow">About the Project</p>
            <h1>A role-based website for managing club events from announcement to approval.</h1>
            <p class="muted">The system is designed for club members, event administrators, and coordinators who need a simple digital workflow.</p>
        </section>

        <section class="panel-grid">
            <article class="panel">
                <div class="section-head">
                    <h2>Project Purpose</h2>
                    <p class="muted">Replace manual event registration and paper-based participant tracking with a web-based workflow.</p>
                </div>
                <div class="timeline-list">
                    <div>
                        <strong>1. Publish Events</strong>
                        <span>Admins create events with category, venue, date, time, deadline, capacity, and image.</span>
                    </div>
                    <div>
                        <strong>2. Member Registration</strong>
                        <span>Members create accounts, browse events, search categories, and request seats.</span>
                    </div>
                    <div>
                        <strong>3. Approval Control</strong>
                        <span>Admins approve or reject participants while the system tracks capacity and status.</span>
                    </div>
                    <div>
                        <strong>4. Reporting</strong>
                        <span>Reports show category performance, seat usage, pending requests, and active members.</span>
                    </div>
                </div>
            </article>

            <article class="panel">
                <div class="section-head">
                    <h2>Core Modules</h2>
                    <p class="muted">The project includes both website pages and authenticated dashboard pages.</p>
                </div>
                <div class="feature-list">
                    <span>Public homepage and event catalog</span>
                    <span>Member registration and login</span>
                    <span>Member dashboard and My Events page</span>
                    <span>Admin event creation and editing</span>
                    <span>Participant approval management</span>
                    <span>Reports and analytics dashboard</span>
                </div>
            </article>
        </section>

        <section class="section-band compact-band">
            <div class="section-head center-head">
                <p class="eyebrow">Technology</p>
                <h2>Built with raw PHP and MySQL</h2>
                <p class="muted">This keeps the project understandable for academic submission while still offering real website features.</p>
            </div>
        </section>
    </main>

    <footer class="footer-band website-footer">
        <div class="site-shell">
            <span>University Club Event Management</span>
            <span>About Project</span>
        </div>
    </footer>
</body>
</html>
