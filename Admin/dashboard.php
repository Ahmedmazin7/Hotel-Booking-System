<?php
include '../includes/auth.php';
require_admin();

include '../config/db.php';

// small helper to run a query that returns one number
function get_count($conn, $sql) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_row($result);
    return $row[0];
}

// ---------- room status ----------
$total_rooms = get_count($conn, "SELECT COUNT(*) FROM rooms");

// rooms available today (not in maintenance and not booked for today)
$available_today = get_count($conn, "SELECT COUNT(*) FROM rooms
    WHERE status = 'Available'
    AND room_id NOT IN (
        SELECT room_id FROM bookings
        WHERE status IN ('Confirmed', 'Checked-in')
        AND check_in <= CURDATE() AND check_out > CURDATE()
    )");

$maintenance_rooms = get_count($conn, "SELECT COUNT(*) FROM rooms WHERE status = 'Maintenance'");

$occupied_rooms = $total_rooms - $available_today - $maintenance_rooms;

// widths for the coloured bar (in percent)
$occupied_percent = 0;
$available_percent = 0;
$maintenance_percent = 0;
if ($total_rooms > 0) {
    $occupied_percent = $occupied_rooms / $total_rooms * 100;
    $available_percent = $available_today / $total_rooms * 100;
    $maintenance_percent = $maintenance_rooms / $total_rooms * 100;
}

// ---------- stat cards ----------
$active_bookings = get_count($conn, "SELECT COUNT(*) FROM bookings WHERE status IN ('Confirmed', 'Checked-in')");

$checkouts_today = get_count($conn, "SELECT COUNT(*) FROM bookings WHERE check_out = CURDATE() AND status != 'Cancelled'");

$total_guests = get_count($conn, "SELECT COUNT(*) FROM guests");

$revenue_month = get_count($conn, "SELECT COALESCE(SUM(total_price), 0) FROM bookings
    WHERE status != 'Cancelled'
    AND YEAR(check_in) = YEAR(CURDATE()) AND MONTH(check_in) = MONTH(CURDATE())");

// ---------- today's arrivals ----------
$sql = "SELECT guests.full_name, rooms.room_number, rooms.room_type, bookings.status,
               DATEDIFF(bookings.check_out, bookings.check_in) AS nights
        FROM bookings
        JOIN guests ON bookings.guest_id = guests.guest_id
        JOIN rooms ON bookings.room_id = rooms.room_id
        WHERE bookings.check_in = CURDATE() AND bookings.status != 'Cancelled'
        ORDER BY rooms.room_number";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmt);
$arrivals = mysqli_stmt_get_result($stmt);
$arrivals_count = mysqli_num_rows($arrivals);

// ---------- recent bookings ----------
$sql = "SELECT bookings.booking_id, guests.full_name, rooms.room_number, rooms.room_type,
               bookings.check_in, bookings.check_out, bookings.status
        FROM bookings
        JOIN guests ON bookings.guest_id = guests.guest_id
        JOIN rooms ON bookings.room_id = rooms.room_id
        ORDER BY bookings.booking_id DESC
        LIMIT 6";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmt);
$recent_bookings = mysqli_stmt_get_result($stmt);

$page_title = "Dashboard";
$page_css = "dashboard.css";
include '../includes/header.php';
?>

<div class="dashboard-grid">

  <!-- left column -->
  <div class="col">

    <section class="card">
      <div class="card-head">
        <h2>Today's arrivals</h2>
        <a href="bookings.php" class="card-link">View bookings</a>
      </div>

      <?php if ($arrivals_count == 0) { ?>
        <p class="empty">No guests are arriving today.</p>
      <?php } else { ?>
        <?php while ($row = mysqli_fetch_assoc($arrivals)) { ?>
          <div class="arrival">
            <span class="arrival-avatar"><?php echo htmlspecialchars(get_initials($row['full_name'])); ?></span>
            <div class="arrival-info">
              <span class="arrival-name"><?php echo htmlspecialchars($row['full_name']); ?></span>
              <span class="arrival-meta">
                Room <?php echo htmlspecialchars($row['room_number']); ?> ·
                <?php echo htmlspecialchars($row['room_type']); ?> ·
                <?php echo $row['nights']; ?> <?php echo $row['nights'] == 1 ? 'night' : 'nights'; ?>
              </span>
            </div>
            <span class="badge <?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span>
          </div>
        <?php } ?>
      <?php } ?>
    </section>

    <section class="card">
      <div class="card-head">
        <h2>Recent bookings</h2>
        <a href="bookings.php" class="card-link">View all</a>
      </div>

      <?php if (mysqli_num_rows($recent_bookings) == 0) { ?>
        <p class="empty">No bookings yet.</p>
      <?php } else { ?>
        <div class="table-wrap">
          <table>
            <tr>
              <th>#</th>
              <th>Guest</th>
              <th>Room</th>
              <th>Check-in</th>
              <th>Check-out</th>
              <th>Status</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($recent_bookings)) { ?>
              <tr>
                <td class="muted"><?php echo $row['booking_id']; ?></td>
                <td class="strong"><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td><?php echo htmlspecialchars($row['room_number'] . ' ' . $row['room_type']); ?></td>
                <td><?php echo date('d M', strtotime($row['check_in'])); ?></td>
                <td><?php echo date('d M', strtotime($row['check_out'])); ?></td>
                <td><span class="badge <?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
              </tr>
            <?php } ?>
          </table>
        </div>
      <?php } ?>
    </section>

  </div>

  <!-- right column -->
  <div class="col">

    <section class="card">
      <div class="card-head">
        <h2>Room status</h2>
        <span class="muted"><?php echo $total_rooms; ?> rooms</span>
      </div>

      <div class="room-bar">
        <span class="bar-occupied" style="width: <?php echo $occupied_percent; ?>%"></span>
        <span class="bar-available" style="width: <?php echo $available_percent; ?>%"></span>
        <span class="bar-maintenance" style="width: <?php echo $maintenance_percent; ?>%"></span>
      </div>

      <div class="room-counts">
        <div>
          <span class="small-label">Occupied</span>
          <span class="count-value"><?php echo $occupied_rooms; ?></span>
        </div>
        <div>
          <span class="small-label">Available</span>
          <span class="count-value"><?php echo $available_today; ?></span>
        </div>
        <div>
          <span class="small-label">Maintenance</span>
          <span class="count-value"><?php echo $maintenance_rooms; ?></span>
        </div>
      </div>
    </section>

    <div class="stat-grid">
      <div class="card stat-card">
        <span class="stat-icon teal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        </span>
        <span class="small-label">Active bookings</span>
        <span class="stat-value"><?php echo $active_bookings; ?></span>
      </div>

      <div class="card stat-card">
        <span class="stat-icon purple">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
        </span>
        <span class="small-label">Check-outs today</span>
        <span class="stat-value"><?php echo $checkouts_today; ?></span>
      </div>

      <div class="card stat-card">
        <span class="stat-icon orange">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </span>
        <span class="small-label">Total guests</span>
        <span class="stat-value"><?php echo $total_guests; ?></span>
      </div>

      <div class="card stat-card">
        <span class="stat-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </span>
        <span class="small-label">Revenue, <?php echo date('F'); ?></span>
        <span class="stat-value">$<?php echo number_format($revenue_month); ?></span>
      </div>
    </div>

    <section class="attention">
      <div>
        <span class="small-label">Today</span>
        <?php if ($arrivals_count == 0) { ?>
          <h2>No guests are arriving today</h2>
        <?php } else { ?>
          <h2><?php echo $arrivals_count; ?> <?php echo $arrivals_count == 1 ? 'guest is' : 'guests are'; ?> arriving today</h2>
        <?php } ?>
      </div>
      <a href="bookings.php" class="btn-light">Manage bookings</a>
    </section>

  </div>

</div>

<?php include '../includes/footer.php'; ?>
