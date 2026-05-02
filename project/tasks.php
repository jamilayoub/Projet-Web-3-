<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$message = "";

// Handle adding a new task
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {

    if ($_POST["action"] == "add_task") {
        $title = trim($_POST["title"]);
        if (!empty($title)) {
            $stmt = $conn->prepare("INSERT INTO tasks (user_id, title, status) VALUES (?, ?, 'pending')");
            $stmt->bind_param("is", $user_id, $title);
            $stmt->execute();
            $stmt->close();
            $message = "Task added!";
        }
    }

    if ($_POST["action"] == "complete_task") {
        $task_id = (int)$_POST["task_id"];
        $stmt = $conn->prepare("UPDATE tasks SET status = 'completed' WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    if ($_POST["action"] == "log_hours") {
        $hours = (float)$_POST["hours"];
        $date  = date("Y-m-d");
        if ($hours > 0) {
            $stmt = $conn->prepare("INSERT INTO study_hours (user_id, hours, date) VALUES (?, ?, ?)");
            $stmt->bind_param("ids", $user_id, $hours, $date);
            $stmt->execute();
            $stmt->close();
            $message = "Study hours logged!";
        }
    }

    // Redirect to avoid form resubmission on refresh
    header("Location: tasks.php");
    exit();
}

// Load all tasks for this user
$stmt = $conn->prepare("SELECT id, title, status, created_at FROM tasks WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Load total study hours today
$today = date("Y-m-d");
$stmt  = $conn->prepare("SELECT COALESCE(SUM(hours), 0) AS today_hours FROM study_hours WHERE user_id = ? AND date = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_hours = $stmt->get_result()->fetch_assoc()["today_hours"];
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tasks - StudyTrack</title>
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
<h2>Task Management</h2>

<?php if (!empty($message)): ?>
    <p style="color:green; text-align:center; margin-bottom:15px;"><?php echo $message; ?></p>
<?php endif; ?>

<div class="tasks-container" style="margin-top:30px;">

    <!-- Add Task -->
    <h3>Add a New Task</h3>
    <form method="POST" action="tasks.php" style="margin-top:10px;">
        <input type="hidden" name="action" value="add_task">
        <input type="text" name="title" placeholder="Task title" required>
        <button type="submit" class="btn">Add Task</button>
    </form>

    <!-- Log Study Hours -->
    <h3 style="margin-top:30px;">Log Study Hours</h3>
    <p style="margin:5px 0 10px;">Today so far: <strong><?php echo $today_hours; ?> hour(s)</strong></p>
    <form method="POST" action="tasks.php">
        <input type="hidden" name="action" value="log_hours">
        <input type="number" name="hours" placeholder="Hours studied" step="0.5" min="0.5" max="24" required>
        <button type="submit" class="btn">Log Hours</button>
    </form>

    <!-- Task List -->
    <h3 style="margin-top:30px;">Your Tasks</h3>
    <ul id="taskList" style="margin-top:10px;">

    <?php if (empty($tasks)): ?>
        <li style="padding:10px; text-align:center; color:#888;">No tasks yet. Add one above!</li>
    <?php else: ?>
        <?php foreach ($tasks as $task): ?>
        <li style="display:flex; justify-content:space-between; align-items:center; padding:10px; border-bottom:1px solid #eee;">
            <span>
                <?php if ($task["status"] == "completed"): ?>
                    <s style="color:#888;"><?php echo htmlspecialchars($task["title"]); ?></s>
                    <span style="color:green; margin-left:8px;">✓ Done</span>
                <?php else: ?>
                    <?php echo htmlspecialchars($task["title"]); ?>
                <?php endif; ?>
            </span>
            <?php if ($task["status"] == "pending"): ?>
            <form method="POST" action="tasks.php" style="margin:0;">
                <input type="hidden" name="action"  value="complete_task">
                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                <button type="submit" class="complete">Complete</button>
            </form>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    <?php endif; ?>

    </ul>
</div>

</section>

<footer>
<p>© 2026 Student Productivity Platform</p>
</footer>

</body>
</html>
