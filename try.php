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
                <option value="promo">Promo</option>
                <option value="campaign">Campaign</option>
                <option value="post">Post</option>
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
                    echo "{ id: 'promo-" . $row['promo_id'] . "', content: 'Promo: " . $row['promo_name'] . "', start: '" . $row['date_from'] . "', end: '" . $row['date_to'] . "' },\n";
                }

                // Fetch data from posts
                $stmt = $conn->query("SELECT * FROM posts");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "{ id: 'post-" . $row['post_id'] . "', content: 'Post: " . $row['name'] . "', start: '" . $row['date_from'] . "', end: '" . $row['date_to'] . "' },\n";
                }

                // Fetch data from campaigns
                $stmt = $conn->query("SELECT * FROM campaigns");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "{ id: 'campaign-" . $row['campaign_id'] . "', content: 'Campaign: " . $row['name'] . "', start: '" . $row['date_from'] . "', end: '" . $row['date_to'] . "' },\n";
                }
                ?>
            ];

            var options = {
                // Timeline configuration options
                // You can customize the timeline appearance and behavior here
            };

            var container = document.getElementById('timeline');
            var timeline = new vis.Timeline(container, new vis.DataSet(items), options);
        });
    </script>
</body>
</html>
