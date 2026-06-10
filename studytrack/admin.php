<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Stats
$total_students = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()["c"];
$total_tasks    = $conn->query("SELECT COUNT(*) AS c FROM tasks WHERE status='completed'")->fetch_assoc()["c"];
$total_hours    = $conn->query("SELECT COALESCE(SUM(hours),0) AS h FROM study_hours")->fetch_assoc()["h"];
$total_points   = ($total_tasks * 10) + ($total_hours * 10);

$sql = "
    SELECT
        u.id, u.username,
        COALESCE(SUM(sh.hours), 0) AS total_hours,
        COALESCE(COUNT(CASE WHEN t.status='completed' THEN 1 END), 0) AS completed_tasks,
        (COALESCE(SUM(sh.hours), 0) * 10) +
        (COALESCE(COUNT(CASE WHEN t.status='completed' THEN 1 END), 0) * 10) AS points
    FROM users u
    LEFT JOIN study_hours sh ON sh.user_id = u.id
    LEFT JOIN tasks t        ON t.user_id  = u.id
    GROUP BY u.id, u.username
    ORDER BY points DESC
";
$students = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin – StudyTrack</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<header>
<nav class="navbar">
  <a href="admin.php" class="logo-area">
    <div class="logo-placeholder">S</div>
    <span class="logo-text">StudyTrack <span style="font-size:12px; opacity:0.6;">Admin</span></span>
  </a>
  <ul class="nav-links">
    <li><a href="leaderboard.php">Leaderboard</a></li>
    <li><a href="logout.php" class="btn-logout">Logout</a></li>
  </ul>
</nav>
</header>

<main class="page">

  <p class="page-title">Admin Dashboard</p>
  <p class="page-subtitle">Platform-wide overview</p>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-value"><?= $total_students ?></div>
      <div class="stat-label">Total Students</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= $total_tasks ?></div>
      <div class="stat-label">Tasks Completed</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= $total_hours ?></div>
      <div class="stat-label">Total Study Hours</div>
    </div>
    <div class="stat-card highlight">
      <div class="stat-value"><?= $total_points ?></div>
      <div class="stat-label">Total Points Earned</div>
    </div>
  </div>

  <div class="panel">
    <h3>Students Overview</h3>
    <?php if (empty($students)): ?>
    <div class="empty-state">
      <div class="empty-icon">📭</div>
      <p>No students registered yet.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Username</th>
          <th>Study Hours</th>
          <th>Tasks Done</th>
          <th>Points</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($students as $s): ?>
        <tr>
          <td><?= $s["id"] ?></td>
          <td><?= htmlspecialchars($s["username"]) ?></td>
          <td><?= $s["total_hours"] ?></td>
          <td><?= $s["completed_tasks"] ?></td>
          <td><strong><?= $s["points"] ?></strong></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>

</main>

<footer>
  <p>© 2026 StudyTrack &mdash; Student Productivity Platform</p>
</footer>

</body>
</html>
