<?php
session_start();
include "db.php";

// If already logged in, go straight to dashboard
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = $_POST["email"];
    $password = $_POST["password"];

    // Prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {
            $_SESSION["user_id"]   = $user["id"];
            $_SESSION["username"]  = $user["username"];
            $stmt->close();
            $conn->close();
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Wrong password. Please try again.";
        }
    } else {
        $error = "No account found with that email.";
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
<title>Login - StudyTrack</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">

<div class="auth-container">
<h2>Login</h2>

<?php if (isset($_GET['registered'])): ?>
    <p style="color:green; margin-bottom:10px;">Account created! You can now log in.</p>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <p style="color:red; margin-bottom:10px;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<form method="POST" action="">
    <input type="email"    name="email"    placeholder="Email"    required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" class="btn" style="width:100%; margin-top:10px;">Login</button>
</form>

<p style="margin-top:15px;">Don't have an account? <a href="register.php">Register</a></p>
</div>

</body>
</html>
