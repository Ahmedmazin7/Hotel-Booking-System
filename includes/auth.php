<?php
// start the session if it is not started yet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// use this at the top of every admin page
function require_admin() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: /HMS-Project/Admin/login.php");
        exit;
    }
}

// turn "Ahmed Ali" into "AA" for the round avatars
function get_initials($name) {
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $word) {
        if ($word != '') {
            $initials .= strtoupper($word[0]);
        }
    }
    return substr($initials, 0, 2);
}

// use this at the top of every guest page
function require_guest() {
    if (!isset($_SESSION['guest_id'])) {
        header("Location: /HMS-Project/Guest/login.php");
        exit;
    }
}
?>
