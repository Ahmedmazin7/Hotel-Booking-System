<?php
// Database connection settings (XAMPP defaults)
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';               // XAMPP root has no password by default
$db_name = 'hotel_booking';

// Connect to MySQL using mysqli
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Stop the page if the connection fails
if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

// Use UTF-8 so names with special characters are stored correctly
mysqli_set_charset($conn, 'utf8mb4');