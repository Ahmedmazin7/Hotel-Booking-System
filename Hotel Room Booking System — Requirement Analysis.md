# Hotel Room Booking System — Requirement Analysis

Oct 20, 2026 · @ahmed

## Project overview

A small web app where guests book hotel rooms online and an admin manages rooms, guests and bookings. Built with PHP, MySQL and XAMPP, with 4 tables and full CRUD.

**Users**

| Role | Stored in | How they get in | What they do |
| --- | --- | --- | --- |
| Admin | `admins` table | Admin Login page (account seeded in the database) | Manages rooms, guests and all bookings; sees the dashboard |
| Guest | `guests` table | Signs up, then uses the Guest Login page | Browses available rooms, books one, views and cancels own bookings |

Admins and guests are kept in separate tables with separate login pages. Each login checks only its own table and starts its own session, so a guest can never log in as an admin.

## Functional requirements

**Everyone**

1. FR-01 Guest Sign-up (name, email, phone, password), saved to the `guests` table.
2. FR-02 Guest Login checks the `guests` table; Admin Login checks the `admins` table; both have Log out.
3. FR-03 Pages are protected: a guest session cannot open admin pages and an admin session cannot open guest pages; logged-out users are sent to the matching login page.

**Admin**

1. FR-04 Dashboard shows total rooms, rooms available today, active bookings and total guests.
2. FR-05 Rooms: add, list, edit, delete a room (number, type, price per night, capacity, status).
3. FR-06 Guests: add, list, edit, delete a guest account.
4. FR-07 Bookings: create a booking for any guest, list all bookings, edit dates/status, delete a booking.
5. FR-08 Search guests by name/email and filter bookings by guest name or check-in date.

**Guest**

1. FR-09 Browse rooms and check availability for chosen dates.
2. FR-10 Book a room; total price is calculated automatically.
3. FR-11 View own bookings and cancel an upcoming one.

## Database design

Database `hotel_booking` has 4 tables. `admins` and `guests` hold the two kinds of login separately. Each booking links one guest to one room; `admins` stands on its own with no foreign key.

**admins**

| Column | Type | Key / rule |
| --- | --- | --- |
| admin\_id | INT AUTO\_INCREMENT | Primary key |
| full\_name | VARCHAR(100) | Required |
| username | VARCHAR(50) | Required, unique |
| password | VARCHAR(255) | Hashed with `password_hash()` |
| created\_at | TIMESTAMP | Default now |

**guests**

| Column | Type | Key / rule |
| --- | --- | --- |
| guest\_id | INT AUTO\_INCREMENT | Primary key |
| full\_name | VARCHAR(100) | Required |
| email | VARCHAR(100) | Required, unique (used to log in) |
| phone | VARCHAR(20) | Required |
| password | VARCHAR(255) | Hashed with `password_hash()` |
| created\_at | TIMESTAMP | Default now |

**rooms**

| Column | Type | Key / rule |
| --- | --- | --- |
| room\_id | INT AUTO\_INCREMENT | Primary key |
| room\_number | VARCHAR(10) | Required, unique |
| room\_type | ENUM('Single','Double','Deluxe','Suite') | Required |
| price\_per\_night | DECIMAL(10,2) | Required, > 0 |
| capacity | INT | 1 to 6 |
| status | ENUM('Available','Maintenance') | Default 'Available' |

**bookings**

| Column | Type | Key / rule |
| --- | --- | --- |
| booking\_id | INT AUTO\_INCREMENT | Primary key |
| guest\_id | INT | Foreign key → guests.guest\_id |
| room\_id | INT | Foreign key → rooms.room\_id |
| check\_in | DATE | Required |
| check\_out | DATE | Required, after check\_in |
| total\_price | DECIMAL(10,2) | Nights × price\_per\_night |
| status | ENUM('Confirmed','Checked-in','Checked-out','Cancelled') | Default 'Confirmed' |
| created\_at | TIMESTAMP | Default now |

