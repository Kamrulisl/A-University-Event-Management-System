<?php
require_once __DIR__ . '/../backend/db.php';
requireAdminAuth();

$message = '';
$messageType = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verifyCsrfToken()) {
        $message = 'Invalid form request. Please try again.';
        $messageType = 'error';
    } elseif ($action === 'create_club') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $advisorName = trim($_POST['advisor_name'] ?? '');
        $advisorEmail = trim($_POST['advisor_email'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            $message = 'Club name is required.';
            $messageType = 'error';
        } elseif ($advisorEmail !== '' && !isValidEmail($advisorEmail)) {
            $message = 'Please enter a valid advisor email.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO clubs (name, category, advisor_name, advisor_email, description)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('sssss', $name, $category, $advisorName, $advisorEmail, $description);

            if ($stmt->execute()) {
                $message = 'Club created successfully.';
            } else {
                $message = 'Could not create club. The club may already exist.';
                $messageType = 'error';
            }

            $stmt->close();
        }
    } elseif ($action === 'update_status') {
        $clubId = (int) ($_POST['club_id'] ?? 0);
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
        $stmt = $conn->prepare('UPDATE clubs SET status = ? WHERE club_id = ?');
        $stmt->bind_param('si', $status, $clubId);

        if ($stmt->execute()) {
            $message = 'Club status updated.';
        } else {
            $message = 'Could not update club status.';
            $messageType = 'error';
        }

        $stmt->close();
    } elseif ($action === 'create_club_admin') {
        $clubId = (int) ($_POST['club_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($clubId < 1 || $name === '' || !isValidEmail($email) || strlen($password) < 6) {
            $message = 'Club, name, valid email, and 6+ character password are required.';
            $messageType = 'error';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                'INSERT INTO club_admins (club_id, name, email, password)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->bind_param('isss', $clubId, $name, $email, $passwordHash);

            if ($stmt->execute()) {
                $message = 'Club admin account created.';
            } else {
                $message = 'Could not create club admin. Email may already exist.';
                $messageType = 'error';
            }

            $stmt->close();
        }
    } elseif ($action === 'update_club_admin_status') {
        $clubAdminId = (int) ($_POST['club_admin_id'] ?? 0);
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
        $stmt = $conn->prepare('UPDATE club_admins SET status = ? WHERE club_admin_id = ?');
        $stmt->bind_param('si', $status, $clubAdminId);

        if ($stmt->execute()) {
            $message = 'Club admin status updated.';
        } else {
            $message = 'Could not update club admin status.';
            $messageType = 'error';
        }

        $stmt->close();
    }
}

$clubOptions = $conn->query(
    'SELECT club_id, name
     FROM clubs
     WHERE status = "active"
     ORDER BY name ASC'
);

$clubAdmins = $conn->query(
    'SELECT ca.club_admin_id, ca.name, ca.email, ca.status, ca.created_at, c.name AS club_name
     FROM club_admins ca
     INNER JOIN clubs c ON c.club_id = ca.club_id
     ORDER BY c.name ASC, ca.name ASC'
);

