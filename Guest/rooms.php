<?php
include '../includes/auth.php';
include '../config/db.php';

// allowed values (same as the ENUM in the database)
$room_types = array('Single', 'Double', 'Deluxe', 'Suite');

$error = "";
$success = "";

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

// check the booking dates, return an error message or "" if they are fine
function check_dates($check_in, $check_out) {
    if (!valid_date($check_in) || !valid_date($check_out)) {
        return "Please enter valid check-in and check-out dates.";
    } elseif ($check_in < date('Y-m-d')) {
        return "Check-in cannot be in the past.";
    } elseif (count_nights($check_in, $check_out) < 1) {
        return "Check-out must be after check-in.";
    } elseif (count_nights($check_in, $check_out) > 365) {
        return "A booking can be at most 365 nights.";
    }
    return "";
}

// search values (all optional)
$check_in = "";
$check_out = "";
$room_type = "";
if (isset($_GET['check_in'])) {
    $check_in = trim($_GET['check_in']);
}
if (isset($_GET['check_out'])) {
    $check_out = trim($_GET['check_out']);
}
if (isset($_GET['room_type'])) {
    $room_type = trim($_GET['room_type']);
}

// ---------- messages after a redirect ----------
if (isset($_GET['msg']) && $_GET['msg'] == 'booked' && isset($_GET['id'])) {
    $success = "Booking #" . (int) $_GET['id'] . " is confirmed. You can see it under My Bookings.";
}

// ---------- BOOK a room ----------
if (isset($_POST['book'])) {
    $room_id = (int) $_POST['room_id'];
    $check_in = trim($_POST['check_in']);
    $check_out = trim($_POST['check_out']);

    // guests must log in first, then come back to the same search
    if (!isset($_SESSION['guest_id'])) {
        $_SESSION['after_login'] = 'rooms.php?' . http_build_query(array('check_in' => $check_in, 'check_out' => $check_out));
        header("Location: login.php?msg=login_to_book");
        exit;
    }

    $guest_id = $_SESSION['guest_id'];

    $stmt = mysqli_prepare($conn, "SELECT room_number, price_per_night, status FROM rooms WHERE room_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $room_id);
    mysqli_stmt_execute($stmt);
    $room = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    // validate
    $error = check_dates($check_in, $check_out);
    if ($error == "") {
        if (!$room) {
            $error = "That room no longer exists.";
        } elseif ($room['status'] == 'Maintenance') {
            $error = "Room " . $room['room_number'] . " is under maintenance and can't be booked.";
        } else {
            // no double booking: another non-cancelled booking for this room must not overlap
            $sql = "SELECT COUNT(*) FROM bookings
                    WHERE room_id = ? AND status != 'Cancelled'
                    AND check_in < ? AND check_out > ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iss", $room_id, $check_out, $check_in);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_row(mysqli_stmt_get_result($stmt));

            if ($row[0] > 0) {
                $error = "Sorry, room " . $room['room_number'] . " was just booked for these dates. Please pick another room.";
            }
        }
    }

    // save if there are no errors
    if ($error == "") {
        $total_price = count_nights($check_in, $check_out) * $room['price_per_night'];
        $status = 'Confirmed';

        $stmt = mysqli_prepare($conn, "INSERT INTO bookings (guest_id, room_id, check_in, check_out, total_price, status) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iissds", $guest_id, $room_id, $check_in, $check_out, $total_price, $status);
        mysqli_stmt_execute($stmt);

        header("Location: rooms.php?msg=booked&id=" . mysqli_insert_id($conn));
        exit;
    }
}

// ---------- search for rooms ----------
// dates only count once both are filled in and valid
$has_dates = false;
if ($check_in != "" || $check_out != "") {
    if ($error == "") {
        $error = check_dates($check_in, $check_out);
    }
    $has_dates = ($error == "");
}

$sql = "SELECT room_id, room_number, room_type, price_per_night, capacity
        FROM rooms
        WHERE status = 'Available'";
$types = "";
$params = array();

if ($has_dates) {
    // hide rooms that have an overlapping non-cancelled booking
    $sql .= " AND room_id NOT IN (
                SELECT room_id FROM bookings
                WHERE status != 'Cancelled' AND check_in < ? AND check_out > ?
              )";
    $types .= "ss";
    $params[] = $check_out;
    $params[] = $check_in;
}
if (in_array($room_type, $room_types)) {
    $sql .= " AND room_type = ?";
    $types .= "s";
    $params[] = $room_type;
} else {
    $room_type = "";
}
$sql .= " ORDER BY price_per_night, room_number";

