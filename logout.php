<?php
session_start();

// remember who was logged in so we send them to the right login page
$was_admin = isset($_SESSION['admin_id']);

// clear the session
session_unset();
session_destroy();

if ($was_admin) {
    header("Location: Admin/login.php");
} else {
    header("Location: Guest/login.php");
}
exit;
?>
