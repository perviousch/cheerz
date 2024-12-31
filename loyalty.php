<link rel="stylesheet" href="main.css">
<?php
include 'dbconnections.php';

// Function to convert date format from dd/mm/yyyy to YYYY-MM-DD
function formatDate($date) {
    $dateObj = DateTime::createFromFormat('d/m/Y', $date);
    return $dateObj ? $dateObj->format('Y-m-d') : false;
}

// Handle CSV upload
if (isset($_POST['upload'])) {
    $file = $_FILES['csv']['tmp_name'];
    $handle = fopen($file, 'r');
    $row = 0;

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $row++;
        if ($row == 1) continue; // Skip header row

        $date = formatDate($data[0]);
        if (!$date) continue; // Skip if date conversion fails

        $total_registration = $data[1];
        $stores = array_slice($data, 2);

        // Check if the date already exists in the database
        $checkQuery = "SELECT COUNT(*) FROM loyalty_registration WHERE registration_date = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([$date]);
        $count = $stmt->fetchColumn();

        if ($count > 0) continue; // Skip existing dates

        // Insert new data
        $query = "INSERT INTO loyalty_registration (registration_date, total_registration, GC, KAB, IBEX, MM, CM, CV, TP, NGW, ZBR, LM, KS, SLT, LBM) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute(array_merge([$date, $total_registration], $stores));
    }

    fclose($handle);
    echo "CSV data uploaded successfully!";
}

// Handle single entry submission
if (isset($_POST['submit_single'])) {
    $date = $_POST['date'];
    $total_registration = $_POST['total_registration'];
    $stores = [
        $_POST['GC'], $_POST['KAB'], $_POST['IBEX'], $_POST['MM'],
        $_POST['CM'], $_POST['CV'], $_POST['TP'], $_POST['NGW'],
        $_POST['ZBR'], $_POST['LM'], $_POST['KS'], $_POST['SLT'], $_POST['LBM']
    ];

    $query = "INSERT INTO loyalty_registration (registration_date, total_registration, GC, KAB, IBEX, MM, CM, CV, TP, NGW, ZBR, LM, KS, SLT, LBM) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute(array_merge([$date, $total_registration], $stores));

    echo "Single entry added successfully!";
}

// Fetch data for table display and charts
$query = "SELECT * FROM loyalty_registration ORDER BY registration_date ASC";
$result = $conn->query($query);
$data = $result->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for charts
$dates = array_column($data, 'registration_date');
$totalRegistrations = array_column($data, 'total_registration');
$stores = ['GC', 'KAB', 'IBEX', 'MM', 'CM', 'CV', 'TP', 'NGW', 'ZBR', 'LM', 'KS', 'SLT', 'LBM'];

// Weekly data
$weeklyData = [];
foreach ($data as $row) {
    $weekNumber = date('W', strtotime($row['registration_date']));
    $year = date('Y', strtotime($row['registration_date']));
    $weekKey = $year . '-W' . $weekNumber;
    if (!isset($weeklyData[$weekKey])) {
        $weeklyData[$weekKey] = 0;
    }
    $weeklyData[$weekKey] += $row['total_registration'];
}

// Monthly data
$monthlyData = [];
foreach ($data as $row) {
    $monthKey = date('Y-m', strtotime($row['registration_date']));
    if (!isset($monthlyData[$monthKey])) {
        $monthlyData[$monthKey] = 0;
    }
    $monthlyData[$monthKey] += $row['total_registration'];
}

// Store-wise data
$storeData = [];
foreach ($stores as $store) {
    $storeData[$store] = array_sum(array_column($data, $store));
}

// Daily average
$dailyAverage = count($data) > 0 ? array_sum($totalRegistrations) / count($data) : 0;

// Future projections (simple linear regression)
$xValues = array_keys($monthlyData);
$yValues = array_values($monthlyData);
$xValuesNumeric = array_map('strtotime', $xValues);
$n = count($xValuesNumeric);

if ($n > 1) {
    $sumX = array_sum($xValuesNumeric);
    $sumY = array_sum($yValues);
    $sumXY = array_sum(array_map(function($x, $y) { return $x * $y; }, $xValuesNumeric, $yValues));
    $sumXX = array_sum(array_map(function($x) { return $x * $x; }, $xValuesNumeric));

    $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
    $intercept = ($sumY - $slope * $sumX) / $n;

    // Generate future dates
    $lastDate = end($xValues);
    $futureMonths = [];
    $projections = [];
    for ($i = 1; $i <= 12; $i++) {
        $futureMonth = date('Y-m', strtotime($lastDate . " +$i months"));
        $futureMonths[] = $futureMonth;
        $timestamp = strtotime($futureMonth);
        $projections[] = max(0, round($slope * $timestamp + $intercept)); // Ensure non-negative projections
    }
} else {
    $futureMonths = [];
    $projections = [];
}


// Day of week data (example data - replace with your actual data)
$dayOfWeekData = [10, 15, 20, 18, 22, 25, 12];


