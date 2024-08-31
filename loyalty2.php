<?php
require_once 'dbconnections.php';
require_once 'functions.php';

// Initialize variables
$message = '';
$data = [];
$stores = ['GC', 'KAB', 'IBEX', 'MM', 'CM', 'CV', 'TP', 'NGW', 'ZBR', 'LM', 'KS'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload'])) {
        $message = handleCSVUpload($conn);
    } elseif (isset($_POST['add_entry'])) {
        $message = handleSingleEntry($conn, $stores);
    } elseif (isset($_POST['delete'])) {
        $message = handleDeleteEntry($conn);
    } elseif (isset($_POST['download_csv'])) {
        downloadCSV($conn);
    }
}

// Fetch and filter data
$data = fetchData($conn);

// Prepare chart data
$chartData = prepareChartData($data);

// Corrected function to prepare day of week data
function prepareDayOfWeekData($data) {
    $dayOfWeekData = array_fill(0, 7, 0);
    $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    foreach ($data as $row) {
        $date = DateTime::createFromFormat('d/m/Y', $row['registration_date']);
        if ($date === false) {
            // Skip this row if the date is invalid
            continue;
        }
        $dayOfWeek = (int)$date->format('w'); // 0 (Sunday) to 6 (Saturday)
        $dayOfWeekData[$dayOfWeek] += (int)$row['total_registration'];
    }
    
    return array_combine($dayNames, $dayOfWeekData);
}

