<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id  = $_SESSION["user_id"];
$username = $_SESSION["username"];

// Get total points: each completed task = 10 pts, each study hour = 10 pts
$points = 0;
$hours  = 0;
$completed_tasks = 0;

// Count completed tasks
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tasks WHERE user_id = ? AND status = 'completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$completed_tasks = $row["cnt"];
$stmt->close();

// Sum study hours
$stmt = $conn->prepare("SELECT COALESCE(SUM(hours), 0) AS total FROM study_hours WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$row   = $stmt->get_result()->fetch_assoc();
$hours = $row["total"];
$stmt->close();

$points = ($completed_tasks * 10) + ($hours * 10);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - StudyTrack</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="dashboard_style.css">
</head>
<body>

<header>
<nav class="navbar">
<h1 class="logo">StudyTrack</h1>
<ul class="links">
<li><a href="dashboard.php">Dashboard</a></li>
<li><a href="tasks.php">Tasks</a></li>
<li><a href="leaderboard.php">Leaderboard</a></li>
<li><a href="logout.php" class="btn2">Logout</a></li>
</ul>
</nav>
</header>

<section class="about">
<h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>

<div class="features-grid" style="margin-top:30px;">

<div class="feature-card">
<h3>Total Points</h3>
<p id="points" style="font-size:32px; font-weight:bold; color:#547792;">
    <?php echo $points; ?>
</p>
</div>

<div class="feature-card">
<h3>Total Study Hours</h3>
<p id="hours" style="font-size:32px; font-weight:bold; color:#547792;">
    <?php echo $hours; ?>
</p>
</div>

<div class="feature-card">
<h3>Completed Tasks</h3>
<p id="tasksCompleted" style="font-size:32px; font-weight:bold; color:#547792;">
    <?php echo $completed_tasks; ?>
</p>
</div>

</div>

<div style="margin-top:30px; text-align:center;">
<h3>Quick Summary</h3>
<p id="summary" style="margin-top:10px;">
<?php
if ($hours == 0 && $completed_tasks == 0) {
    echo "No study activity yet. Add some tasks or log study hours to get started!";
} else {
    echo "You studied " . $hours . " hour(s) and completed " . $completed_tasks . " task(s), earning " . $points . " points.";
}
?>
</p>
</div>

</section>

<footer>
<p>© 2026 Student Productivity Platform</p>
</footer>

</body>
</html>
