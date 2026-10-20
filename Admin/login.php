<?php
session_start();

// already logged in? go to the dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

// connect to the database
include '../config/db.php';

$error = "";
$success = "";

// check if login button is clicked
if (isset($_POST['login'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];

    // check empty fields
    if (empty($username) || empty($password)) {
        $error = "All fields are required";
    } else {

        // find the admin by username
        $sql = "SELECT * FROM admins WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);

            // check password
            if (password_verify($password, $row['password'])) {
                $_SESSION['admin_id'] = $row['admin_id'];
                $_SESSION['admin_username'] = $row['username'];
                $_SESSION['admin_name'] = $row['full_name'];
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Wrong username or password";
            }
        } else {
            $error = "Wrong username or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Log in · StayEase</title>
  <link rel="stylesheet" href="/HMS-Project/css/style.css">
</head>
<body>
  <div class="card">
    <h1>Admin Log in</h1>

    <?php
    if ($error != "") {
        echo "<p class='message error'>" . $error . "</p>";
    }
    if ($success != "") {
        echo "<p class='message success'>" . htmlspecialchars($success) . "</p>";
    }
    ?>

    <form action="login.php" method="post">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>

      <button type="submit" name="login">Log in</button>
    </form>

  </div>
</body>
</html>
