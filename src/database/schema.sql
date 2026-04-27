CREATE DATABASE IF NOT EXISTS premier_university_events;
USE premier_university_events;

CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100) DEFAULT 'Premier University',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    image_path VARCHAR(255) DEFAULT NULL,
    category VARCHAR(80) DEFAULT 'General',
    event_date DATE NOT NULL,
    event_time TIME DEFAULT NULL,
    registration_deadline DATE DEFAULT NULL,
    venue VARCHAR(150) NOT NULL,
    capacity INT NOT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_events_capacity CHECK (capacity > 0 AND capacity <= 500),
    CONSTRAINT fk_events_admin FOREIGN KEY (created_by) REFERENCES admins(admin_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS registrations (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    event_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_student_event UNIQUE (student_id, event_id),
    CONSTRAINT fk_registrations_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    CONSTRAINT fk_registrations_event FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);

INSERT INTO admins (name, email, password)
SELECT 'System Admin', 'admin@puc.ac.bd', '$2y$10$7A0h539bPk8aP0yb./DupuBjlzms9yeQ5dBZF5mGVQM8LVNhX5uca'
WHERE NOT EXISTS (
    SELECT 1 FROM admins WHERE email = 'admin@puc.ac.bd'
);

INSERT INTO events (title, description, image_path, category, event_date, event_time, registration_deadline, venue, capacity, created_by)
SELECT 'Programming Contest', 'Inter-department problem solving contest for Premier University students.', 'assets/images/puc_logo.png', 'Competition', '2026-05-15', '10:00:00', '2026-05-12', 'Main Auditorium', 200, 1
WHERE NOT EXISTS (
    SELECT 1 FROM events WHERE title = 'Programming Contest' AND event_date = '2026-05-15'
);

INSERT INTO events (title, description, image_path, category, event_date, event_time, registration_deadline, venue, capacity, created_by)
SELECT 'AI Workshop', 'Hands-on workshop on practical AI tools and project ideas.', 'assets/images/puc_logo.png', 'Workshop', '2026-05-22', '14:30:00', '2026-05-19', 'CSE Lab 3', 120, 1
WHERE NOT EXISTS (
    SELECT 1 FROM events WHERE title = 'AI Workshop' AND event_date = '2026-05-22'
);
