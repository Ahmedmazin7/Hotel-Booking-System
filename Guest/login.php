<?php
session_start();
require '../config/db.php';

$error = '';
$success = '';
$email = '';

// sent here from Book a Room
if (isset($_GET['msg']) && $_GET['msg'] === 'login_to_book') {
    $error = 'Please log in to book a room.';
}

// Only run when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 1. Validate the input
    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // 2. Look for the guest in the database

        $stmt = mysqli_prepare($conn, 'SELECT guest_id, full_name, password FROM guests WHERE email = ?');
        
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $guest = mysqli_fetch_assoc($result);

        // 3. Check the guest exists and the password matches

        if ($guest && password_verify($password, $guest['password'])) {
            $_SESSION['guest_id'] = $guest['guest_id'];
            $_SESSION['full_name'] = $guest['full_name'];

            // go back to the room search the guest came from, or to Book a Room
            $next = 'rooms.php';
            if (isset($_SESSION['after_login'])) {
                $next = $_SESSION['after_login'];
                unset($_SESSION['after_login']);
            }
            header('Location: ' . $next);
            exit;
        } else {
            $error = 'Email or password is incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Log in · StayEase</title>
  <link rel="stylesheet" href="/HMS-Project/css/style.css">
</head>
<body>
  <div class="card">
    <h1>Log in</h1>

    <?php if ($error): ?>
      <p class="message error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
      <p class="message success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form action="login.php" method="post">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>

      <button type="submit">Log in</button>
    </form>

    <p class="switch">Don't have an account? <a href="signup.php">Sign up</a></p>
    <p class="switch"><a href="rooms.php">Browse rooms</a></p>
  </div>
</body>
</html>
