<?php
// DB connection
include 'dbconnections.php';

// Date format function  
function formatDate($date) {
  return date('Y-m-d', strtotime($date)); 
}

// Add task
if(isset($_POST['add_task'])) {
  $task_id = mt_rand(100000, 999999);
  $task_name = $_POST['task_name'];
  $task_date = formatDate($_POST['task_date']);
  $task_type = $_POST['task_type'];
  $details = $_POST['details'];
  
  // Insert SQL query
  $sql = "INSERT INTO tasks(task_id, task_name, task_date, task_type, status, details)
          VALUES ($task_id, '$task_name', '$task_date', '$task_type', 'Not Done', '$details')";

  $conn->exec($sql);
}

// Update task status
if(isset($_POST['update_task_status'])) {
  $task_id = $_POST['task_id'];
  
  // Update SQL query
  $sql = "UPDATE tasks SET status = 'Done' WHERE task_id = $task_id";
  $conn->exec($sql);
}

// Get tasks   
$sql = "SELECT * FROM tasks ORDER BY task_date";
$result = $conn->query($sql);
$tasks = [];

$doneCount = 0;
$undoneCount = 0;

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
?>

<!DOCTYPE html>
<html>
<head>
  <title>Calendar</title>

  <!-- FullCalendar -->
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
window.embeddedChatbotConfig = {
chatbotId: "PT7vFg6-7c4AMTe8I8oZ6",
domain: "www.chatbase.co"
}
</script>
<script
src="https://www.chatbase.co/embed.min.js"
chatbotId="PT7vFg6-7c4AMTe8I8oZ6"
domain="www.chatbase.co"
defer>
</script>

  <style>  
      .graph-analysis {
      width: 100%;
      max-width: 600px;
      margin: 20px auto;
    }
    #calendar-container {
      max-width: 900px;
      margin: 0 auto;
    }
    .fc-event {
      cursor: pointer;
    }
    .fc-event.done {
      background: #81C784;  
    }
    .fc-event.not-done {
      background: #D84E00;
    }
	.header-container {
    background-color: #f2f2f2;
    padding: 10px;
    text-align: center;
}

.content-container {
    display: flex;
}

.task-form {
    flex: 1;
    margin-right: 20px;
}

.calendar-details-container {
    flex: 2;
    position: relative;
}

#calendar-container {
    margin-bottom: 20px;
}

#details-panel {
    position: absolute;
    top: 0;
    right: 0;
    background-color: #fff;
    border: 1px solid #ccc;
    padding: 10px;
    width: 200px;
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
        label: 'undone',
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
		<!-- Graphical Analysis -->
          <div class="graph-analysis">
             <canvas id="taskChart"></canvas>
          </div>
	</div>
        <!-- Calendar and Details Panel -->
        <div class="calendar-details-container">
            <div id='calendar-container'></div>

            <div id='details-panel' style='display: none; background-color: #fff; border: 1px solid #ccc; padding: 10px;'>
                <h2>Task Details</h2>
            </div>
        </div>
    </div>
</body>
</html>
