<?php
require_once __DIR__ . '/../backend/db.php';
requireAdminAuth();

$students = $conn->query(
    'SELECT s.student_id, s.name, s.email, s.department, s.created_at,
            COUNT(r.registration_id) AS total_registrations,
            SUM(CASE WHEN r.status = "approved" THEN 1 ELSE 0 END) AS approved_count
     FROM students s
     LEFT JOIN registrations r ON r.student_id = s.student_id
     GROUP BY s.student_id, s.name, s.email, s.department, s.created_at
     ORDER BY s.created_at DESC'
);

$studentSummary = $conn->query(
    'SELECT
        COUNT(*) AS total_students,
        COUNT(DISTINCT department) AS total_departments
     FROM students'
)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | Premier University</title>
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
                    <a href="manage-events.php">Manage Events</a>
                    <a href="manage-students.php" class="active">Students</a>
                    <a href="manage-participants.php">Participants</a>
                    <a href="profile.php">Admin Profile</a>
                    <a href="../index.php">Home</a>
                    <a href="../backend/logout.php?admin=1">Logout</a>
                </nav>
            </div>
        </aside>

        <main class="content">
            <section class="topbar">
                <div>
                    <h1>Student Management</h1>
                    <p class="muted">Review registered students and monitor their event participation activity.</p>
                </div>
            </section>

            <section class="stats-grid">
                <article class="stat-card">
                    <span>Total Students</span>
                    <strong><?= e((string) ((int) ($studentSummary['total_students'] ?? 0))); ?></strong>
                    <small>Student accounts in the system</small>
                </article>
                <article class="stat-card">
                    <span>Departments</span>
                    <strong><?= e((string) ((int) ($studentSummary['total_departments'] ?? 0))); ?></strong>
                    <small>Distinct academic units</small>
                </article>
                <article class="stat-card">
                    <span>Admin View</span>
                    <strong>PUC</strong>
                    <small>Student participation directory</small>
                </article>
                <article class="stat-card">
                    <span>Status</span>
                    <strong>Live</strong>
                    <small>Data from current registrations</small>
                </article>
            </section>

            <section class="panel">
                <div class="section-head">
                    <h2>Student Directory</h2>
                    <p class="muted">Each row shows the student profile and registration performance summary.</p>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Joined</th>
                                <th>Registrations</th>
                                <th>Approved</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$students || $students->num_rows === 0): ?>
                                <tr>
                                    <td colspan="6">No student accounts found.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($student = $students->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= e($student['name']); ?></td>
                                        <td><?= e($student['email']); ?></td>
                                        <td><?= e($student['department']); ?></td>
                                        <td><?= e(date('d M Y', strtotime($student['created_at']))); ?></td>
                                        <td><?= e((string) ((int) $student['total_registrations'])); ?></td>
                                        <td><?= e((string) ((int) $student['approved_count'])); ?></td>
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
