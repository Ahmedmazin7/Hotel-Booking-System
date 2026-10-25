<?php
require '../config/db.php';

$error = '';
$success = '';
$full_name = '';
$email = '';
$phone = '';

// Only run when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validate the input
    if ($full_name === '' || $email === '' || $phone === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // 2. Check if the email is already registered
        $stmt = mysqli_prepare($conn, 'SELECT guest_id FROM guests WHERE email = ?');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_fetch_assoc($result)) {
            $error = 'An account with this email already exists.';
        } else {
            // 3. Save the new guest with a hashed password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn, 'INSERT INTO guests (full_name, email, phone, password) VALUES (?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'ssss', $full_name, $email, $phone, $hashed_password);

            if (mysqli_stmt_execute($stmt)) {
                $success = 'Account created! You can now log in.';
                $full_name = $email = $phone = '';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}

$side_title = 'Your next stay starts here';
$side_text = 'Create a free account to book rooms and manage your bookings in one place.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign up · StayEase</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="/HMS-Project/css/guest-auth.css">
</head>
<body>
  <div class="auth">
    <?php include '../includes/guest_auth_side.php'; ?>

    <main class="auth-form">
      <a href="rooms.php" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Browse rooms
      </a>

      <h1>Create account</h1>
      <p class="subtitle">It only takes a minute</p>

      <?php if ($error): ?>
        <p class="message error"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <?php if ($success): ?>
        <p class="message success"><?= htmlspecialchars($success) ?> <a href="login.php">Log in</a></p>
      <?php endif; ?>

      <form action="signup.php" method="post">
        <label for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($full_name) ?>" required>

        <div class="form-row">
          <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" value="<?= htmlspecialchars($email) ?>" required>
          </div>
          <div>
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" placeholder="03001234567" value="<?= htmlspecialchars($phone) ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" minlength="8" required>
          </div>
          <div>
            <label for="confirm_password">Confirm password</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>
          </div>
        </div>
        <p class="field-hint">At least 8 characters.</p>

        <button type="submit">Create account</button>
      </form>

      <p class="switch">Already have an account? <a href="login.php">Log in</a></p>
    </main>
  </div>
</body>
</html>
