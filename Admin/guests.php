<?php
include '../includes/auth.php';
require_admin();

include '../config/db.php';

$error = "";
$success = "";

// form values (empty for a new guest)
$guest_id = 0;
$full_name = "";
$email = "";
$phone = "";

// search text
$search = "";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

// ---------- messages after a redirect ----------
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added') {
        $success = "Guest added successfully.";
    } elseif ($_GET['msg'] == 'updated') {
        $success = "Guest updated successfully.";
    } elseif ($_GET['msg'] == 'deleted') {
        $success = "Guest deleted successfully.";
    }
}

// ---------- DELETE a guest ----------
if (isset($_POST['delete'])) {
    $delete_id = (int) $_POST['guest_id'];

    // a guest with bookings cannot be deleted (foreign key)
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM bookings WHERE guest_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_row(mysqli_stmt_get_result($stmt));

    if ($row[0] > 0) {
        $error = "This guest has bookings, so they can't be deleted. Delete their bookings first.";
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM guests WHERE guest_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        mysqli_stmt_execute($stmt);

        header("Location: guests.php?msg=deleted");
        exit;
    }
}

// ---------- ADD or UPDATE a guest ----------
if (isset($_POST['save'])) {
    $guest_id = (int) $_POST['guest_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    // validate
    if ($full_name == "" || $email == "" || $phone == "") {
        $error = "Name, email and phone are required.";
    } elseif (strlen($full_name) > 100) {
        $error = "Name can be at most 100 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $error = "Please enter a valid email address.";
    } elseif (!preg_match('/^[0-9]{7,15}$/', $phone)) {
        $error = "Phone must be 7 to 15 digits (numbers only).";
    } elseif ($guest_id == 0 && $password == "") {
        $error = "Please enter a password for the new guest.";
    } elseif ($password != "" && strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // email must be unique (ignore the guest we are editing)
        $stmt = mysqli_prepare($conn, "SELECT guest_id FROM guests WHERE email = ? AND guest_id != ?");
        mysqli_stmt_bind_param($stmt, "si", $email, $guest_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $error = "Another guest is already using " . $email . ".";
        }
    }

    // save if there are no errors
    if ($error == "") {
        if ($guest_id == 0) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn, "INSERT INTO guests (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $phone, $hashed_password);
            mysqli_stmt_execute($stmt);

            header("Location: guests.php?msg=added");
            exit;
        } else {
            if ($password == "") {
                // keep the old password
                $stmt = mysqli_prepare($conn, "UPDATE guests SET full_name = ?, email = ?, phone = ? WHERE guest_id = ?");
                mysqli_stmt_bind_param($stmt, "sssi", $full_name, $email, $phone, $guest_id);
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE guests SET full_name = ?, email = ?, phone = ?, password = ? WHERE guest_id = ?");
                mysqli_stmt_bind_param($stmt, "ssssi", $full_name, $email, $phone, $hashed_password, $guest_id);
            }
            mysqli_stmt_execute($stmt);

            header("Location: guests.php?msg=updated");
            exit;
        }
    }
}

// ---------- load a guest into the form for editing ----------
if (isset($_GET['edit']) && !isset($_POST['save'])) {
    $edit_id = (int) $_GET['edit'];

    $stmt = mysqli_prepare($conn, "SELECT guest_id, full_name, email, phone FROM guests WHERE guest_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $guest = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($guest) {
        $guest_id = $guest['guest_id'];
        $full_name = $guest['full_name'];
        $email = $guest['email'];
        $phone = $guest['phone'];
    } else {
        $error = "Guest not found.";
    }
}

// ---------- list guests (with search) ----------
$sql = "SELECT guests.guest_id, guests.full_name, guests.email, guests.phone, guests.created_at,
               (SELECT COUNT(*) FROM bookings WHERE bookings.guest_id = guests.guest_id) AS total_bookings
        FROM guests";

if ($search != "") {
    $sql .= " WHERE guests.full_name LIKE ? OR guests.email LIKE ?";
}
$sql .= " ORDER BY guests.full_name";

$stmt = mysqli_prepare($conn, $sql);
if ($search != "") {
    $like = "%" . $search . "%";
    mysqli_stmt_bind_param($stmt, "ss", $like, $like);
}
mysqli_stmt_execute($stmt);
$guests = mysqli_stmt_get_result($stmt);

