<!DOCTYPE html>
<html>
<head>
    <title>Marketing Calendar</title>
    <link rel="stylesheet" href="styles.css">
    <script src="scripts.js"></script>
</head>
<body>

<?php include 'dbconnections.php'; ?>

<div class="calendar-container">
    <div class="calendar">
        <!-- Calendar view goes here -->
        <!-- Use JavaScript or a library like FullCalendar for dynamic calendar display -->
    </div>

    <div class="sidebar">
        <!-- Sidebar content goes here -->
        <h2>Upcoming Campaigns</h2>
        <ul>
            <li>Campaign 1 - Date</li>
            <li>Campaign 2 - Date</li>
            <!-- Display upcoming campaigns using dummy data -->
        </ul>
    </div>

    <div class="task-list">
        <!-- Task list content goes here -->
        <h2>Tasks for Today</h2>
        <ul>
            <li>Task 1</li>
            <li>Task 2</li>
            <!-- Display tasks for today using dummy data -->
        </ul>
    </div>
</div>
<div class="footer">
<?php
include 'footer.php'; // Include your footer file
?>
</div>
</body>
</html>
