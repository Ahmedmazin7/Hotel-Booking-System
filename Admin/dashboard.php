<?php
include '../includes/auth.php';
require_admin();

include '../config/db.php';

// small helper to run a COUNT query and return the number
function get_count($conn, $sql) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_row($result);
    return $row[0];
}

// 1. total rooms
$total_rooms = get_count($conn, "SELECT COUNT(*) FROM rooms");

// 2. rooms available today (not in maintenance and not booked for today)
$available_today = get_count($conn, "SELECT COUNT(*) FROM rooms
    WHERE status = 'Available'
    AND room_id NOT IN (
        SELECT room_id FROM bookings
        WHERE status IN ('Confirmed', 'Checked-in')
        AND check_in <= CURDATE() AND check_out > CURDATE()
    )");

// 3. active bookings
$active_bookings = get_count($conn, "SELECT COUNT(*) FROM bookings WHERE status IN ('Confirmed', 'Checked-in')");

// 4. total guests
$total_guests = get_count($conn, "SELECT COUNT(*) FROM guests");

// today's check-ins
$sql = "SELECT bookings.booking_id, guests.full_name, rooms.room_number, rooms.room_type,
               bookings.check_in, bookings.check_out, bookings.total_price, bookings.status
        FROM bookings
        JOIN guests ON bookings.guest_id = guests.guest_id
        JOIN rooms ON bookings.room_id = rooms.room_id
        WHERE bookings.check_in = CURDATE()
        ORDER BY rooms.room_number";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmt);
$todays_bookings = mysqli_stmt_get_result($stmt);

$page_title = "Dashboard";
include '../includes/header.php';
?>

<h1>Dashboard</h1>
<p class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>. Today is <?php echo date('l, d F Y'); ?>.</p>

<div class="stats">
  <div class="stat">
    <span class="number"><?php echo $total_rooms; ?></span>
    <span class="label">Total rooms</span>
  </div>
  <div class="stat">
    <span class="number"><?php echo $available_today; ?></span>
    <span class="label">Rooms available today</span>
  </div>
  <div class="stat">
    <span class="number"><?php echo $active_bookings; ?></span>
    <span class="label">Active bookings</span>
  </div>
  <div class="stat">
    <span class="number"><?php echo $total_guests; ?></span>
    <span class="label">Total guests</span>
  </div>
</div>

<div class="panel">
  <h2>Today's check-ins</h2>

  <?php if (mysqli_num_rows($todays_bookings) == 0) { ?>
    <p class="empty">No check-ins today.</p>
  <?php } else { ?>
    <table>
      <tr>
        <th>#</th>
        <th>Guest</th>
        <th>Room</th>
        <th>Check-in</th>
        <th>Check-out</th>
        <th>Total</th>
        <th>Status</th>
      </tr>
      <?php while ($row = mysqli_fetch_assoc($todays_bookings)) { ?>
        <tr>
          <td><?php echo $row['booking_id']; ?></td>
          <td><?php echo htmlspecialchars($row['full_name']); ?></td>
          <td><?php echo htmlspecialchars($row['room_number'] . ' (' . $row['room_type'] . ')'); ?></td>
          <td><?php echo $row['check_in']; ?></td>
          <td><?php echo $row['check_out']; ?></td>
          <td>$<?php echo number_format($row['total_price'], 2); ?></td>
          <td><span class="badge <?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
        </tr>
      <?php } ?>
    </table>
  <?php } ?>
</div>

<?php include '../includes/footer.php'; ?>
