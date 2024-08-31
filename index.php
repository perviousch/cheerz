<!DOCTYPE html>
<html>
<?php include 'dbconnections.php'; ?>
<head>
    <title>Marketing Calendar</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="styles.css">
    <script src="scripts.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/vis/4.21.0/vis.min.css" rel="stylesheet" type="text/css" />
	<style>
        /* CSS for the dashboard sections */
        section {
            padding: 20px;
            border: 1px solid #ccc;
            margin-bottom: 20px;
        }

        .card {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            transition: transform 0.3s ease-in-out;
        }

        .card:hover {
            transform: scale(1.05);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        }

        h2 {
            margin-bottom: 10px;
        }

        h3 {
            margin-bottom: 5px;
        }

        button {
            padding: 5px 10px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        button:hover {
            background-color: #2980b9;
        }
        /* CSS for the timeline container */
        #timeline {
            height: 400px;
            border: 1px solid #ccc;
            margin-bottom: 20px;
        }
		/* CSS for the notification bar */
.notification-bar {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 10px 20px;
    background-color: #333;
    color: #fff;
    border-radius: 5px;
    z-index: 100;
    display: none;
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.notification-bar.show {
    display: block;
    opacity: 1;
}
    </style>
	    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var header = document.querySelector('header');
            var headerContainer = document.querySelector('.header-container');
            var headerHeight = header.offsetHeight;

            window.addEventListener('scroll', function() {
                if (window.scrollY > headerHeight) {
                    headerContainer.classList.add('fixed-header');
                } else {
                    headerContainer.classList.remove('fixed-header');
                }
            });
        });
    </script>
    <script>
        // JavaScript functions for handling schedule form submission
        function addSchedule() {
            var type = document.getElementById('type').value;
            var name = document.getElementById('name').value;
            var dateFrom = document.getElementById('date_from').value;
            var dateTo = document.getElementById('date_to').value;

            // Send form data to respective PHP script based on type
            if (type === 'promo') {
                window.location.href = 'handle_schedule.php?type=promo&name=' + name + '&date_from=' + dateFrom + '&date_to=' + dateTo;
            } else if (type === 'campaign') {
                window.location.href = 'handle_schedule.php?type=campaign&name=' + name + '&date_from=' + dateFrom + '&date_to=' + dateTo;
            } else if (type === 'post') {
                window.location.href = 'handle_schedule.php?type=post&name=' + name + '&date_from=' + dateFrom + '&date_to=' + dateTo;
            }
        }
    </script>
</head>
<body>
    <div class="header-container">
        <header>
            <?php include('header.php'); ?>
        </header>
    </div>

    <section>
        <h2>Schedule Entry</h2>
        <form onsubmit="event.preventDefault(); addSchedule()">
            <label for="type">Type:</label>
            <select id="type" name="type">
                <option value="promo">Promotion</option>
                <option value="campaign">Campaign</option>
                <option value="post">Posts</option>
            </select><br><br>
            <label for="name">Name:</label>
            <input type="text" id="name" name="name"><br><br>
            <label for="date_from">Date From:</label>
            <input type="date" id="date_from" name="date_from"><br><br>
            <label for="date_to">Date To:</label>
            <input type="date" id="date_to" name="date_to"><br><br>
            <button type="submit">Add Schedule</button>
        </form>
    </section>

    <div id="timeline"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/vis/4.21.0/vis.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get data from multiple tables (promofiles, posts, campaigns)
            // Replace this with your database connection and queries
            var items = [
                <?php
                // Fetch data from promofiles
                $stmt = $conn->query("SELECT * FROM promofiles");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "{ id: 'PR-" . $row['promo_id'] . "', content: 'Promo: " . $row['promo_name'] . "', start: '" . $row['date_from'] . "', end: '" . $row['date_to'] . "' },\n";
                }

                // Fetch data from posts
                $stmt = $conn->query("SELECT * FROM posts");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "{ id: 'PS-" . $row['post_id'] . "', content: 'Post: " . $row['name'] . "', start: '" . $row['date_from'] . "', end: '" . $row['date_to'] . "' },\n";
                }

                // Fetch data from campaigns
                $stmt = $conn->query("SELECT * FROM campaigns");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "{ id: 'CP-" . $row['campaign_id'] . "', content: 'Campaign: " . $row['name'] . "', start: '" . $row['date_from'] . "', end: '" . $row['date_to'] . "' },\n";
                }
                ?>
            ];
            // Define colors for different types
            var colors = {
                promo: '#1e50ee', // Blue for promos
                campaign: '#2ecc71', // Green for campaigns
                post: '#e74c3c', // Red for posts
            };

            // Assign colors based on item type
            items.forEach(function(item) {
                item.style = 'background-color:' + colors[item.type] + ';';
            });
            var options = {
                // Timeline configuration options
                // You can customize the timeline appearance and behavior here
            };

            var container = document.getElementById('timeline');
            var timeline = new vis.Timeline(container, new vis.DataSet(items), options);
        });
    </script>
	<script>
    // Function to display the notification bar
    function showNotification(type, name, isStarting) {
        var notificationBar = document.getElementById('notificationBar');
        var message;

        if (isStarting) {
            message = `${type}: ${name} is starting in 8 hours`;
        } else {
            message = `${type}: ${name} is ending in 8 hours`;
        }

        notificationBar.textContent = message;
        notificationBar.classList.add('show');

        setTimeout(function() {
            notificationBar.classList.remove('show');
        }, 5000); // Hide the notification bar after 5 seconds
    }

    // Check for items starting or ending in 8 hours
    var currentDate = new Date();
    var eightHoursFromNow = new Date(currentDate.getTime() + (8 * 60 * 60 * 1000));

    items.forEach(function(item) {
        var startDate = new Date(item.start);
        var endDate = new Date(item.end);

        // Check if the item is starting in 8 hours
        if (startDate >= currentDate && startDate <= eightHoursFromNow) {
            var type = item.id.split('-')[0].toLowerCase();
            var name = item.content.split(': ')[1];
            showNotification(type, name, true);
        }

        // Check if the item is ending in 8 hours
        if (endDate >= currentDate && endDate <= eightHoursFromNow) {
            var type = item.id.split('-')[0].toLowerCase();
            var name = item.content.split(': ')[1];
            showNotification(type, name, false);
        }
    });
</script>
	<div class="notification-bar" id="notificationBar"></div>
</body>
</html>
