<link rel="stylesheet" href="main.css">
<?php
include 'dbconnections.php';
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
        $query = "INSERT INTO loyalty_registration (registration_date, total_registration, GC, KAB, IBEX, MM, CM, CV, TP, NGW, ZBR, LM, KS) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute(array_merge([$date, $total_registration], $stores));
    }

    fclose($handle);
    echo "CSV data uploaded successfully!";
}

// Handle single entry addition
if (isset($_POST['add_entry'])) {
    $date = formatDate($_POST['date']);
    $total_registration = $_POST['total_registration'];

    // Extract store values from POST data
    $stores = array_map('intval', array_slice($_POST, 2, 11));

    // Check if we have exactly 11 store values
    if (count($stores) != 11) {
        echo "Error: Expected 11 store values, but received " . count($stores);
        exit;
    }

if (isset($_POST['add_entry'])) {
    $date = formatDate($_POST['date']);
    $total_registration = $_POST['total_registration'];

    // Extract store values from POST data
    $stores = array_map('intval', array_slice($_POST, 2, 11));

    // Debug: Check if we have exactly 11 store values
    if (count($stores) != 11) {
        echo "Error: Expected 11 store values, but received " . count($stores);
        exit;
    }

    if ($date && $total_registration) {
        // Check if the date already exists in the database
        $checkQuery = "SELECT COUNT(*) FROM loyalty_registration WHERE registration_date = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([$date]);
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            $query = "INSERT INTO loyalty_registration (registration_date, total_registration, GC, KAB, IBEX, MM, CM, CV, TP, NGW, ZBR, LM, KS) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            
            // Merge date, total_registration, and store values into one array
            $parameters = array_merge([$date, $total_registration], $stores);

            // Debug: Check the number of parameters and print them
            if (count($parameters) != 13) {
                echo "Error: Expected 13 parameters, but received " . count($parameters);
                var_dump($parameters);
                exit;
            }

            // Execute the prepared statement
            $stmt->execute($parameters);
            echo "Entry added successfully!";
        } else {
            echo "Date already exists!";
        }
    } else {
        echo "Invalid date or total registration!";
    }
}
}



// Handle delete entry
if (isset($_POST['delete'])) {
    $id = $_POST['delete_id'];
    $deleteQuery = "DELETE FROM loyalty_registration WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->execute([$id]);
    echo "Entry deleted successfully!";
}

// Fetch data for table display
$filterQuery = "";
$filterParams = [];

if (isset($_POST['filter'])) {
    $start_date = formatDate($_POST['start_date']);
    $end_date = formatDate($_POST['end_date']);

    if ($start_date && $end_date) {
        $filterQuery = " WHERE registration_date BETWEEN ? AND ?";
        $filterParams = [$start_date, $end_date];
    }
}

$query = "SELECT * FROM loyalty_registration" . $filterQuery . " ORDER BY registration_date ASC";
$stmt = $conn->prepare($query);
$stmt->execute($filterParams);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for charts
$dates = array_column($data, 'registration_date');
$totalRegistrations = array_column($data, 'total_registration');
$stores = ['GC', 'KAB', 'IBEX', 'MM', 'CM', 'CV', 'TP', 'NGW', 'ZBR', 'LM', 'KS'];

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
$dailyAverage = array_sum($totalRegistrations) / count($data);

// Future projections (simple linear regression)
$xValues = array_keys($monthlyData);
$yValues = array_values($monthlyData);
$xValuesNumeric = array_map(function($date) {
    return strtotime($date);
}, $xValues);
$n = count($xValuesNumeric);
$sumX = array_sum($xValuesNumeric);
$sumY = array_sum($yValues);
$sumXY = 0;
$sumXX = 0;
for ($i = 0; $i < $n; $i++) {
    $sumXY += $xValuesNumeric[$i] * $yValues[$i];
    $sumXX += $xValuesNumeric[$i] * $xValuesNumeric[$i];
}
$slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
$intercept = ($sumY - $slope * $sumX) / $n;

// Generate future dates
$lastDate = end($xValues);
$futureMonths = [];
for ($i = 1; $i <= 12; $i++) {
    $futureMonths[] = date('Y-m', strtotime($lastDate . " +$i months"));
}

// Calculate projections
$projections = [];
foreach ($futureMonths as $month) {
    $timestamp = strtotime($month);
    $projections[] = $slope * $timestamp + $intercept;
}

// Prepare JavaScript data
$jsWeeklyData = json_encode(array_values($weeklyData));
$jsMonthlyData = json_encode(array_values($monthlyData));
$jsStoreData = json_encode(array_values($storeData));
$jsFutureMonths = json_encode($futureMonths);
$jsProjections = json_encode($projections);

// Handle CSV download
if (isset($_POST['download_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="loyalty_data.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, array('Date', 'Total Registrations', 'GC', 'KAB', 'IBEX', 'MM', 'CM', 'CV', 'TP', 'NGW', 'ZBR', 'LM', 'KS'));

    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Loyalty Program Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 20px;
            margin: 20px auto;
        }

        .chart-container canvas {
            flex: 1 1 45%;
            max-width: 45%;
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
    <div class="header-container">
        <header>
            <?php include('header.php'); ?>
            <link rel="stylesheet" href="main.css">
            <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-matrix@1.1.1/dist/chartjs-chart-matrix.min.js"></script>
        </header>
    </div>
    <h1>Loyalty Program Analytics</h1>
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

    <h2>Filter by Date</h2>
    <form method="post">
        <label for="start_date">Start Date (dd/mm/yyyy):</label>
        <input type="text" id="start_date" name="start_date" required>
        <label for="end_date">End Date (dd/mm/yyyy):</label>
        <input type="text" id="end_date" name="end_date" required>
        <input type="submit" name="filter" value="Filter">
    </form>

    <h2>Download All Entries</h2>
    <form method="post">
        <input type="submit" name="download_csv" value="Download CSV">
    </form>

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

    <script>
        // Weekly Chart
        var ctxWeekly = document.getElementById('weeklyChart').getContext('2d');
        var weeklyChart = new Chart(ctxWeekly, {
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
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Monthly Chart
        var ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
        var monthlyChart = new Chart(ctxMonthly, {
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
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Store Performance Chart
        var ctxStore = document.getElementById('storePerformanceChart').getContext('2d');
        var storeChart = new Chart(ctxStore, {
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
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Daily Average Chart
        var ctxDaily = document.getElementById('dailyAverageChart').getContext('2d');
        var dailyChart = new Chart(ctxDaily, {
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
        var projectionsChart = new Chart(ctxProjections, {
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
