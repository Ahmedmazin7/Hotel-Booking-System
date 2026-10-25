<?php
include '../includes/auth.php';
require_admin();

include '../config/db.php';

// allowed values (same as the ENUMs in the database)
$room_types = array('Single', 'Double', 'Deluxe', 'Suite');
$room_statuses = array('Available', 'Maintenance');

$error = "";
$success = "";

// form values (empty for a new room)
$room_id = 0;
$room_number = "";
$room_type = "Single";
$price = "";
$capacity = "";
$status = "Available";

// ---------- messages after a redirect ----------
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added') {
        $success = "Room added successfully.";
    } elseif ($_GET['msg'] == 'updated') {
        $success = "Room updated successfully.";
    } elseif ($_GET['msg'] == 'deleted') {
        $success = "Room deleted successfully.";
    }
}

// ---------- DELETE a room ----------
if (isset($_POST['delete'])) {
    $delete_id = (int) $_POST['room_id'];

    // a room with bookings cannot be deleted (foreign key)
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM bookings WHERE room_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_row(mysqli_stmt_get_result($stmt));

    if ($row[0] > 0) {
        $error = "This room has bookings, so it can't be deleted. Delete its bookings first or set it to Maintenance.";
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM rooms WHERE room_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        mysqli_stmt_execute($stmt);

        header("Location: rooms.php?msg=deleted");
        exit;
    }
}

// ---------- ADD or UPDATE a room ----------
if (isset($_POST['save'])) {
    $room_id = (int) $_POST['room_id'];
    $room_number = trim($_POST['room_number']);
    $room_type = $_POST['room_type'];
    $price = trim($_POST['price']);
    $capacity = trim($_POST['capacity']);
    $status = $_POST['status'];

    // validate
    if ($room_number == "" || $price == "" || $capacity == "") {
        $error = "All fields are required.";
    } elseif (strlen($room_number) > 10) {
        $error = "Room number can be at most 10 characters.";
    } elseif (!in_array($room_type, $room_types)) {
        $error = "Please choose a valid room type.";
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = "Price must be a number above 0.";
    } elseif ($price > 99999) {
        $error = "Price is too high.";
    } elseif (filter_var($capacity, FILTER_VALIDATE_INT) === false || $capacity < 1 || $capacity > 6) {
        $error = "Capacity must be a whole number from 1 to 6.";
    } elseif (!in_array($status, $room_statuses)) {
        $error = "Please choose a valid status.";
    } else {
        // room number must be unique (ignore the room we are editing)
        $stmt = mysqli_prepare($conn, "SELECT room_id FROM rooms WHERE room_number = ? AND room_id != ?");
        mysqli_stmt_bind_param($stmt, "si", $room_number, $room_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $error = "Room number " . $room_number . " already exists.";
        }
    }

    // save if there are no errors
    if ($error == "") {
        if ($room_id == 0) {
            $stmt = mysqli_prepare($conn, "INSERT INTO rooms (room_number, room_type, price_per_night, capacity, status) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssdis", $room_number, $room_type, $price, $capacity, $status);
            mysqli_stmt_execute($stmt);

            header("Location: rooms.php?msg=added");
            exit;
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE rooms SET room_number = ?, room_type = ?, price_per_night = ?, capacity = ?, status = ? WHERE room_id = ?");
            mysqli_stmt_bind_param($stmt, "ssdisi", $room_number, $room_type, $price, $capacity, $status, $room_id);
            mysqli_stmt_execute($stmt);

            header("Location: rooms.php?msg=updated");
            exit;
        }
    }
}

