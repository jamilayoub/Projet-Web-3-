<?php

session_start();

include "db.php";



if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");

    exit();

}



$user_id  = $_SESSION["user_id"];

$username = $_SESSION["username"];



// Handle weekly goal save

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "save_goal") {

    $goal = (int)$_POST["weekly_goal"];

    if ($goal > 0 && $goal <= 168) {

        $stmt = $conn->prepare("UPDATE users SET weekly_goal=? WHERE id=?");

        $stmt->bind_param("ii", $goal, $user_id);

        $stmt->execute();

        $stmt->close();

        $_SESSION["toast"] = ["msg" => "Weekly goal updated to {$goal} hours!", "type" => "success"];

    }

    header("Location: dashboard.php");

    exit();

}



// Flash toast

$toast = "";

$toast_type = "info";

if (isset($_SESSION["toast"])) {

    $toast      = $_SESSION["toast"]["msg"];

    $toast_type = $_SESSION["toast"]["type"];

    unset($_SESSION["toast"]);

}



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



// Points progress (goal = 100 points)

$goal     = 100;

$progress = min(($points / $goal) * 100, 100);



// Weekly study goal

$stmt = $conn->prepare("SELECT weekly_goal FROM users WHERE id=?");

$stmt->bind_param("i", $user_id);

$stmt->execute();

$weekly_goal = $stmt->get_result()->fetch_assoc()["weekly_goal"] ?? 10;

$stmt->close();



// Weekly hours (this week)

$stmt = $conn->prepare("SELECT COALESCE(SUM(hours),0) AS h FROM study_hours WHERE user_id=? AND YEARWEEK(date)=YEARWEEK(NOW())");

$stmt->bind_param("i", $user_id);

$stmt->execute();

$weekly_hours = $stmt->get_result()->fetch_assoc()["h"];

$stmt->close();



$weekly_progress = ($weekly_goal > 0) ? min(($weekly_hours / $weekly_goal) * 100, 100) : 0;



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

    <li><a href="dashboard.php" class="active">Dashboard</a></li>

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



  <!-- Points Progress Bar -->

  <div class="panel">

    <h3>🏅 Points Progress</h3>

    <p style="font-size:14px; color:#666; margin-bottom:12px;">

      <?= $points ?> / <?= $goal ?> points toward your next goal

    </p>

    <div class="progress-bar">

      <div class="progress-fill" style="width:<?= $progress ?>%"></div>

    </div>

    <p style="font-size:13px; color:var(--mid); margin-top:8px; font-weight:600;"><?= round($progress) ?>%</p>

  </div>



  <!-- Weekly Study Goal -->

  <div class="panel">

    <h3>📅 Weekly Study Goal</h3>

    <p style="font-size:14px; color:#666; margin-bottom:12px;">

      <?= round($weekly_hours, 1) ?> / <?= $weekly_goal ?> hours this week

    </p>

    <div class="progress-bar">

      <div class="progress-fill" style="width:<?= $weekly_progress ?>%"></div>

    </div>

    <p style="font-size:13px; color:var(--mid); margin-top:8px; font-weight:600;"><?= round($weekly_progress) ?>%</p>

    <form method="POST" action="dashboard.php" style="margin-top:16px;">

      <input type="hidden" name="action" value="save_goal">

      <div class="form-row" style="align-items:flex-end; gap:10px;">

        <div class="form-group" style="max-width:180px;">

          <label for="weekly_goal">Weekly goal (hours)</label>

          <input type="number" id="weekly_goal" name="weekly_goal" value="<?= $weekly_goal ?>" min="1" max="168" step="1">

        </div>

        <div class="form-group" style="flex:0;">

          <label>&nbsp;</label>

          <button type="submit" class="btn btn-sm">Save</button>

        </div>

      </div>

    </form>

  </div>



  <div style="text-align:center; margin-top:16px; display:flex; gap:14px; justify-content:center; flex-wrap:wrap;">

    <a href="tasks.php" class="btn">Manage Tasks</a>

    <a href="leaderboard.php" class="btn-outline">View Leaderboard</a>

  </div>



</main>



<footer>

  <p>© 2026 StudyTrack &mdash; Student Productivity Platform</p>

</footer>



<!-- Toast Notification -->

<div id="toast" class="toast <?php if($toast) echo "toast-".$toast_type; ?>">

  <?= htmlspecialchars($toast) ?>

</div>

<script>

<?php if ($toast): ?>

(function() {

  const t = document.getElementById("toast");

  t.classList.add("show");

  setTimeout(() => t.classList.remove("show"), 3500);

})();

<?php endif; ?>

</script>



</body>

</html>
