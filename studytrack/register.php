<?php
session_start();
include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email    = trim($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: login.php?registered=1");
        exit();
    } else {
        $error = "Registration failed. That email may already be in use.";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register – StudyTrack</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">

<div class="auth-card">

  <!-- LOGO AREA: swap the placeholder with your logo image when ready -->
  <div class="auth-logo">
    <div class="auth-logo-placeholder">S</div>
    <strong>StudyTrack</strong>
  </div>

  <h2>Create account</h2>
  <p class="subtitle">Start tracking your progress today</p>

  <?php if ($error): ?>
    <div class="msg-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="register.php">
    <input type="text"     name="username" placeholder="Username"       required>
    <input type="email"    name="email"    placeholder="Email address"  required>
    <input type="password" name="password" placeholder="Password"       required>
    <button type="submit" class="btn">Create Account</button>
  </form>

  <p class="auth-footer">Already have an account? <a href="login.php">Login</a></p>
</div>

</body>
</html>
