CREATE DATABASE IF NOT EXISTS university_club_events;
USE university_club_events;

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
    department VARCHAR(100) DEFAULT 'General',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS clubs (
    club_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    category VARCHAR(80) DEFAULT 'General',
    advisor_name VARCHAR(120) DEFAULT NULL,
    advisor_email VARCHAR(120) DEFAULT NULL,
    description TEXT,
    logo_path VARCHAR(255) DEFAULT 'assets/images/club_logo.svg',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS club_admins (
    club_admin_id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_club_admins_club FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT DEFAULT NULL,
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
    created_by_club_admin INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_events_capacity CHECK (capacity > 0 AND capacity <= 500),
    CONSTRAINT fk_events_club FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE SET NULL,
    CONSTRAINT fk_events_admin FOREIGN KEY (created_by) REFERENCES admins(admin_id) ON DELETE SET NULL,
    CONSTRAINT fk_events_club_admin FOREIGN KEY (created_by_club_admin) REFERENCES club_admins(club_admin_id) ON DELETE SET NULL
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

CREATE TABLE IF NOT EXISTS club_memberships (
    membership_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    club_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    decided_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT uq_student_club UNIQUE (student_id, club_id),
    CONSTRAINT fk_memberships_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    CONSTRAINT fk_memberships_club FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE
);

INSERT INTO admins (name, email, password)
SELECT 'Club Admin', 'admin@club.edu', '$2y$10$7A0h539bPk8aP0yb./DupuBjlzms9yeQ5dBZF5mGVQM8LVNhX5uca'
WHERE NOT EXISTS (
    SELECT 1 FROM admins WHERE email = 'admin@club.edu'
);

INSERT INTO clubs (name, category, advisor_name, advisor_email, description, logo_path)
SELECT 'Computer Programming Club', 'Technology', 'CSE Faculty Advisor', 'programming.club@university.edu',
       'Organizes programming contests, coding workshops, and technical learning sessions.', 'assets/images/club_logo.svg'
WHERE NOT EXISTS (
    SELECT 1 FROM clubs WHERE name = 'Computer Programming Club'
);

INSERT INTO clubs (name, category, advisor_name, advisor_email, description, logo_path)
SELECT 'AI & Innovation Club', 'Technology', 'Innovation Lab Advisor', 'ai.club@university.edu',
       'Runs AI workshops, project showcases, and innovation meetups for club members.', 'assets/images/club_logo.svg'
WHERE NOT EXISTS (
    SELECT 1 FROM clubs WHERE name = 'AI & Innovation Club'
);

INSERT INTO club_admins (club_id, name, email, password)
SELECT c.club_id, 'Programming Club Admin', 'programming.admin@club.edu',
       '$2y$10$tTSd18ATcarfpkddMikPte6e3GGTfttYKt7RJlRsv.nOphzRqaroy'
FROM clubs c
WHERE c.name = 'Computer Programming Club'
AND NOT EXISTS (
    SELECT 1 FROM club_admins WHERE email = 'programming.admin@club.edu'
);

INSERT INTO club_admins (club_id, name, email, password)
SELECT c.club_id, 'AI Club Admin', 'ai.admin@club.edu',
       '$2y$10$tTSd18ATcarfpkddMikPte6e3GGTfttYKt7RJlRsv.nOphzRqaroy'
FROM clubs c
WHERE c.name = 'AI & Innovation Club'
AND NOT EXISTS (
    SELECT 1 FROM club_admins WHERE email = 'ai.admin@club.edu'
);

INSERT INTO events (club_id, title, description, image_path, category, event_date, event_time, registration_deadline, venue, capacity, created_by)
SELECT c.club_id, 'Programming Contest', 'Inter-department problem solving contest for club members.', 'assets/images/club_logo.svg', 'Competition', '2026-05-15', '10:00:00', '2026-05-12', 'Main Auditorium', 200, 1
FROM clubs c
WHERE c.name = 'Computer Programming Club'
AND NOT EXISTS (
    SELECT 1 FROM events WHERE title = 'Programming Contest' AND event_date = '2026-05-15'
);

INSERT INTO events (club_id, title, description, image_path, category, event_date, event_time, registration_deadline, venue, capacity, created_by)
SELECT c.club_id, 'AI Workshop', 'Hands-on workshop on practical AI tools and project ideas.', 'assets/images/club_logo.svg', 'Workshop', '2026-05-22', '14:30:00', '2026-05-19', 'CSE Lab 3', 120, 1
FROM clubs c
WHERE c.name = 'AI & Innovation Club'
AND NOT EXISTS (
    SELECT 1 FROM events WHERE title = 'AI Workshop' AND event_date = '2026-05-22'
);

UPDATE events e
INNER JOIN clubs c ON c.name = 'Computer Programming Club'
SET e.club_id = c.club_id
WHERE e.club_id IS NULL
  AND e.title = 'Programming Contest';

UPDATE events e
INNER JOIN clubs c ON c.name = 'AI & Innovation Club'
SET e.club_id = c.club_id
WHERE e.club_id IS NULL
  AND e.title = 'AI Workshop';
