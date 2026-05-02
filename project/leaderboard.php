<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Fetch leaderboard: all users with their stats, ordered by points
$sql = "
    SELECT
        u.id,
        u.username,
        COALESCE(SUM(sh.hours), 0)                        AS total_hours,
        COALESCE(COUNT(CASE WHEN t.status='completed' THEN 1 END), 0) AS completed_tasks,
        (COALESCE(SUM(sh.hours), 0) * 10) +
        (COALESCE(COUNT(CASE WHEN t.status='completed' THEN 1 END), 0) * 10) AS points
    FROM users u
    LEFT JOIN study_hours sh ON sh.user_id = u.id
    LEFT JOIN tasks t        ON t.user_id  = u.id
    GROUP BY u.id, u.username
    ORDER BY points DESC
";

$result = $conn->query($sql);
$students = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leaderboard - StudyTrack</title>
<link rel="stylesheet" href="leaderboard.css">
</head>

<body>

<header>
<nav class="navbar">
<h1 class="logo">StudyTrack</h1>
<ul class="links">
<li><a href="dashboard.php" class="btn">Dashboard</a></li>
<li><a href="tasks.php"     class="btn">Tasks</a></li>
<li><a href="logout.php"    class="btn2">Logout</a></li>
</ul>
</nav>
</header>

<section class="leaderboard-section">

<h2>🏆 Leaderboard</h2>
<p>Students ranked by total productivity points</p>

<div class="leaderboard-container">

<table class="leaderboard-table">
<thead>
<tr>
<th>Rank</th>
<th>Student</th>
<th>Study Hours</th>
<th>Tasks Completed</th>
<th>Points</th>
</tr>
</thead>

<tbody>
<?php if (empty($students)): ?>
<tr><td colspan="5">No data yet.</td></tr>
<?php else: ?>
<?php foreach ($students as $i => $s): ?>
<tr <?php if ($s["id"] == $_SESSION["user_id"]) echo 'style="background:#d4edda;"'; ?>>
    <td><?php echo $i + 1; ?></td>
    <td><?php echo htmlspecialchars($s["username"]); ?><?php if ($s["id"] == $_SESSION["user_id"]) echo " (You)"; ?></td>
    <td><?php echo $s["total_hours"]; ?></td>
    <td><?php echo $s["completed_tasks"]; ?></td>
    <td><?php echo $s["points"]; ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>

</table>

</div>

</section>

<footer>
<p>© 2026 Student Productivity Platform</p>
</footer>

</body>
</html>
