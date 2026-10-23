<?php
// get the current file name so we can highlight the active link
$current_page = basename($_SERVER['PHP_SELF']);

$admin_name = $_SESSION['admin_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($page_title); ?> · Stayeaase</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="/HMS-Project/css/admin.css">
  <?php if (isset($page_css)) { ?>
    <link rel="stylesheet" href="/HMS-Project/css/<?php echo $page_css; ?>">
  <?php } ?>
</head>
<body class="admin">

<div class="shell">

  <aside class="sidebar">
    <a href="/HMS-Project/Admin/dashboard.php" class="brand">Stay<span>ease</span></a>

    <nav class="side-nav">
      <a href="/HMS-Project/Admin/dashboard.php" class="<?php if ($current_page == 'dashboard.php') echo 'active'; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
        Dashboard
      </a>
      <a href="/HMS-Project/Admin/bookings.php" class="<?php if ($current_page == 'bookings.php') echo 'active'; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        Bookings
      </a>
      <a href="/HMS-Project/Admin/rooms.php" class="<?php if ($current_page == 'rooms.php') echo 'active'; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4v16M2 8h18a2 2 0 0 1 2 2v10M2 17h20M6 8v9"/></svg>
        Rooms
      </a>
      <a href="/HMS-Project/Admin/guests.php" class="<?php if ($current_page == 'guests.php') echo 'active'; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Guests
      </a>
    </nav>

    <hr class="side-divider">

    <nav class="side-nav">
      <a href="/HMS-Project/Guest/login.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
        Guest site
      </a>
      <a href="/HMS-Project/logout.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
        Log out
      </a>
    </nav>
  </aside>

  <div class="main">

    <header class="page-header">
      <div>
        <p class="eyebrow">Welcome back, <?php echo htmlspecialchars($admin_name); ?></p>
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
      </div>

      <div class="header-actions">
        <a href="/HMS-Project/Admin/guests.php" class="icon-btn" title="Search guests">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        </a>
        <span class="avatar"><?php echo htmlspecialchars(get_initials($admin_name)); ?></span>
        <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
      </div>
    </header>

    <main>
