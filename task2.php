<?php
// DB connection
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
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
  $task = [
    'id' => $row['task_id'],
    'title' => $row['task_name'],
    'start' => $row['task_date'],
    'classNames' => ($row['status'] === 'Done') ? 'done' : 'not-done',
    'extendedProps' => [
      'taskId' => $row['task_id'],
      'details' => $row['details'] // Make sure 'details' field exists in your tasks table
    ]
  ];
  
  if ($row['status'] === 'Done') {
    $doneCount++;
  } else {
    $undoneCount++;
  }

  $tasks[] = $task;
}

// Calculate the first and last day of the current month
$currentMonthStart = date('Y-m-01'); // First day of the current month
$currentMonthEnd = date('Y-m-t'); // Last day of the current month

// Modify the SQL query to fetch tasks for the current month
$sql = "SELECT * FROM tasks WHERE task_date BETWEEN '$currentMonthStart' AND '$currentMonthEnd' ORDER BY task_date";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html>
<head>
  <title>Calendar</title>

  <!-- FullCalendar and Chart.js -->
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <style>  
    /* Your existing styles */
    .graph-analysis {
      width: 100%;
      max-width: 600px;
      margin: 20px auto;
    }
  </style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var tasks = <?php echo json_encode($tasks); ?>;
  buildCalendar(tasks);
  renderChart();
});

function renderChart() {
  var ctx = document.getElementById('taskChart').getContext('2d');
  var chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Tasks'],
      datasets: [{
        label: 'Done',
        data: [<?php echo $doneCount; ?>],
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        borderColor: 'rgba(75, 192, 192, 1)',
        borderWidth: 1
      }, {
        label: 'Undone',
        data: [<?php echo $undoneCount; ?>],
        backgroundColor: 'rgba(255, 99, 132, 0.2)',
        borderColor: 'rgba(255, 99, 132, 1)',
        borderWidth: 1
      }]
    },
    options: {
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
}
</script>
<!-- Your existing script for calendar initialization -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var tasks = <?php echo json_encode($tasks); ?>;
      buildCalendar(tasks);
    });
function buildCalendar(tasks) {
  var calendarEl = document.getElementById('calendar-container');
  var detailsPanel = document.getElementById('details-panel');

  var calendar = new FullCalendar.Calendar(calendarEl, {
    events: tasks,
    initialView: 'dayGridMonth',
    eventClick: function(info) {
      var eventObj = info.event;
      var eventId = eventObj.extendedProps.taskId;
      
      if (eventObj.classNames[0] === "done") {
        eventObj.setProp("classNames", ["not-done"]);
        updateTaskStatus(eventId, 'Not Done');
      } else if (eventObj.classNames[0] === "not-done") {
        eventObj.setProp("classNames", ["done"]);
        updateTaskStatus(eventId, 'Done');
      }
    },
    eventMouseEnter: function(info) {
      if (info.event.extendedProps.details) {
        detailsPanel.innerHTML = info.event.extendedProps.details;
        detailsPanel.style.display = 'block';
      }
    },
    eventMouseLeave: function(info) {
      detailsPanel.style.display = 'none';
    },
    eventDidMount: function(info) {
      var eventDate = new Date(info.event.start);
      var currentDate = new Date();
      var isTaskDone = info.event.classNames.includes('done');
      
      if (eventDate < currentDate && !isTaskDone) {
        var isSameDay = eventDate.getDate() === currentDate.getDate() &&
                        eventDate.getMonth() === currentDate.getMonth() &&
                        eventDate.getFullYear() === currentDate.getFullYear();
        if (!isSameDay) {
          info.el.style.backgroundColor = 'red';
          info.el.classList.add('not-done-past');
        }
      }

      // Adding click event to the red events
      if (info.el.classList.contains('not-done-past')) {
        info.el.addEventListener('click', function() {
          var eventObj = info.event;
          var eventId = eventObj.extendedProps.taskId;
          
          if (eventObj.classNames[0] === "done") {
            eventObj.setProp("classNames", ["not-done"]);
            updateTaskStatus(eventId, 'Not Done');
          } else if (eventObj.classNames[0] === "not-done") {
            eventObj.setProp("classNames", ["done"]);
            updateTaskStatus(eventId, 'Done');
          }
        });
      }
    }
  });

  calendar.render();
}





    function updateTaskStatus(taskId, status) {
      $.ajax({
        type: 'POST',
        url: 'status_update.php', // Change this to your current PHP page name
        data: {
          update_task_status: true,
          task_id: taskId,
          task_status: status
        },
        success: function(response) {
          console.log('Task status updated successfully.');
        },
        error: function(xhr, status, error) {
          console.error('Error updating task status:', error);
        }
      });
    }
  </script>

</head>
<body>
<!-- Your existing HTML structure -->
 <div class="header-container">
        <header>
            <?php include('header.php'); ?>
        </header>
    </div>

    <div class="content-container">
	<div class="graph-analysis">
        <!-- Form -->
        <form method="post" class="task-form">
            <input name="task_name" placeholder="Task">
            <input type="date" name="task_date">
            <select name="task_type">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option> 
            </select>
            <textarea type="text" name="details" placeholder="Task Details"></textarea>
            <button type="submit" name="add_task">Add Task</button>
        </form>
	</div>
        <!-- Calendar and Details Panel -->
        <div class="calendar-details-container">
            <div id='calendar-container'></div>

            <div id='details-panel' style='display: none; background-color: #fff; border: 1px solid #ccc; padding: 10px;'>
                <h2>Task Details</h2>
            </div>
        </div>
    </div>

<!-- Graphical Analysis -->
<div class="graph-analysis">
    <canvas id="taskChart"></canvas>
</div>

</body>
</html>