// ---------- load a room into the form for editing ----------
if (isset($_GET['edit']) && !isset($_POST['save'])) {
    $edit_id = (int) $_GET['edit'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM rooms WHERE room_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $room = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($room) {
        $room_id = $room['room_id'];
        $room_number = $room['room_number'];
        $room_type = $room['room_type'];
        $price = $room['price_per_night'];
        $capacity = $room['capacity'];
        $status = $room['status'];
    } else {
        $error = "Room not found.";
    }
}

// ---------- list all rooms ----------
$sql = "SELECT rooms.*,
               (SELECT COUNT(*) FROM bookings WHERE bookings.room_id = rooms.room_id) AS total_bookings
        FROM rooms
        ORDER BY rooms.room_number";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmt);
$rooms = mysqli_stmt_get_result($stmt);

$page_title = "Rooms";
$page_css = "rooms.css";
include '../includes/header.php';
?>

<?php if ($success != "") { ?>
  <p class="alert success"><?php echo htmlspecialchars($success); ?></p>
<?php } ?>

<?php if ($error != "") { ?>
  <p class="alert error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<div class="rooms-grid">

  <!-- add / edit form -->
  <section class="card">
    <div class="card-head">
      <h2><?php echo $room_id == 0 ? 'Add a room' : 'Edit room ' . htmlspecialchars($room_number); ?></h2>
    </div>

    <form action="rooms.php<?php if ($room_id != 0) echo '?edit=' . $room_id; ?>" method="post" class="form">
      <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">

      <label for="room_number">Room number</label>
      <input type="text" id="room_number" name="room_number" maxlength="10" value="<?php echo htmlspecialchars($room_number); ?>" required>

      <label for="room_type">Room type</label>
      <select id="room_type" name="room_type" required>
        <?php foreach ($room_types as $type) { ?>
          <option value="<?php echo $type; ?>" <?php if ($room_type == $type) echo 'selected'; ?>><?php echo $type; ?></option>
        <?php } ?>
      </select>

      <div class="form-row">
        <div>
          <label for="price">Price / night (MVR)</label>
          <input type="number" id="price" name="price" min="0.01" max="99999" step="0.01" value="<?php echo htmlspecialchars($price); ?>" required>
        </div>
        <div>
          <label for="capacity">Capacity</label>
          <input type="number" id="capacity" name="capacity" min="1" max="6" step="1" value="<?php echo htmlspecialchars($capacity); ?>" required>
        </div>
      </div>

      <label for="status">Status</label>
      <select id="status" name="status" required>
        <?php foreach ($room_statuses as $s) { ?>
          <option value="<?php echo $s; ?>" <?php if ($status == $s) echo 'selected'; ?>><?php echo $s; ?></option>
        <?php } ?>
      </select>

      <div class="form-actions">
        <button type="submit" name="save" class="btn-primary"><?php echo $room_id == 0 ? 'Add room' : 'Save changes'; ?></button>
        <?php if ($room_id != 0) { ?>
          <a href="rooms.php" class="btn-secondary">Cancel</a>
        <?php } ?>
      </div>
    </form>
  </section>

  <!-- rooms list -->
  <section class="card">
    <div class="card-head">
      <h2>All rooms</h2>
      <span class="muted"><?php echo mysqli_num_rows($rooms); ?> rooms</span>
    </div>

    <?php if (mysqli_num_rows($rooms) == 0) { ?>
      <p class="empty">No rooms yet. Add your first room using the form.</p>
    <?php } else { ?>
      <div class="room-list">
        <?php while ($row = mysqli_fetch_assoc($rooms)) { ?>
          <div class="room-item <?php if ($row['room_id'] == $room_id) echo 'editing'; ?>">
            <span class="room-badge <?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['room_number']); ?></span>

            <div class="room-info">
              <span class="room-name">
                <?php echo htmlspecialchars($row['room_type']); ?> ·
                <?php echo $row['capacity']; ?> <?php echo $row['capacity'] == 1 ? 'guest' : 'guests'; ?>
              </span>
              <span class="room-meta">
                MVR <?php echo number_format($row['price_per_night'], 2); ?> / night ·
                <?php echo $row['total_bookings']; ?> <?php echo $row['total_bookings'] == 1 ? 'booking' : 'bookings'; ?>
              </span>
            </div>

            <span class="badge <?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span>

            <div class="room-actions">
              <a href="rooms.php?edit=<?php echo $row['room_id']; ?>" class="btn-small">Edit</a>
              <form action="rooms.php" method="post" onsubmit="return confirm('Delete room <?php echo htmlspecialchars($row['room_number'], ENT_QUOTES); ?>? This cannot be undone.');">
                <input type="hidden" name="room_id" value="<?php echo $row['room_id']; ?>">
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
