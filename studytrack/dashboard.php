<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id  = $_SESSION["user_id"];
$username = $_SESSION["username"];

// Completed tasks count
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tasks WHERE user_id = ? AND status = 'completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed_tasks = $stmt->get_result()->fetch_assoc()["cnt"];
$stmt->close();

// Total study hours
$stmt = $conn->prepare("SELECT COALESCE(SUM(hours), 0) AS total FROM study_hours WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$hours = $stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

// Pending tasks count
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tasks WHERE user_id = ? AND status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_tasks = $stmt->get_result()->fetch_assoc()["cnt"];
$stmt->close();

// Study streak: count consecutive days with logged hours up to today
$streak = 0;
$check_date = date("Y-m-d");
while (true) {
    $stmt = $conn->prepare("SELECT 1 FROM study_hours WHERE user_id = ? AND date = ? LIMIT 1");
    $stmt->bind_param("is", $user_id, $check_date);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) break;
    $streak++;
    $check_date = date("Y-m-d", strtotime($check_date . " -1 day"));
    $stmt->close();
}

$points = ($completed_tasks * 10) + ($hours * 10);

$conn->close();

// Summary message
if ($hours == 0 && $completed_tasks == 0) {
    $summary = "No activity yet — add your first task or log some study hours to get started!";
    $summary_emoji = "👋";
} elseif ($completed_tasks == 0) {
    $summary = "You've logged {$hours} study hour(s). Complete some tasks to boost your points!";
    $summary_emoji = "📖";
} elseif ($hours == 0) {
    $summary = "You've completed {$completed_tasks} task(s). Try logging some study hours too!";
    $summary_emoji = "✅";
} else {
    $summary = "You studied {$hours} hour(s) and completed {$completed_tasks} task(s) — earning {$points} points total!";
    $summary_emoji = "🎯";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – StudyTrack</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<header>
<nav class="navbar">
  <a href="dashboard.php" class="logo-area">
    <div class="logo-placeholder">S</div>
    <span class="logo-text">StudyTrack</span>
  </a>
  <ul class="nav-links">
    <li><a href="dashboard.php"  class="active">Dashboard</a></li>
    <li><a href="tasks.php">Tasks</a></li>
    <li><a href="leaderboard.php">Leaderboard</a></li>
    <li><a href="logout.php" class="btn-logout">Logout</a></li>
  </ul>
</nav>
</header>

<main class="page">

  <p class="page-title">Welcome back, <?= htmlspecialchars($username) ?>! 👋</p>
  <p class="page-subtitle">Here's how your studying is going</p>

  <?php if ($streak > 0): ?>
  <div class="streak-badge">🔥 <?= $streak ?>-day study streak — keep it up!</div>
  <?php endif; ?>

  <div class="stats-grid">
    <div class="stat-card highlight">
      <div class="stat-value"><?= $points ?></div>
      <div class="stat-label">Total Points</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= $hours ?></div>
      <div class="stat-label">Study Hours</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= $completed_tasks ?></div>
      <div class="stat-label">Tasks Done</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= $pending_tasks ?></div>
      <div class="stat-label">Tasks Pending</div>
    </div>
  </div>

  <div class="summary-bar">
    <span class="emoji"><?= $summary_emoji ?></span>
    <span><?= $summary ?></span>
  </div>

  <div style="text-align:center; margin-top:16px; display:flex; gap:14px; justify-content:center; flex-wrap:wrap;">
    <a href="tasks.php" class="btn">Manage Tasks</a>
    <a href="leaderboard.php" class="btn-outline">View Leaderboard</a>
  </div>

</main>

<footer>
  <p>© 2026 StudyTrack &mdash; Student Productivity Platform</p>
</footer>

</body>
</html>