$dayOfWeekData = prepareDayOfWeekData($data);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loyalty Program Analytics</title>
    <link rel="stylesheet" href="main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-matrix@1.1.1/dist/chartjs-chart-matrix.min.js"></script>
    <style>
        .chart-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 20px;
            margin: 20px auto;
        }

        .chart-container canvas {
            flex: 1 1 30%;
            max-width: 30%;
            min-width: 300px;
            max-height: 300px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 10px;
            background-color: #fff;
            border-radius: 8px;
            box-sizing: border-box;
        }

        canvas {
            width: 100% !important;
            height: auto !important;
        }

        @media (max-width: 768px) {
            .chart-container {
                flex-direction: column;
            }

            .chart-container canvas {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>
    
    <h1>Loyalty Program Analytics</h1>
    <section id="charts" class="chart-container">
        <canvas id="weeklyChart"></canvas>
        <canvas id="monthlyChart"></canvas>
        <canvas id="storePerformanceChart"></canvas>
        <canvas id="dailyAverageChart"></canvas>
        <canvas id="futureProjectionsChart"></canvas>
        <canvas id="dayOfWeekChart"></canvas>
    </section>
    
    <?php if (!empty($message)): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <section id="data-entry">
        <h2>Add Single Entry</h2>
        <form method="post">
            <label for="date">Date (dd/mm/yyyy):</label>
            <input type="text" id="date" name="date" required>
            <label for="total_registration">Total Registrations:</label>
            <input type="number" id="total_registration" name="total_registration" required>
            <?php foreach ($stores as $store): ?>
              <label for="<?php echo $store; ?>"><?php echo $store; ?>:</label>
              <input type="number" id="<?php echo $store; ?>" name="<?php echo $store; ?>" required>
            <?php endforeach; ?>
            <input type="submit" name="add_entry" value="Add Entry">
        </form>
        <h2>Upload CSV</h2>
        <form enctype="multipart/form-data" method="post">
            <input type="file" name="csv" accept=".csv" required>
            <input type="submit" name="upload" value="Upload CSV">
        </form>
    </section>

    <section id="data-filter">
        <h2>Filter by Date</h2>
        <form method="post">
            <label for="start_date">Start Date (dd/mm/yyyy):</label>
            <input type="text" id="start_date" name="start_date" required>
            <label for="end_date">End Date (dd/mm/yyyy):</label>
            <input type="text" id="end_date" name="end_date" required>
            <input type="submit" name="filter" value="Filter">
        </form>
    </section>

    <section id="data-download">
        <h2>Download All Entries</h2>
        <form method="post">
            <input type="submit" name="download_csv" value="Download CSV">
        </form>
    </section>

    <section id="data-table">
        <h2>Data Table</h2>
        <table border="1">
        <thead>
            <tr>
                <th>Date</th>
                <th>Total Registrations</th>
                <th>GC</th>
                <th>KAB</th>
                <th>IBEX</th>
                <th>MM</th>
                <th>CM</th>
                <th>CV</th>
                <th>TP</th>
                <th>NGW</th>
                <th>ZBR</th>
                <th>LM</th>
                <th>KS</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (count($data) > 0) {
                foreach ($data as $row) {
                    echo "<tr>";
                    echo "<td>{$row['registration_date']}</td>";
                    echo "<td>{$row['total_registration']}</td>";
                    echo "<td>{$row['GC']}</td>";
                    echo "<td>{$row['KAB']}</td>";
                    echo "<td>{$row['IBEX']}</td>";
                    echo "<td>{$row['MM']}</td>";
                    echo "<td>{$row['CM']}</td>";
                    echo "<td>{$row['CV']}</td>";
                    echo "<td>{$row['TP']}</td>";
                    echo "<td>{$row['NGW']}</td>";
                    echo "<td>{$row['ZBR']}</td>";
                    echo "<td>{$row['LM']}</td>";
                    echo "<td>{$row['KS']}</td>";
                    echo "<td>
                        <form method='post'>
                            <input type='hidden' name='delete_date' value='{$row['registration_date']}'>
                            <input type='submit' name='delete' value='Delete'>
                        </form>
                    </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='14'>No data available</td></tr>";
            }
            ?>
        </tbody>
    </table>
    </section>

<script>
    createWeeklyChart(<?php echo json_encode($chartData['weekly']); ?>);
    createMonthlyChart(<?php echo json_encode($chartData['monthly']); ?>);
    createStorePerformanceChart(<?php echo json_encode($chartData['storePerformance']); ?>);
    createDailyAverageChart(<?php echo json_encode($chartData['dailyAverage']); ?>);
    createFutureProjectionsChart(<?php echo json_encode($chartData['futureProjections']); ?>);
    createDayOfWeekChart(<?php echo json_encode($dayOfWeekData); ?>);

    function createWeeklyChart(data) {
        var ctx = document.getElementById('weeklyChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: Object.keys(data),
                datasets: [{
                    label: 'Weekly Registrations',
                    data: Object.values(data),
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function createMonthlyChart(data) {
        var ctx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(data),
                datasets: [{
                    label: 'Monthly Registrations',
                    data: Object.values(data),
                    backgroundColor: 'rgb(153, 102, 255)',
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function createStorePerformanceChart(data) {
        var ctx = document.getElementById('storePerformanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(data),
                datasets: [{
                    label: 'Store Registrations',
                    data: Object.values(data),
                    backgroundColor: 'rgb(255, 159, 64)',
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function createDailyAverageChart(data) {
        var ctx = document.getElementById('dailyAverageChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Daily Average', 'Remaining Capacity'],
                datasets: [{
                    data: [data, 100 - data],
                    backgroundColor: ['rgb(54, 162, 235)', 'rgb(255, 205, 86)']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Daily Average Registrations'
                    }
                }
            }
        });
    }

    function createFutureProjectionsChart(data) {
        var ctx = document.getElementById('futureProjectionsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: Object.keys(data),
                datasets: [{
                    label: 'Projected Monthly Registrations',
                    data: Object.values(data),
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function createDayOfWeekChart(data) {
        var ctx = document.getElementById('dayOfWeekChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(data),
                datasets: [{
                    label: 'All-time Registrations by Day of Week',
                    data: Object.values(data),
                    backgroundColor: 'rgb(75, 192, 192)',
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'All-time Day of Week Performance'
                    }
                }
            }
        });
    }

</script>

</body>
</html>