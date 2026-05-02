<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Prepared statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: login.php?registered=1");
        exit();
    } else {
        $error = "Registration failed: " . $conn->error;
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
<title>Register - StudyTrack</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">

<div class="auth-container">
<h2>Create Account</h2>

<?php if (!empty($error)): ?>
    <p style="color:red; margin-bottom:10px;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<form action="register.php" method="POST">
    <input type="text"     name="username" placeholder="Username" required>
    <input type="email"    name="email"    placeholder="Email"    required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" class="btn" style="width:100%; margin-top:10px;">Register</button>
</form>

<p style="margin-top:15px;">Already have an account? <a href="login.php">Login</a></p>
</div>

</body>
</html>
