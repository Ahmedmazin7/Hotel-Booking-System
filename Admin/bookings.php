<?php
include '../includes/auth.php';
require_admin();

include '../config/db.php';

// allowed values (same as the ENUM in the database)
$booking_statuses = array('Confirmed', 'Checked-in', 'Checked-out', 'Cancelled');

$error = "";
$success = "";

// form values (empty for a new booking)
$booking_id = 0;
$guest_id = 0;
$room_id = 0;
$check_in = "";
$check_out = "";
$status = "Confirmed";

// filter values
$filter_guest = "";
$filter_date = "";
if (isset($_GET['filter_guest'])) {
    $filter_guest = trim($_GET['filter_guest']);
}
if (isset($_GET['filter_date'])) {
    $filter_date = trim($_GET['filter_date']);
}

// check that a date is a real date in YYYY-MM-DD format
function valid_date($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') == $date;
}

// number of nights between two dates
function count_nights($check_in, $check_out) {
    $in = new DateTime($check_in);
    $out = new DateTime($check_out);
    return (int) $in->diff($out)->format('%r%a');
}

// ---------- messages after a redirect ----------
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added') {
        $success = "Booking created successfully.";
    } elseif ($_GET['msg'] == 'updated') {
        $success = "Booking updated successfully.";
    } elseif ($_GET['msg'] == 'deleted') {
        $success = "Booking deleted successfully.";
    }
}

// ---------- DELETE a booking ----------
if (isset($_POST['delete'])) {
    $delete_id = (int) $_POST['booking_id'];

    $stmt = mysqli_prepare($conn, "DELETE FROM bookings WHERE booking_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    mysqli_stmt_execute($stmt);

    header("Location: bookings.php?msg=deleted");
    exit;
}

// ---------- which booking are we editing? ----------
if (isset($_POST['save'])) {
    $booking_id = (int) $_POST['booking_id'];
} elseif (isset($_GET['edit'])) {
    $booking_id = (int) $_GET['edit'];
}

// load the booking being edited (guest and room can't be changed)
$editing = null;
if ($booking_id != 0) {
    $sql = "SELECT bookings.*, guests.full_name, rooms.room_number, rooms.room_type, rooms.price_per_night
            FROM bookings
            JOIN guests ON bookings.guest_id = guests.guest_id
            JOIN rooms ON bookings.room_id = rooms.room_id
            WHERE bookings.booking_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $booking_id);
    mysqli_stmt_execute($stmt);
    $editing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($editing) {
        $guest_id = $editing['guest_id'];
        $room_id = $editing['room_id'];
        $check_in = $editing['check_in'];
        $check_out = $editing['check_out'];
        $status = $editing['status'];
    } else {
        $error = "Booking not found.";
        $booking_id = 0;
    }
}

