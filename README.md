# Premier University Event Management System

Raw PHP and MySQL based university event management system for Premier University.

## Features

- Student registration and login
- Student profile update and password change
- Student event dashboard and status tracking
- Admin login
- Create university events
- Edit and delete events
- Manage student directory
- Student event registration
- Admin approval or rejection of participants
- Admin profile update and password change
- Dashboard summaries for admins and students
- CSRF protection for login, registration, profile, event, and approval forms
- Server-side validation for emails, passwords, dates, capacities, and uploads
- Event image upload support for JPG, PNG, and WebP files up to 2 MB

## Tech Stack

- PHP
- MySQL
- HTML
- CSS

## Security Notes

- Passwords are stored with PHP `password_hash()`
- Login regenerates the session ID after successful authentication
- Form submissions include CSRF tokens to reduce forged request risk
- Event uploads are checked by file size, extension, and detected MIME type

## Project Entry

- Start from `src/index.php`

## Database Setup

1. Create a MySQL database named `premier_university_events`
2. Import [`src/database/schema.sql`](/home/saim/6th/EventProject/src/database/schema.sql)
3. Update database credentials in [`src/backend/db.php`](/home/saim/6th/EventProject/src/backend/db.php) if needed

Default connection values:

- Host: `localhost`
- User: `event_app`
- Password: `event_app_2026`
- Database: `premier_university_events`

## Default Admin Login

- Email: `admin@puc.ac.bd`
- Password: `admin123`

## Main Pages

- Home: `src/index.php`
- Student login: `src/student/login.php`
- Student registration: `src/student/register.php`
- Student dashboard: `src/student/dashboard.php`
- Student profile: `src/student/profile.php`
- Admin login: `src/admin/admin-login.php`
- Admin dashboard: `src/admin/admin-dashboard.php`
- Create event: `src/admin/create-event.php`
- Manage events: `src/admin/manage-events.php`
- Manage students: `src/admin/manage-students.php`
- Manage participants: `src/admin/manage-participants.php`
- Admin profile: `src/admin/profile.php`
# A-University-Event-Management-System