$page_title = "Guests";
$page_css = "guests.css";
include '../includes/header.php';
?>

<?php if ($success != "") { ?>
  <p class="alert success"><?php echo htmlspecialchars($success); ?></p>
<?php } ?>

<?php if ($error != "") { ?>
  <p class="alert error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<div class="guests-grid">

  <!-- add / edit form -->
  <section class="card">
    <div class="card-head">
      <h2><?php echo $guest_id == 0 ? 'Add a guest' : 'Edit guest'; ?></h2>
    </div>

    <form action="guests.php<?php if ($guest_id != 0) echo '?edit=' . $guest_id; ?>" method="post" class="form">
      <input type="hidden" name="guest_id" value="<?php echo $guest_id; ?>">

      <label for="full_name">Full name</label>
      <input type="text" id="full_name" name="full_name" maxlength="100" value="<?php echo htmlspecialchars($full_name); ?>" required>

      <label for="email">Email</label>
      <input type="email" id="email" name="email" maxlength="100" value="<?php echo htmlspecialchars($email); ?>" required>

      <label for="phone">Phone</label>
      <input type="tel" id="phone" name="phone" pattern="[0-9]{7,15}" title="7 to 15 digits" value="<?php echo htmlspecialchars($phone); ?>" required>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" minlength="6" <?php if ($guest_id == 0) echo 'required'; ?>>
      <p class="hint">
        <?php echo $guest_id == 0 ? 'At least 6 characters.' : 'Leave blank to keep the current password.'; ?>
      </p>

      <div class="form-actions">
        <button type="submit" name="save" class="btn-primary"><?php echo $guest_id == 0 ? 'Add guest' : 'Save changes'; ?></button>
        <?php if ($guest_id != 0) { ?>
          <a href="guests.php" class="btn-secondary">Cancel</a>
        <?php } ?>
      </div>
    </form>
  </section>

  <!-- guests list -->
  <section class="card">
    <div class="card-head">
      <h2>All guests</h2>
      <span class="muted"><?php echo mysqli_num_rows($guests); ?> <?php echo mysqli_num_rows($guests) == 1 ? 'guest' : 'guests'; ?></span>
    </div>

    <form action="guests.php" method="get" class="search-form">
      <input type="search" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search); ?>">
      <button type="submit" class="btn-small">Search</button>
      <?php if ($search != "") { ?>
        <a href="guests.php" class="btn-small clear">Clear</a>
      <?php } ?>
    </form>

    <?php if (mysqli_num_rows($guests) == 0) { ?>
      <p class="empty">
        <?php echo $search != "" ? 'No guests match "' . htmlspecialchars($search) . '".' : 'No guests yet. Add one using the form.'; ?>
      </p>
    <?php } else { ?>
      <div class="guest-list">
        <?php while ($row = mysqli_fetch_assoc($guests)) { ?>
          <div class="guest-item <?php if ($row['guest_id'] == $guest_id) echo 'editing'; ?>">
            <span class="guest-avatar"><?php echo htmlspecialchars(get_initials($row['full_name'])); ?></span>

            <div class="guest-info">
              <span class="guest-name"><?php echo htmlspecialchars($row['full_name']); ?></span>
              <span class="guest-meta">
                <?php echo htmlspecialchars($row['email']); ?> · <?php echo htmlspecialchars($row['phone']); ?>
              </span>
            </div>

            <div class="guest-stats">
              <span class="guest-bookings"><?php echo $row['total_bookings']; ?> <?php echo $row['total_bookings'] == 1 ? 'booking' : 'bookings'; ?></span>
              <span class="guest-joined">Joined <?php echo date('d M Y', strtotime($row['created_at'])); ?></span>
            </div>

            <div class="guest-actions">
              <a href="guests.php?edit=<?php echo $row['guest_id']; ?>" class="btn-small">Edit</a>
              <form action="guests.php" method="post" onsubmit="return confirm('Delete <?php echo htmlspecialchars(addslashes($row['full_name']), ENT_QUOTES); ?>? This cannot be undone.');">
                <input type="hidden" name="guest_id" value="<?php echo $row['guest_id']; ?>">
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