// ---------- CREATE or UPDATE a booking ----------
if (isset($_POST['save']) && $error == "") {
    $check_in = trim($_POST['check_in']);
    $check_out = trim($_POST['check_out']);
    $status = $_POST['status'];

    // guest and room only come from the form when creating
    if ($booking_id == 0) {
        $guest_id = (int) $_POST['guest_id'];
        $room_id = (int) $_POST['room_id'];
    }

    // find the guest and room
    $stmt = mysqli_prepare($conn, "SELECT guest_id FROM guests WHERE guest_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $guest_id);
    mysqli_stmt_execute($stmt);
    $guest = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    $stmt = mysqli_prepare($conn, "SELECT room_number, price_per_night, status FROM rooms WHERE room_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $room_id);
    mysqli_stmt_execute($stmt);
    $room = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    // validate
    if (!$guest) {
        $error = "Please choose a guest.";
    } elseif (!$room) {
        $error = "Please choose a room.";
    } elseif (!valid_date($check_in) || !valid_date($check_out)) {
        $error = "Please enter valid check-in and check-out dates.";
    } elseif (count_nights($check_in, $check_out) < 1) {
        $error = "Check-out must be after check-in.";
    } elseif (count_nights($check_in, $check_out) > 365) {
        $error = "A booking can be at most 365 nights.";
    } elseif ($booking_id == 0 && $check_in < date('Y-m-d')) {
        $error = "New bookings cannot start in the past.";
    } elseif ($booking_id == 0 && $room['status'] == 'Maintenance') {
        $error = "Room " . $room['room_number'] . " is under maintenance and can't be booked.";
    } elseif (!in_array($status, $booking_statuses)) {
        $error = "Please choose a valid status.";
    } elseif ($status != 'Cancelled') {
        // no double booking: another non-cancelled booking for this room must not overlap
        $sql = "SELECT COUNT(*) FROM bookings
                WHERE room_id = ? AND booking_id != ? AND status != 'Cancelled'
                AND check_in < ? AND check_out > ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iiss", $room_id, $booking_id, $check_out, $check_in);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_row(mysqli_stmt_get_result($stmt));

        if ($row[0] > 0) {
            $error = "Room " . $room['room_number'] . " is already booked for some of these dates.";
        }
    }

    // save if there are no errors
    if ($error == "") {
        $nights = count_nights($check_in, $check_out);

        if ($booking_id == 0) {
            $total_price = $nights * $room['price_per_night'];

            $stmt = mysqli_prepare($conn, "INSERT INTO bookings (guest_id, room_id, check_in, check_out, total_price, status) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iissds", $guest_id, $room_id, $check_in, $check_out, $total_price, $status);
            mysqli_stmt_execute($stmt);

            header("Location: bookings.php?msg=added");
            exit;
        } else {
            // only recalculate the total if the dates changed
            if ($check_in != $editing['check_in'] || $check_out != $editing['check_out']) {
                $total_price = $nights * $room['price_per_night'];
            } else {
                $total_price = $editing['total_price'];
            }

            $stmt = mysqli_prepare($conn, "UPDATE bookings SET check_in = ?, check_out = ?, total_price = ?, status = ? WHERE booking_id = ?");
            mysqli_stmt_bind_param($stmt, "ssdsi", $check_in, $check_out, $total_price, $status, $booking_id);
            mysqli_stmt_execute($stmt);

            header("Location: bookings.php?msg=updated");
            exit;
        }
    }
}

// ---------- lists for the form ----------
$stmt = mysqli_prepare($conn, "SELECT guest_id, full_name, email FROM guests ORDER BY full_name");
mysqli_stmt_execute($stmt);
$all_guests = mysqli_stmt_get_result($stmt);

$stmt = mysqli_prepare($conn, "SELECT room_id, room_number, room_type, price_per_night FROM rooms WHERE status = 'Available' ORDER BY room_number");
mysqli_stmt_execute($stmt);
$available_rooms = mysqli_stmt_get_result($stmt);

// ---------- list bookings (with filters) ----------
$sql = "SELECT bookings.*, guests.full_name, rooms.room_number, rooms.room_type
        FROM bookings
        JOIN guests ON bookings.guest_id = guests.guest_id
        JOIN rooms ON bookings.room_id = rooms.room_id
        WHERE 1 = 1";
$types = "";
$params = array();

if ($filter_guest != "") {
    $sql .= " AND guests.full_name LIKE ?";
    $types .= "s";
    $params[] = "%" . $filter_guest . "%";
}
if ($filter_date != "" && valid_date($filter_date)) {
    $sql .= " AND bookings.check_in = ?";
    $types .= "s";
    $params[] = $filter_date;
}
$sql .= " ORDER BY bookings.check_in DESC, bookings.booking_id DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($types != "") {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$bookings = mysqli_stmt_get_result($stmt);
$is_filtered = ($types != "");

$page_title = "Bookings";
$page_css = "bookings.css";
include '../includes/header.php';
?>

<?php if ($success != "") { ?>
  <p class="alert success"><?php echo htmlspecialchars($success); ?></p>
<?php } ?>

<?php if ($error != "") { ?>
  <p class="alert error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<div class="bookings-grid">

  <!-- create / edit form -->
  <section class="card">
    <div class="card-head">
      <h2><?php echo $booking_id == 0 ? 'New booking' : 'Edit booking #' . $booking_id; ?></h2>
    </div>

    <form action="bookings.php<?php if ($booking_id != 0) echo '?edit=' . $booking_id; ?>" method="post" class="form">
      <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">

      <?php if ($booking_id == 0) { ?>
        <label for="guest_id">Guest</label>
        <select id="guest_id" name="guest_id" required>
          <option value="">Choose a guest</option>
          <?php while ($g = mysqli_fetch_assoc($all_guests)) { ?>
            <option value="<?php echo $g['guest_id']; ?>" <?php if ($guest_id == $g['guest_id']) echo 'selected'; ?>>
              <?php echo htmlspecialchars($g['full_name'] . ' (' . $g['email'] . ')'); ?>
            </option>
          <?php } ?>
        </select>

        <label for="room_id">Room</label>
        <select id="room_id" name="room_id" required>
          <option value="">Choose a room</option>
          <?php while ($r = mysqli_fetch_assoc($available_rooms)) { ?>
            <option value="<?php echo $r['room_id']; ?>" <?php if ($room_id == $r['room_id']) echo 'selected'; ?>>
              <?php echo htmlspecialchars($r['room_number'] . ' · ' . $r['room_type'] . ' · MVR ' . number_format($r['price_per_night'], 2) . ' / night'); ?>
            </option>
          <?php } ?>
        </select>
      <?php } else { ?>
        <div class="fixed-info">
          <span class="small-label">Guest</span>
          <span class="fixed-value"><?php echo htmlspecialchars($editing['full_name']); ?></span>
          <span class="small-label">Room</span>
          <span class="fixed-value">
            <?php echo htmlspecialchars($editing['room_number'] . ' · ' . $editing['room_type'] . ' · MVR ' . number_format($editing['price_per_night'], 2) . ' / night'); ?>
          </span>
        </div>
      <?php } ?>

      <div class="form-row">
        <div>
          <label for="check_in">Check-in</label>
          <input type="date" id="check_in" name="check_in" value="<?php echo htmlspecialchars($check_in); ?>" <?php if ($booking_id == 0) echo 'min="' . date('Y-m-d') . '"'; ?> required>
        </div>
        <div>
          <label for="check_out">Check-out</label>
          <input type="date" id="check_out" name="check_out" value="<?php echo htmlspecialchars($check_out); ?>" required>
        </div>
      </div>

      <label for="status">Status</label>
      <select id="status" name="status" required>
        <?php foreach ($booking_statuses as $s) { ?>
          <option value="<?php echo $s; ?>" <?php if ($status == $s) echo 'selected'; ?>><?php echo $s; ?></option>
        <?php } ?>
      </select>

      <div class="form-actions">
        <button type="submit" name="save" class="btn-primary"><?php echo $booking_id == 0 ? 'Create booking' : 'Save changes'; ?></button>
        <?php if ($booking_id != 0) { ?>
          <a href="bookings.php" class="btn-secondary">Cancel</a>
        <?php } ?>
      </div>
    </form>
  </section>

  <!-- bookings list -->
  <section class="card">
    <div class="card-head">
      <h2>All bookings</h2>
      <span class="muted"><?php echo mysqli_num_rows($bookings); ?> <?php echo mysqli_num_rows($bookings) == 1 ? 'booking' : 'bookings'; ?></span>
    </div>

    <form action="bookings.php" method="get" class="filter-form">
      <input type="search" name="filter_guest" placeholder="Guest name" value="<?php echo htmlspecialchars($filter_guest); ?>">
      <input type="date" name="filter_date" title="Check-in date" value="<?php echo htmlspecialchars($filter_date); ?>">
      <button type="submit" class="btn-small">Filter</button>
      <?php if ($is_filtered) { ?>
        <a href="bookings.php" class="btn-small clear">Clear</a>
      <?php } ?>
    </form>

    <?php if (mysqli_num_rows($bookings) == 0) { ?>
      <p class="empty"><?php echo $is_filtered ? 'No bookings match these filters.' : 'No bookings yet. Create one using the form.'; ?></p>
    <?php } else { ?>
      <div class="booking-list">
        <?php while ($row = mysqli_fetch_assoc($bookings)) { ?>
          <?php $nights = count_nights($row['check_in'], $row['check_out']); ?>
          <div class="booking-item <?php if ($row['booking_id'] == $booking_id) echo 'editing'; ?>">
            <span class="booking-avatar"><?php echo htmlspecialchars(get_initials($row['full_name'])); ?></span>

            <div class="booking-info">
              <span class="booking-name">
                <?php echo htmlspecialchars($row['full_name']); ?>
                <span class="booking-id">#<?php echo $row['booking_id']; ?></span>
              </span>
              <span class="booking-meta">
                Room <?php echo htmlspecialchars($row['room_number']); ?> ·
                <?php echo htmlspecialchars($row['room_type']); ?> ·
                <?php echo date('d M', strtotime($row['check_in'])); ?> → <?php echo date('d M Y', strtotime($row['check_out'])); ?>
              </span>
            </div>

            <div class="booking-total">
              <span class="booking-price">MVR <?php echo number_format($row['total_price'], 2); ?></span>
              <span class="booking-nights"><?php echo $nights; ?> <?php echo $nights == 1 ? 'night' : 'nights'; ?></span>
            </div>

            <span class="badge <?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span>

            <div class="booking-actions">
              <a href="bookings.php?edit=<?php echo $row['booking_id']; ?>" class="btn-small">Edit</a>
              <form action="bookings.php" method="post" onsubmit="return confirm('Delete booking #<?php echo $row['booking_id']; ?>? This cannot be undone.');">
                <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
                <button type="submit" name="delete" class="btn-small danger">Delete</button>
              </form>
            </div>
          </div>
        <?php } ?>
      </div>
    <?php } ?>
  </section>

</div>

<?php include '../includes/footer.php'; ?>