$clubs = $conn->query(
    'SELECT c.club_id, c.name, c.category, c.advisor_name, c.advisor_email, c.description, c.logo_path, c.status,
            COUNT(DISTINCT e.event_id) AS total_events,
            COUNT(DISTINCT r.registration_id) AS total_registrations
     FROM clubs c
     LEFT JOIN events e ON e.club_id = c.club_id
     LEFT JOIN registrations r ON r.event_id = e.event_id
     GROUP BY c.club_id, c.name, c.category, c.advisor_name, c.advisor_email, c.description, c.logo_path, c.status
     ORDER BY c.status ASC, c.name ASC'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clubs | University Club Event Management</title>
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
                        <span>Club & Event Control</span>
                    </div>
                </div>
            </div>

            <div>
                <nav class="nav-links">
                    <a href="admin-dashboard.php">Dashboard</a>
                    <a href="manage-clubs.php" class="active">Clubs</a>
                    <a href="create-event.php">Create Event</a>
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
                    <h1>Club Management</h1>
                    <p class="muted">Create club profiles, assign advisors, and track each club's event activity.</p>
                </div>
                <div class="topbar-actions">
                    <a class="button-link" href="create-event.php">Create Event</a>
                    <a class="button-link ghost" href="reports.php">Reports</a>
                </div>
            </section>

            <?php if ($message !== ''): ?>
                <div class="alert <?= e($messageType); ?>"><?= e($message); ?></div>
            <?php endif; ?>

            <div class="panel-grid">
                <section class="panel">
                    <div class="section-head">
                        <h2>Create Club</h2>
                        <p class="muted">Add official clubs before publishing their events.</p>
                    </div>
                    <form method="post" class="stack-form">
                        <?= csrfField(); ?>
                        <input type="hidden" name="action" value="create_club">
                        <label>
                            <span>Club Name</span>
                            <input type="text" name="name" placeholder="Debate Club" required>
                        </label>
                        <div class="inline-grid">
                            <label>
                                <span>Category</span>
                                <input type="text" name="category" placeholder="Cultural / Technology / Sports">
                            </label>
                            <label>
                                <span>Advisor Name</span>
                                <input type="text" name="advisor_name" placeholder="Faculty Advisor">
                            </label>
                        </div>
                        <label>
                            <span>Advisor Email</span>
                            <input type="email" name="advisor_email" placeholder="advisor@university.edu">
                        </label>
                        <label>
                            <span>Description</span>
                            <textarea name="description" rows="5" placeholder="Write what this club does"></textarea>
                        </label>
                        <button type="submit">Create Club</button>
                    </form>
                </section>

                <section class="panel">
                    <div class="section-head">
                        <h2>Create Club Admin</h2>
                        <p class="muted">Assign an account that can manage only one club's events and members.</p>
                    </div>
                    <form method="post" class="stack-form">
                        <?= csrfField(); ?>
                        <input type="hidden" name="action" value="create_club_admin">
                        <label>
                            <span>Club</span>
                            <select name="club_id" required>
                                <option value="">Select club</option>
                                <?php if ($clubOptions): ?>
                                    <?php while ($clubOption = $clubOptions->fetch_assoc()): ?>
                                        <option value="<?= e((string) $clubOption['club_id']); ?>"><?= e($clubOption['name']); ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </label>
                        <label>
                            <span>Admin Name</span>
                            <input type="text" name="name" placeholder="Club Executive" required>
                        </label>
                        <label>
                            <span>Email</span>
                            <input type="email" name="email" placeholder="club.admin@university.edu" required>
                        </label>
                        <label>
                            <span>Password</span>
                            <input type="password" name="password" placeholder="Minimum 6 characters" required>
                        </label>
                        <button type="submit">Create Club Admin</button>
                    </form>
                </section>
            </div>

            <section class="panel">
                <div class="section-head">
                    <h2>Club Admin Accounts</h2>
                    <p class="muted">Control who can access each club-specific panel.</p>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Club</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$clubAdmins || $clubAdmins->num_rows === 0): ?>
                                <tr><td colspan="5">No club admin accounts found.</td></tr>
                            <?php else: ?>
                                <?php while ($clubAdmin = $clubAdmins->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= e($clubAdmin['name']); ?></td>
                                        <td><?= e($clubAdmin['email']); ?></td>
                                        <td><?= e($clubAdmin['club_name']); ?></td>
                                        <td><span class="status-badge status-<?= $clubAdmin['status'] === 'active' ? 'success' : 'rejected'; ?>"><?= e(ucfirst($clubAdmin['status'])); ?></span></td>
                                        <td>
                                            <form method="post">
                                                <?= csrfField(); ?>
                                                <input type="hidden" name="action" value="update_club_admin_status">
                                                <input type="hidden" name="club_admin_id" value="<?= e((string) $clubAdmin['club_admin_id']); ?>">
                                                <input type="hidden" name="status" value="<?= $clubAdmin['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                <button type="submit" class="small-btn <?= $clubAdmin['status'] === 'active' ? 'danger-btn' : ''; ?>">
                                                    <?= $clubAdmin['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel">
                <div class="section-head">
                    <h2>All Clubs</h2>
                    <p class="muted">Manage club status and review club-wise activity.</p>
                </div>
                <div class="showcase-grid">
                    <?php if (!$clubs || $clubs->num_rows === 0): ?>
                        <div class="empty-state">No clubs found.</div>
                    <?php else: ?>
                        <?php while ($club = $clubs->fetch_assoc()): ?>
                            <article class="club-card">
                                <div class="club-card-head">
                                    <img src="<?= eventImageUrl($club['logo_path'], '../'); ?>" alt="<?= e($club['name']); ?>">
                                    <div>
                                        <span class="status-badge status-<?= $club['status'] === 'active' ? 'success' : 'rejected'; ?>"><?= e(ucfirst($club['status'])); ?></span>
                                        <h3><?= e($club['name']); ?></h3>
                                        <p><?= e($club['category'] ?: 'General'); ?></p>
                                    </div>
                                </div>
                                <p><?= e($club['description'] ?: 'No club description added yet.'); ?></p>
                                <div class="meta-list">
                                    <span>Advisor: <?= e($club['advisor_name'] ?: 'Not assigned'); ?></span>
                                    <span>Email: <?= e($club['advisor_email'] ?: 'Not added'); ?></span>
                                    <span>Events: <?= e((string) $club['total_events']); ?></span>
                                    <span>Registrations: <?= e((string) $club['total_registrations']); ?></span>
                                </div>
                                <form method="post" class="table-actions">
                                    <?= csrfField(); ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="club_id" value="<?= e((string) $club['club_id']); ?>">
                                    <input type="hidden" name="status" value="<?= $club['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                    <button type="submit" class="small-btn <?= $club['status'] === 'active' ? 'danger-btn' : ''; ?>">
                                        <?= $club['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                            </article>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
