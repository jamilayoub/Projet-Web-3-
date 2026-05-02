<?php
session_start();
include "db.php";

// Basic admin protection - only allow if logged in
// (You can add a role column to users table later for stricter access)
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Total students
$total_students = $conn->query("SELECT COUNT(*) AS cnt FROM users")->fetch_assoc()["cnt"];

// Total tasks (completed)
$total_tasks = $conn->query("SELECT COUNT(*) AS cnt FROM tasks WHERE status='completed'")->fetch_assoc()["cnt"];

// Total points across all users
$total_hours_row = $conn->query("SELECT COALESCE(SUM(hours),0) AS h FROM study_hours")->fetch_assoc();
$total_hours     = $total_hours_row["h"];
$total_points    = ($total_tasks * 10) + ($total_hours * 10);

// Per-student overview
$sql = "
    SELECT
        u.id,
        u.username,
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
<title>Admin Dashboard - StudyTrack</title>
<link rel="stylesheet" href="admin.css">
</head>

<body>

<header>
<nav class="navbar">
<h1 class="logo">StudyTrack Admin</h1>
<ul class="links">
<li><a href="leaderboard.php" class="btn">Leaderboard</a></li>
<li><a href="logout.php"      class="btn2">Logout</a></li>
</ul>
</nav>
</header>

<section class="admin-section">

<h2>Admin Dashboard</h2>

<div class="stats">

<div class="card">
<h3>Total Students</h3>
<p><?php echo $total_students; ?></p>
</div>

<div class="card">
<h3>Tasks Completed</h3>
<p><?php echo $total_tasks; ?></p>
</div>

<div class="card">
<h3>Total Points</h3>
<p><?php echo $total_points; ?></p>
</div>

</div>

<h3 class="table-title">Students Overview</h3>

<div class="table-container">
<table class="admin-table">
<thead>
<tr>
<th>ID</th>
<th>Name</th>
<th>Study Hours</th>
<th>Tasks Completed</th>
<th>Points</th>
</tr>
</thead>
<tbody>
<?php if (empty($students)): ?>
<tr><td colspan="5">No students yet.</td></tr>
<?php else: ?>
<?php foreach ($students as $s): ?>
<tr>
    <td><?php echo $s["id"]; ?></td>
    <td><?php echo htmlspecialchars($s["username"]); ?></td>
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