$stmt = mysqli_prepare($conn, $sql);
if ($types != "") {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$rooms = mysqli_stmt_get_result($stmt);
$room_count = mysqli_num_rows($rooms);

$is_filtered = ($check_in != "" || $check_out != "" || $room_type != "");
$nights = $has_dates ? count_nights($check_in, $check_out) : 0;

$page_title = "Book a Room";
$page_eyebrow = "Find your stay";
$page_css = "guest-rooms.css";
include '../includes/guest_header.php';
?>

  <?php if ($success != "") { ?>
    <p class="alert success"><?php echo htmlspecialchars($success); ?></p>
  <?php } ?>

  <?php if ($error != "") { ?>
    <p class="alert error"><?php echo htmlspecialchars($error); ?></p>
  <?php } ?>

  <!-- search form -->
  <section class="card search-card">
    <form action="rooms.php" method="get" class="form search-form">
      <div>
        <label for="check_in">Check-in</label>
        <input type="date" id="check_in" name="check_in" min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($check_in); ?>">
      </div>
      <div>
        <label for="check_out">Check-out</label>
        <input type="date" id="check_out" name="check_out" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" value="<?php echo htmlspecialchars($check_out); ?>">
      </div>
      <div>
        <label for="room_type">Room type</label>
        <select id="room_type" name="room_type">
          <option value="">Any type</option>
          <?php foreach ($room_types as $t) { ?>
            <option value="<?php echo $t; ?>" <?php if ($room_type == $t) echo 'selected'; ?>><?php echo $t; ?></option>
          <?php } ?>
        </select>
      </div>
      <div class="search-actions">
        <button type="submit" class="btn-primary">Search</button>
        <?php if ($is_filtered) { ?>
          <a href="rooms.php" class="btn-secondary">Clear</a>
        <?php } ?>
      </div>
    </form>
  </section>

  <!-- results -->
  <div class="results-head">
    <h2>
      <?php if ($has_dates) { ?>
        Available <?php echo date('d M', strtotime($check_in)); ?> → <?php echo date('d M Y', strtotime($check_out)); ?>
        <span class="muted">· <?php echo $nights; ?> <?php echo $nights == 1 ? 'night' : 'nights'; ?></span>
      <?php } else { ?>
        All rooms
      <?php } ?>
    </h2>
    <span class="muted"><?php echo $room_count; ?> <?php echo $room_count == 1 ? 'room' : 'rooms'; ?></span>
  </div>

  <?php if (!$has_dates) { ?>
    <p class="hint">Choose your check-in and check-out dates to see which rooms are free and book one.</p>
  <?php } ?>

  <?php if ($room_count == 0) { ?>
    <section class="card">
      <p class="empty"><?php echo $is_filtered ? 'No rooms match your search. Try other dates or filters.' : 'No rooms are open for booking right now.'; ?></p>
    </section>
  <?php } else { ?>
    <div class="room-grid">
      <?php while ($room = mysqli_fetch_assoc($rooms)) { ?>
        <article class="card room-card">
          <div class="room-top">
            <span class="room-type-tag"><?php echo htmlspecialchars($room['room_type']); ?></span>
            <span class="room-number">Room <?php echo htmlspecialchars($room['room_number']); ?></span>
          </div>

          <p class="room-price">
            MVR <?php echo number_format($room['price_per_night'], 2); ?>
            <span class="muted">/ night</span>
          </p>
          <p class="room-capacity muted">
            Sleeps <?php echo $room['capacity']; ?> <?php echo $room['capacity'] == 1 ? 'guest' : 'guests'; ?>
          </p>

          <?php if ($has_dates) { ?>
            <div class="room-total">
              <span class="small-label">Total for <?php echo $nights; ?> <?php echo $nights == 1 ? 'night' : 'nights'; ?></span>
              <span class="room-total-price">MVR <?php echo number_format($nights * $room['price_per_night'], 2); ?></span>
            </div>

            <form action="rooms.php?<?php echo htmlspecialchars(http_build_query(array('check_in' => $check_in, 'check_out' => $check_out, 'room_type' => $room_type))); ?>" method="post"
                  onsubmit="return confirm('Book room <?php echo htmlspecialchars($room['room_number']); ?> for MVR <?php echo number_format($nights * $room['price_per_night'], 2); ?>?');">
              <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
              <input type="hidden" name="check_in" value="<?php echo htmlspecialchars($check_in); ?>">
              <input type="hidden" name="check_out" value="<?php echo htmlspecialchars($check_out); ?>">
              <button type="submit" name="book" class="btn-primary room-btn">
                <?php echo $guest_name != "" ? 'Book now' : 'Log in to book'; ?>
              </button>
            </form>
          <?php } else { ?>
            <a href="#check_in" class="btn-secondary room-btn">Pick dates to book</a>
          <?php } ?>
        </article>
      <?php } ?>
    </div>
  <?php } ?>

<?php include '../includes/guest_footer.php'; ?>
