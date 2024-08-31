<?php
// DB connection
include 'dbconnections.php';

if(isset($_POST['update_task_status'])) {
  $task_id = $_POST['task_id'];
  $task_status = $_POST['task_status'];
  
  // Update SQL query
  $sql = "UPDATE tasks SET status = :status WHERE task_id = :task_id";
  $stmt = $conn->prepare($sql);
  $stmt->bindParam(':status', $task_status);
  $stmt->bindParam(':task_id', $task_id);
  
  if ($stmt->execute()) {
    echo "Task status updated successfully.";
  } else {
    echo "Error updating task status.";
  }
}
?>
