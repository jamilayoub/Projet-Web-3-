<?php
session_start();
include "db.php";

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user_id"]  = $user["id"];
        $_SESSION["username"] = $user["username"];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Incorrect email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login – StudyTrack</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">

<div class="auth-card">

  <!-- LOGO AREA: swap the placeholder with your logo image when ready -->
  <div class="auth-logo">
    <div class="auth-logo-placeholder">S</div>
    <strong>StudyTrack</strong>
  </div>

  <h2>Welcome back</h2>
  <p class="subtitle">Log in to continue tracking</p>

  <?php if (isset($_GET['registered'])): ?>
    <div class="msg-success">Account created! You can now log in.</div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="msg-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <input type="email"    name="email"    placeholder="Email address" required>
    <input type="password" name="password" placeholder="Password"      required>
    <button type="submit" class="btn">Login</button>
  </form>

  <p class="auth-footer">No account? <a href="register.php">Register here</a></p>
</div>

</body>
</html>
