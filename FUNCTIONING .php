<?php
// Include DB connection
include 'dbconnections.php';

// Helper function to format dates
function formatDate($date) {
    return date('Y-m-d', strtotime($date));
}

// Add a new task
if (isset($_POST['add_task'])) {
    $task_id = mt_rand(100000, 999999);
    $task_name = $_POST['task_name'];
    $task_date = formatDate($_POST['task_date']);
    $task_type = $_POST['task_type'];
    $details = $_POST['details'];

    // Prepare and execute insert query
    $sql = "INSERT INTO tasks (task_id, task_name, task_date, task_type, status, details) VALUES (?, ?, ?, ?, 'Not Done', ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$task_id, $task_name, $task_date, $task_type, $details]);
}

// Update task status to 'Done'
if (isset($_POST['update_task_status'])) {
    $task_id = $_POST['task_id'];

    // Prepare and execute update query
    $sql = "UPDATE tasks SET status = 'Done' WHERE task_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$task_id]);
}

// Fetch tasks for the current month
$currentMonthStart = date('Y-m-01'); // First day of the current month
$currentMonthEnd = date('Y-m-t'); // Last day of the current month

// Prepare and execute select query
$sql = "SELECT * FROM tasks WHERE task_date BETWEEN ? AND ? ORDER BY task_date";
$stmt = $conn->prepare($sql);
$stmt->execute([$currentMonthStart, $currentMonthEnd]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize counters
$doneCount = 0;
$undoneCount = 0;

// Count done and undone tasks
foreach ($tasks as $row) {
    if ($row['status'] === 'Done') {
        $doneCount++;
    } else {
        $undoneCount++;
    }
}

// Continue with your HTML and JavaScript for displaying tasks and stats...
?>