// Prepare JavaScript data
$jsWeeklyData = json_encode(array_values($weeklyData));
$jsMonthlyData = json_encode(array_values($monthlyData));
$jsStoreData = json_encode(array_values($storeData));
$jsFutureMonths = json_encode($futureMonths);
$jsProjections = json_encode($projections);
$jsDayOfWeekData = json_encode(array_values($dayOfWeekData));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loyalty Program Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .chart-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        .chart {
            flex: 1 1 calc(50% - 10px);
            min-width: 45%;
            background-color: #2a2a2a;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            height: 300px;
        }
        .form-container {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form {
            flex: 1;
            background-color: #2a2a2a;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #2a2a2a;
        }
        th, td {
            border: 1px solid #444;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #333;
        }
        input[type="file"], input[type="text"], input[type="number"], input[type="date"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            background-color: #333;
            border: 1px solid #444;
            color: #e0e0e0;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <header>
            <?php include('header.php'); ?>
        </header>
    </div>
    <div class="container">
        <h1>Loyalty Program Analytics</h1>

        <div class="chart-container">
            <div class="chart">
                <canvas id="weeklyChart"></canvas>
            </div>
            <div class="chart">
                <canvas id="monthlyChart"></canvas>
            </div>
            <div class="chart">
                <canvas id="storePerformanceChart"></canvas>
            </div>
            <div class="chart">
                <canvas id="dailyAverageChart"></canvas>
            </div>
            <div class="chart">
                <canvas id="futureProjectionsChart"></canvas>
            </div>
            <div class="chart">
                <canvas id="dayOfWeekChart"></canvas>
            </div>
        </div>

        <div class="form-container">
            <div class="form">
                <h2>Upload CSV</h2>
                <form enctype="multipart/form-data" method="post">
                    <input type="file" name="csv" accept=".csv" required>
                    <input type="submit" name="upload" value="Upload CSV">
                </form>
            </div>

            <div class="form">
                <h2>Add Single Entry</h2>
                <form method="post">
                    <input type="date" name="date" required>
                    <input type="number" name="total_registration" placeholder="Total Registration" required>
                    <?php foreach ($stores as $store): ?>
                        <input type="number" name="<?php echo $store; ?>" placeholder="<?php echo $store; ?>">
                    <?php endforeach; ?>
                    <input type="submit" name="submit_single" value="Add Entry">
                </form>
            </div>
        </div>

        <h2>Data Table</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Total Registrations</th>
                    <?php foreach ($stores as $store): ?>
                        <th><?php echo $store; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?php echo $row['registration_date']; ?></td>
                        <td><?php echo $row['total_registration']; ?></td>
                        <?php foreach ($stores as $store): ?>
                            <td><?php echo $row[$store]; ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Weekly Chart
        var ctxWeekly = document.getElementById('weeklyChart').getContext('2d');
        new Chart(ctxWeekly, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($weeklyData)); ?>,
                datasets: [{
                    label: 'Weekly Registrations',
                    data: <?php echo $jsWeeklyData; ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Monthly Chart
        var ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctxMonthly, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($monthlyData)); ?>,
                datasets: [{
                    label: 'Monthly Registrations',
                    data: <?php echo $jsMonthlyData; ?>,
                    backgroundColor: 'rgb(153, 102, 255)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Store Performance Chart
        var ctxStore = document.getElementById('storePerformanceChart').getContext('2d');
        new Chart(ctxStore, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($stores); ?>,
                datasets: [{
                    label: 'Store Registrations',
                    data: <?php echo $jsStoreData; ?>,
                    backgroundColor: 'rgb(255, 159, 64)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Daily Average Chart
        var ctxDaily = document.getElementById('dailyAverageChart').getContext('2d');
        new Chart(ctxDaily, {
            type: 'doughnut',
            data: {
                labels: ['Daily Average', 'Remaining Capacity'],
                datasets: [{
                    data: [<?php echo $dailyAverage; ?>, 100 - <?php echo $dailyAverage; ?>],
                    backgroundColor: ['rgb(54, 162, 235)', 'rgb(255, 205, 86)']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Daily Average Registrations'
                    }
                }
            }
        });

        // Future Projections Chart
        var ctxProjections = document.getElementById('futureProjectionsChart').getContext('2d');
        new Chart(ctxProjections, {
            type: 'line',
            data: {
                labels: <?php echo $jsFutureMonths; ?>,
                datasets: [{
                    label: 'Projected Monthly Registrations',
                    data: <?php echo $jsProjections; ?>,
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Day of Week Chart
        var ctxDayOfWeek = document.getElementById('dayOfWeekChart').getContext('2d');
        new Chart(ctxDayOfWeek, {
            type: 'bar',
            data: {
                labels: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                datasets: [{
                    label: 'Average Registrations by Day of Week',
                    data: <?php echo $jsDayOfWeekData; ?>,
                    backgroundColor: 'rgb(75, 192, 192)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>