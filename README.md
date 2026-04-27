# University Club Event Management

Raw PHP and MySQL based website and dashboard system for managing club events.

## Features

- Member registration and login
- Member profile update and password change
- Member event dashboard and status tracking
- Member event search, category filtering, and event detail pages
- Public website pages for Home, Events, About, Contact, and Event Details
- Public club directory with club-wise event links
- Admin login
- Create and manage university club profiles
- Create club events
- Create events with category, time, deadline, capacity, and image upload
- Edit and delete events
- Manage member directory
- Member event registration
- Admin approval or rejection of participants
- Admin reports for category performance, seat usage, and active members
- Admin profile update and password change
- Dashboard summaries for admins and members
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

1. Create a MySQL database named `university_club_events`
2. Import [`src/database/schema.sql`](/home/saim/6th/EventProject/src/database/schema.sql)
3. Update database credentials in [`src/backend/db.php`](/home/saim/6th/EventProject/src/backend/db.php) if needed

Default connection values:

- Host: `localhost`
- User: `event_app`
- Password: `event_app_2026`
- Database: `university_club_events`

## Default Admin Login

- Email: `admin@club.edu`
- Password: `admin123`

## Main Pages

- Home: `src/index.php`
- Public event details: `src/event-details.php?id=EVENT_ID`
- Public clubs directory: `src/clubs.php`
- Public events catalog: `src/events.php`
- About page: `src/about.php`
- Contact page: `src/contact.php`
- Member login: `src/student/login.php`
- Member registration: `src/student/register.php`
- Member dashboard: `src/student/dashboard.php`
- Member event details: `src/student/event-details.php?id=EVENT_ID`
- Member profile: `src/student/profile.php`
- Admin login: `src/admin/admin-login.php`
- Admin dashboard: `src/admin/admin-dashboard.php`
- Manage clubs: `src/admin/manage-clubs.php`
- Create event: `src/admin/create-event.php`
- Manage events: `src/admin/manage-events.php`
- Manage members: `src/admin/manage-students.php`
- Manage participants: `src/admin/manage-participants.php`
- Reports: `src/admin/reports.php`
- Admin profile: `src/admin/profile.php`
