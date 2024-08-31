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
        $query = "INSERT INTO loyalty_registration (registration_date, total_registration, GC, KAB, IBEX, MM, CM, CV, TP, NGW, ZBR, LM, KS) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute(array_merge([$date, $total_registration], $stores));
    }

    fclose($handle);
    echo "CSV data uploaded successfully!";
}

// Fetch data for table display
$query = "SELECT * FROM loyalty_registration ORDER BY registration_date ASC";
$result = $conn->query($query);
$data = $result->fetchAll(PDO::FETCH_ASSOC);

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

?>

<!DOCTYPE html>
<html>
<head>
    <title>Loyalty Program Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<style>
	/* Define a container for the charts with a flex layout */
.chart-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between; /* Ensure space between charts */
    gap: 20px; /* Space between charts */
    margin: 20px auto;
}

/* Style individual canvas elements */
.chart-container canvas {
    flex: 1 1 45%; /* Allow flexing with minimum 45% width */
    max-width: 45%;
    min-width: 300px; /* Minimum width for readability */
    max-height: 300px; /* Maximum height for visibility */
    border: 1px solid #ddd; /* Optional: Border for canvas */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Optional: Shadow effect */
    padding: 10px;
    background-color: #fff; /* Background color */
    border-radius: 8px; /* Rounded corners */
    box-sizing: border-box; /* Include padding and border in element's total width and height */
}

/* Ensure the canvas containers are responsive */
canvas {
    width: 100% !important;
    height: auto !important;
}

/* Adjust for smaller screens */
@media (max-width: 768px) {
    .chart-container {
        flex-direction: column; /* Stack charts vertically */
    }

    .chart-container canvas {
        max-width: 100%; /* Full width on small screens */
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
    <!-- Data Visualization Section -->
    <h2>Data Visualization</h2>
<div class="chart-container">
    <canvas id="weeklyChart"></canvas>
    <canvas id="monthlyChart"></canvas>
    <canvas id="storePerformanceChart"></canvas>
    <canvas id="dailyAverageChart"></canvas>
    <canvas id="futureProjectionsChart"></canvas>
</div>

    <!-- CSV Upload Section -->
    <h2>Upload CSV</h2>
    <form enctype="multipart/form-data" method="post">
        <input type="file" name="csv" accept=".csv" required>
        <input type="submit" name="upload" value="Upload CSV">
    </form>

    <!-- Data Table Section -->
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
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='13'>No data available</td></tr>";
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
