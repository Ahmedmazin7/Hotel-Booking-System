-- Hotel Room Booking System
-- Import this file in phpMyAdmin to create the database, tables and sample data.

CREATE DATABASE IF NOT EXISTS hotel_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE hotel_booking;

-- Admins (log in with username) created 
CREATE TABLE IF NOT EXISTS admins (
  admin_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Guests (log in with email) created
CREATE TABLE IF NOT EXISTS guests (
  guest_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  phone VARCHAR(20) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rooms
CREATE TABLE IF NOT EXISTS rooms (
  room_id INT AUTO_INCREMENT PRIMARY KEY,
  room_number VARCHAR(10) NOT NULL UNIQUE,
  room_type ENUM('Single','Double','Deluxe','Suite') NOT NULL,
  price_per_night DECIMAL(10,2) NOT NULL CHECK (price_per_night > 0),
  capacity INT NOT NULL CHECK (capacity BETWEEN 1 AND 6),
  status ENUM('Available','Maintenance') NOT NULL DEFAULT 'Available'
);

-- Bookings (one guest, one room)
CREATE TABLE IF NOT EXISTS bookings (
  booking_id INT AUTO_INCREMENT PRIMARY KEY,
  guest_id INT NOT NULL,
  room_id INT NOT NULL,
  check_in DATE NOT NULL,
  check_out DATE NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  status ENUM('Confirmed','Checked-in','Checked-out','Cancelled') NOT NULL DEFAULT 'Confirmed',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CHECK (check_out > check_in),
  FOREIGN KEY (guest_id) REFERENCES guests(guest_id) ON DELETE RESTRICT,
  FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE RESTRICT
);

-- ---------- Sample data ----------

-- Admin login: admin / admin123
INSERT IGNORE INTO admins (full_name, username, password) VALUES
('Hotel Admin', 'admin', '$2y$10$EmyoMyrdTMkSmPo85wo63uD0kGnv45IjwmxKM7FwGtLrukGlnT.4S');

-- Guest logins: password is guest123
INSERT IGNORE INTO guests (full_name, email, phone, password) VALUES
('Ahmed Ali', 'guest@example.com', '03001234567', '$2y$10$guD6EUk3iPps8PhAjLYF2uNbdZKvUmCUXGj93rb9XA72XfsBGpA0e'),
('Sara Khan', 'sara@example.com', '03007654321', '$2y$10$guD6EUk3iPps8PhAjLYF2uNbdZKvUmCUXGj93rb9XA72XfsBGpA0e'),
('Omar Farooq', 'omar@example.com', '03111234567', '$2y$10$guD6EUk3iPps8PhAjLYF2uNbdZKvUmCUXGj93rb9XA72XfsBGpA0e');

INSERT IGNORE INTO rooms (room_number, room_type, price_per_night, capacity, status) VALUES
('101', 'Single', 50.00, 1, 'Available'),
('102', 'Single', 50.00, 1, 'Available'),
('201', 'Double', 80.00, 2, 'Available'),
('202', 'Double', 80.00, 2, 'Maintenance'),
('301', 'Deluxe', 120.00, 3, 'Available'),
('401', 'Suite', 200.00, 4, 'Available');

-- Booking dates are relative to the day you import, so the dashboard always has something to show
INSERT INTO bookings (guest_id, room_id, check_in, check_out, total_price, status) VALUES
((SELECT guest_id FROM guests WHERE email = 'guest@example.com'), (SELECT room_id FROM rooms WHERE room_number = '101'), CURDATE(), CURDATE() + INTERVAL 2 DAY, 100.00, 'Confirmed'),
((SELECT guest_id FROM guests WHERE email = 'sara@example.com'), (SELECT room_id FROM rooms WHERE room_number = '201'), CURDATE() - INTERVAL 1 DAY, CURDATE() + INTERVAL 2 DAY, 240.00, 'Checked-in'),
((SELECT guest_id FROM guests WHERE email = 'omar@example.com'), (SELECT room_id FROM rooms WHERE room_number = '301'), CURDATE() + INTERVAL 5 DAY, CURDATE() + INTERVAL 7 DAY, 240.00, 'Confirmed'),
((SELECT guest_id FROM guests WHERE email = 'sara@example.com'), (SELECT room_id FROM rooms WHERE room_number = '401'), CURDATE() - INTERVAL 10 DAY, CURDATE() - INTERVAL 7 DAY, 600.00, 'Checked-out'),
((SELECT guest_id FROM guests WHERE email = 'omar@example.com'), (SELECT room_id FROM rooms WHERE room_number = '102'), CURDATE(), CURDATE() + INTERVAL 1 DAY, 50.00, 'Cancelled');