Relationships: one guest has many bookings; one room has many bookings. Foreign keys use ON DELETE RESTRICT, so a room or guest with bookings cannot be deleted until those bookings are removed. Admins log in with a username; guests log in with their email.

&#91;embedded content: entity relationships · 3 tables, 2 foreign keys\]

## Pages and modules

6 functional pages beyond Login and Sign-up (minimum required: 3).

| Page | File | Who | Create | Read | Update | Delete |
| --- | --- | --- | --- | --- | --- | --- |
| Dashboard | admin/dashboard.php | Admin | — | Counts, today's bookings | — | — |
| Rooms | admin/rooms.php | Admin | Add room | List rooms | Edit room | Delete room |
| Guests | admin/guests.php | Admin | Add guest | List + search | Edit guest | Delete guest |
| Bookings | admin/bookings.php | Admin | New booking | List + filter | Edit dates/status | Delete booking |
| Book a Room | guest/rooms.php | Guest | Make booking | Available rooms for dates | — | — |
| My Bookings | guest/my\_bookings.php | Guest | — | Own bookings | Cancel (status → Cancelled) | — |
| Guest Login / Sign-up / Admin Login / Logout | login.php, signup.php, admin/login.php, logout.php | Everyone | Account | — | — | — |

Shared files: `config/db.php` (mysqli connection), `includes/header.php` and `footer.php` (layout + nav), `includes/auth.php` (session and role checks), `assets/style.css`, and `database/hotel_booking.sql` (tables + sample data).

## Validation and business rules

**Business rules**

- Total price = nights × price\_per\_night, where nights = check\_out − check\_in. Calculated in PHP on save; never typed by the user.
- No double booking: a room cannot be booked if another non-cancelled booking overlaps the dates. Overlap test: new check\_in < existing check\_out AND new check\_out > existing check\_in.
- Rooms in Maintenance cannot be booked.
- Guests can cancel only their own bookings, and only before check-in.

**Form validation** (HTML5 `required`/`type` in the browser, re-checked in PHP on the server)

- All required fields filled; email in a valid format; phone 7 to 15 digits.
- Password at least 6 characters; Sign-up asks for it twice and both must match.
- Email and room number must be unique.
- Price above 0; capacity 1 to 6.
- Check-out after check-in; new bookings cannot start in the past.
- Errors show above the form and keep the values already typed.

**Security basics**

- Prepared statements for every query (no SQL injection).
- `htmlspecialchars()` on all output.
- Passwords stored with `password_hash()`, checked with `password_verify()`.

## Non-functional and software requirements

- Simple, clean interface in plain HTML and CSS (no frameworks); works on laptop and phone widths.
- Clear success and error messages after every action; delete asks for confirmation.
- Code organized into shared config, includes and per-page files.

| Software | Version / note |
| --- | --- |
| XAMPP (Apache + MySQL/MariaDB + PHP) | 8.x |
| PHP | 8.0 or later, mysqli extension |
| phpMyAdmin | Bundled with XAMPP, to import the .sql file |
| Web browser | Chrome, Edge or Firefox |
| Code editor | VS Code |
| OS | Windows 10/11 |

## Scope and marking checklist

Out of scope, to keep it simple: online payments, email notifications, room photos/uploads, multiple hotels, and reports or exports.

| Assignment requirement | How this project meets it |
| --- | --- |
| PHP + MySQL connectivity | `config/db.php` with mysqli |
| Minimum 3 related tables | 4 tables: admins, guests, rooms, bookings; bookings has 2 foreign keys |
| Minimum 3 functional pages (excl. login/sign-up) | 6 pages: Dashboard, Rooms, Guests, Bookings, Book a Room, My Bookings |
| CRUD | Full CRUD on Rooms, Guests and Bookings |
| Forms for input | Room, guest, booking, login and sign-up forms |
| Basic form validation | Client-side HTML5 + server-side PHP checks |
| Primary and foreign keys | PK on every table; bookings.guest\_id and bookings.room\_id |
| Simple HTML/CSS interface | One shared stylesheet and layout |

Next: confirm or change this scope, then build the code, the documentation PDF and the video script.
