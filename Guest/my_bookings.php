<?php
include '../includes/auth.php';
require_guest();

include '../config/db.php';

$guest_id = $_SESSION['guest_id'];
$today = date('Y-m-d');

$error = "";
$success = "";

// number of nights between two dates
function count_nights($check_in, $check_out) {
    $in = new DateTime($check_in);
    $out = new DateTime($check_out);
    return (int) $in->diff($out)->format('%r%a');
}

// a guest can cancel only a confirmed booking that hasn't started yet
function can_cancel($booking, $today) {
    return $booking['status'] == 'Confirmed' && $booking['check_in'] >= $today;
}

// ---------- messages after a redirect ----------
if (isset($_GET['msg']) && $_GET['msg'] == 'cancelled') {
    $success = "Your booking has been cancelled.";
}

// ---------- CANCEL a booking ----------
if (isset($_POST['cancel'])) {
    $booking_id = (int) $_POST['booking_id'];

    // guest_id in the WHERE makes sure guests can only cancel their own bookings
    $stmt = mysqli_prepare($conn, "SELECT booking_id, check_in, status FROM bookings WHERE booking_id = ? AND guest_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $booking_id, $guest_id);
    mysqli_stmt_execute($stmt);
    $booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$booking) {
        $error = "Booking not found.";
    } elseif (!can_cancel($booking, $today)) {
        $error = "Booking #" . $booking_id . " can't be cancelled because it has already started or is no longer active.";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE bookings SET status = 'Cancelled' WHERE booking_id = ? AND guest_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $booking_id, $guest_id);
        mysqli_stmt_execute($stmt);

        header("Location: my_bookings.php?msg=cancelled");
        exit;
    }
}

// ---------- list this guest's bookings ----------
$sql = "SELECT bookings.*, rooms.room_number, rooms.room_type
        FROM bookings
        JOIN rooms ON bookings.room_id = rooms.room_id
        WHERE bookings.guest_id = ?
        ORDER BY bookings.check_in DESC, bookings.booking_id DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $guest_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// split into upcoming / current stays and everything else
$upcoming = array();
$past = array();
while ($row = mysqli_fetch_assoc($result)) {
    $active = ($row['status'] == 'Confirmed' || $row['status'] == 'Checked-in') && $row['check_out'] >= $today;
    if ($active) {
        $upcoming[] = $row;
    } else {
        $past[] = $row;
    }
}
// soonest stay first
$upcoming = array_reverse($upcoming);

$page_title = "My Bookings";
$page_eyebrow = "Your stays";
$page_css = "my-bookings.css";
include '../includes/guest_header.php';
?>

<?php if ($success != "") { ?>
  <p class="alert success"><?php echo htmlspecialchars($success); ?></p>
<?php } ?>

<?php if ($error != "") { ?>
  <p class="alert error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<?php
// one section per list, so both use the same markup
$sections = array(
    array('title' => 'Upcoming', 'rows' => $upcoming, 'empty' => 'You have no upcoming stays.'),
    array('title' => 'Past & cancelled', 'rows' => $past, 'empty' => 'Nothing here yet.'),
);
?>

<?php foreach ($sections as $section) { ?>
  <section class="card stay-section">
    <div class="card-head">
      <h2><?php echo $section['title']; ?></h2>
      <?php if ($section['title'] == 'Upcoming') { ?>
        <a href="rooms.php" class="card-link">Book another room</a>
      <?php } else { ?>
        <span class="muted"><?php echo count($section['rows']); ?> <?php echo count($section['rows']) == 1 ? 'booking' : 'bookings'; ?></span>
      <?php } ?>
    </div>

    <?php if (count($section['rows']) == 0) { ?>
      <p class="empty"><?php echo $section['empty']; ?></p>
    <?php } else { ?>
      <div class="stay-list">
        <?php foreach ($section['rows'] as $row) { ?>
          <?php $nights = count_nights($row['check_in'], $row['check_out']); ?>
          <div class="stay-item">
            <span class="stay-room"><?php echo htmlspecialchars($row['room_number']); ?></span>

            <div class="stay-info">
              <span class="stay-title">
                <?php echo htmlspecialchars($row['room_type']); ?> room
                <span class="stay-id">#<?php echo $row['booking_id']; ?></span>
              </span>
              <span class="stay-dates">
                <?php echo date('D d M', strtotime($row['check_in'])); ?> → <?php echo date('D d M Y', strtotime($row['check_out'])); ?>
              </span>
            </div>

            <div class="stay-total">
              <span class="stay-price">$<?php echo number_format($row['total_price'], 2); ?></span>
              <span class="stay-nights"><?php echo $nights; ?> <?php echo $nights == 1 ? 'night' : 'nights'; ?></span>
            </div>

            <span class="badge <?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span>

            <div class="stay-actions">
              <?php if (can_cancel($row, $today)) { ?>
                <form action="my_bookings.php" method="post" onsubmit="return confirm('Cancel booking #<?php echo $row['booking_id']; ?>? This cannot be undone.');">
                  <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
                  <button type="submit" name="cancel" class="btn-small danger">Cancel</button>
                </form>
              <?php } ?>
            </div>
          </div>
        <?php } ?>
      </div>
    <?php } ?>
  </section>
<?php } ?>

<?php include '../includes/guest_footer.php'; ?>
