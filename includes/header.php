<?php
// get the current file name so we can highlight the active link
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($page_title); ?> · StayEase</title>
  <link rel="stylesheet" href="/HMS-Project/css/style.css">
</head>
<body class="app">

  <header class="topbar">
    <a href="/HMS-Project/Admin/dashboard.php" class="brand">StayEase Admin</a>

    <nav>
      <a href="/HMS-Project/Admin/dashboard.php" class="<?php if ($current_page == 'dashboard.php') echo 'active'; ?>">Dashboard</a>
      <a href="/HMS-Project/Admin/rooms.php" class="<?php if ($current_page == 'rooms.php') echo 'active'; ?>">Rooms</a>
      <a href="/HMS-Project/Admin/guests.php" class="<?php if ($current_page == 'guests.php') echo 'active'; ?>">Guests</a>
      <a href="/HMS-Project/Admin/bookings.php" class="<?php if ($current_page == 'bookings.php') echo 'active'; ?>">Bookings</a>
      <a href="/HMS-Project/logout.php">Log out</a>
    </nav>
  </header>

  <main class="container">
