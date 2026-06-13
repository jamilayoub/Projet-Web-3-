<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION["user_id"];

$sql = "
   SELECT
    u.id,
    u.username,
    COALESCE(sh.total_hours, 0) AS total_hours,
    COALESCE(t.completed_tasks, 0) AS completed_tasks,
    (COALESCE(sh.total_hours, 0) * 10) +
    (COALESCE(t.completed_tasks, 0) * 10) AS points
FROM users u
LEFT JOIN (
    SELECT user_id, SUM(hours) AS total_hours
    FROM study_hours
    GROUP BY user_id
) sh ON sh.user_id = u.id
LEFT JOIN (
    SELECT user_id, COUNT(*) AS completed_tasks
    FROM tasks
    WHERE status = 'completed'
    GROUP BY user_id
) t ON t.user_id = u.id
ORDER BY points DESC
";

$students = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$conn->close();

$medals = ["🥇", "🥈", "🥉"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leaderboard – StudyTrack</title>
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
    <li><a href="dashboard.php">Dashboard</a></li>
    <li><a href="tasks.php">Tasks</a></li>
    <li><a href="leaderboard.php" class="active">Leaderboard</a></li>
    <li><a href="logout.php" class="btn-logout">Logout</a></li>
  </ul>
</nav>
</header>

<main class="page">

  <p class="page-title">🏆 Leaderboard</p>
  <p class="page-subtitle">Students ranked by total productivity points</p>

  <?php if (empty($students)): ?>
  <div class="empty-state">
    <div class="empty-icon">📭</div>
    <p>No data yet. Be the first to earn some points!</p>
  </div>
  <?php else: ?>

  <div style="overflow-x:auto;">
  <table class="data-table">
    <thead>
      <tr>
        <th>Rank</th>
        <th>Student</th>
        <th>Study Hours</th>
        <th>Tasks Done</th>
        <th>Points</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($students as $i => $s): ?>
      <tr <?= $s["id"] == $current_user ? 'class="you-row"' : '' ?>>
        <td class="<?= $i < 3 ? "rank-".($i+1) : "" ?>">
          <?= isset($medals[$i]) ? $medals[$i] : ($i + 1) ?>
        </td>
        <td>
          <?= htmlspecialchars($s["username"]) ?>
          <?php if ($s["id"] == $current_user): ?>
            <span style="font-size:12px; background:var(--mid); color:white; padding:2px 8px; border-radius:12px; margin-left:6px;">You</span>
          <?php endif; ?>
        </td>
        <td><?= $s["total_hours"] ?></td>
        <td><?= $s["completed_tasks"] ?></td>
        <td><strong><?= $s["points"] ?></strong></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <?php endif; ?>

</main>

<footer>
  <p>© 2026 StudyTrack &mdash; Student Productivity Platform</p>
</footer>

</body>
</html>
