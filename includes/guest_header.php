<?php
// get the current file name so we can highlight the active link
$current_page = basename($_SERVER['PHP_SELF']);

$guest_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($page_title); ?> · StayEase</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="/HMS-Project/css/admin.css">
  <link rel="stylesheet" href="/HMS-Project/css/guest.css">
  <?php if (isset($page_css)) { ?>
    <link rel="stylesheet" href="/HMS-Project/css/<?php echo $page_css; ?>">
  <?php } ?>
</head>
<body class="admin">

<header class="topbar">
  <a href="/HMS-Project/Guest/rooms.php" class="brand">Stay<span>Ease</span></a>

  <nav class="top-nav">
    <a href="/HMS-Project/Guest/rooms.php" class="<?php if ($current_page == 'rooms.php') echo 'active'; ?>">Book a Room</a>
    <?php if ($guest_name != "") { ?>
      <a href="/HMS-Project/Guest/my_bookings.php" class="<?php if ($current_page == 'my_bookings.php') echo 'active'; ?>">My Bookings</a>
    <?php } ?>
  </nav>

  <div class="header-actions">
    <?php if ($guest_name != "") { ?>
      <span class="avatar"><?php echo htmlspecialchars(get_initials($guest_name)); ?></span>
      <span class="admin-name"><?php echo htmlspecialchars($guest_name); ?></span>
      <a href="/HMS-Project/logout.php" class="btn-secondary">Log out</a>
    <?php } else { ?>
      <a href="/HMS-Project/Guest/login.php" class="btn-secondary">Log in</a>
      <a href="/HMS-Project/Guest/signup.php" class="btn-primary">Sign up</a>
    <?php } ?>
  </div>
</header>

<div class="guest-main">

  <div class="page-header">
    <div>
      <p class="eyebrow"><?php echo htmlspecialchars($page_eyebrow); ?></p>
      <h1><?php echo htmlspecialchars($page_title); ?></h1>
    </div>
  </div>
