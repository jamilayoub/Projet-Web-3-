<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$toast   = "";
$toast_type = "info";

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {

    if ($_POST["action"] == "add_task") {
        $title    = trim($_POST["title"]);
        $priority = in_array($_POST["priority"], ["high","medium","low"]) ? $_POST["priority"] : "medium";
        if (!empty($title)) {
            $stmt = $conn->prepare("INSERT INTO tasks (user_id, title, status, priority) VALUES (?, ?, 'pending', ?)");
            $stmt->bind_param("iss", $user_id, $title, $priority);
            $stmt->execute();
            $stmt->close();
            $_SESSION["toast"] = ["msg" => "Task added!", "type" => "success"];
        }
    }

    if ($_POST["action"] == "complete_task") {
        $task_id = (int)$_POST["task_id"];
        $stmt = $conn->prepare("UPDATE tasks SET status='completed' WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION["toast"] = ["msg" => "Task marked as done! +10 pts 🎉", "type" => "success"];
    }

    if ($_POST["action"] == "delete_task") {
        $task_id = (int)$_POST["task_id"];
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION["toast"] = ["msg" => "Task deleted.", "type" => "info"];
    }

    if ($_POST["action"] == "log_hours") {
        $hours = (float)$_POST["hours"];
        $date  = date("Y-m-d");
        if ($hours > 0 && $hours <= 24) {
            $stmt = $conn->prepare("INSERT INTO study_hours (user_id, hours, date) VALUES (?, ?, ?)");
            $stmt->bind_param("ids", $user_id, $hours, $date);
            $stmt->execute();
            $stmt->close();
            $_SESSION["toast"] = ["msg" => "{$hours} hour(s) logged! +".($hours*10)." pts", "type" => "success"];
        }
    }

    header("Location: tasks.php");
    exit();
}

// Flash toast from session
if (isset($_SESSION["toast"])) {
    $toast      = $_SESSION["toast"]["msg"];
    $toast_type = $_SESSION["toast"]["type"];
    unset($_SESSION["toast"]);
}

// Load tasks — pending first, then completed
$stmt = $conn->prepare("SELECT id, title, status, priority, created_at FROM tasks WHERE user_id=? ORDER BY FIELD(status,'pending','completed'), FIELD(priority,'high','medium','low'), created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Today's hours
$today = date("Y-m-d");
$stmt  = $conn->prepare("SELECT COALESCE(SUM(hours),0) AS h FROM study_hours WHERE user_id=? AND date=?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_hours = $stmt->get_result()->fetch_assoc()["h"];
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tasks – StudyTrack</title>
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
    <li><a href="tasks.php" class="active">Tasks</a></li>
    <li><a href="leaderboard.php">Leaderboard</a></li>
    <li><a href="logout.php" class="btn-logout">Logout</a></li>
  </ul>
</nav>
</header>

<main class="page">

  <p class="page-title">Task Manager</p>
  <p class="page-subtitle">Stay on top of your work — every completed task earns 10 points</p>

  <!-- Add Task -->
  <div class="panel">
    <h3>➕ Add New Task</h3>
    <form method="POST" action="tasks.php">
      <input type="hidden" name="action" value="add_task">
      <div class="form-row">
        <div class="form-group" style="flex:3;">
          <label for="title">Task name</label>
          <input type="text" id="title" name="title" placeholder="e.g. Study Chapter 5, Finish PHP project…" required>
        </div>
        <div class="form-group" style="flex:1;">
          <label for="priority">Priority</label>
          <select id="priority" name="priority">
            <option value="high">🔴 High</option>
            <option value="medium" selected>🟡 Medium</option>
            <option value="low">🟢 Low</option>
          </select>
        </div>
        <div class="form-group" style="flex:0;">
          <label>&nbsp;</label>
          <button type="submit" class="btn">Add Task</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Log Study Hours -->
  <div class="panel">
    <h3>⏱️ Log Study Hours</h3>
    <p style="font-size:14px; color:#666; margin-bottom:14px;">
      Today so far: <strong style="color:var(--mid);"><?= $today_hours ?> hour(s)</strong>
      &nbsp;|&nbsp; Each hour earns 10 points
    </p>
    <form method="POST" action="tasks.php">
      <input type="hidden" name="action" value="log_hours">
      <div class="form-row">
        <div class="form-group">
          <label for="hours">Hours studied</label>
          <input type="number" id="hours" name="hours" placeholder="e.g. 1.5" step="0.5" min="0.5" max="24" required style="max-width:200px;">
        </div>
        <div class="form-group" style="flex:0; justify-content:flex-end;">
          <label>&nbsp;</label>
          <button type="submit" class="btn">Log Hours</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Task List -->
  <div class="panel">
    <h3>📋 Your Tasks (<?= count($tasks) ?>)</h3>

    <?php if (empty($tasks)): ?>
    <div class="empty-state">
      <div class="empty-icon">📭</div>
      <p>No tasks yet — add one above to get started!</p>
    </div>
    <?php else: ?>
    <ul class="task-list">
    <?php foreach ($tasks as $task): ?>
      <li class="task-item <?= $task["status"] == "completed" ? "completed" : "" ?>">

        <span class="priority-badge priority-<?= $task["priority"] ?>">
          <?= $task["priority"] ?>
        </span>

        <span class="task-title">
          <?php if ($task["status"] == "completed"): ?>
            <s><?= htmlspecialchars($task["title"]) ?></s> <span style="color:var(--success); margin-left:4px;">✓</span>
          <?php else: ?>
            <?= htmlspecialchars($task["title"]) ?>
          <?php endif; ?>
        </span>

        <div class="task-actions">
          <?php if ($task["status"] == "pending"): ?>
          <form method="POST" action="tasks.php" style="margin:0;">
            <input type="hidden" name="action"  value="complete_task">
            <input type="hidden" name="task_id" value="<?= $task["id"] ?>">
            <button type="submit" class="btn btn-sm btn-success">Done ✓</button>
          </form>
          <?php endif; ?>

          <form method="POST" action="tasks.php" style="margin:0;" onsubmit="return confirm('Delete this task?');">
            <input type="hidden" name="action"  value="delete_task">
            <input type="hidden" name="task_id" value="<?= $task["id"] ?>">
            <button type="submit" class="btn-danger">✕</button>
          </form>
        </div>

      </li>
    <?php endforeach; ?>
    </ul>
    <?php endif; ?>
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
