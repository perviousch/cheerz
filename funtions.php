<?php
// functions.php

/**
 * Formats a date string from d/m/Y to Y-m-d
 *
 * @param string $date Date string in d/m/Y format
 * @return string|false Formatted date string or false if invalid
 */
function formatDate($date) {
    $dateObj = DateTime::createFromFormat('d/m/Y', $date);
    return $dateObj ? $dateObj->format('Y-m-d') : false;
}

/**
 * Handles CSV file upload and inserts data into the database
 *
 * @param PDO $conn Database connection object
 * @return string Success or error message
 */
function handleCSVUpload($conn) {
    if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        return "Error uploading file.";
    }

    $file = $_FILES['csv']['tmp_name'];
    $handle = fopen($file, 'r');
    $row = 0;
    $insertedCount = 0;

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $row++;
        if ($row == 1) continue; // Skip header row

        $date = formatDate($data[0]);
        if (!$date) continue; // Skip if date conversion fails

        $total_registration = intval($data[1]);
        $stores = array_map('intval', array_slice($data, 2, 11));

        // Check if the date already exists in the database
        $stmt = $conn->prepare("SELECT COUNT(*) FROM loyalty_registration WHERE registration_date = ?");
        $stmt->execute([$date]);
        if ($stmt->fetchColumn() > 0) continue; // Skip existing dates

        // Insert new data
        $query = "INSERT INTO loyalty_registration (registration_date, total_registration, GC, KAB, IBEX, MM, CM, CV, TP, NGW, ZBR, LM, KS) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute(array_merge([$date, $total_registration], $stores));
        $insertedCount++;
    }

    fclose($handle);
    return "CSV data uploaded successfully. $insertedCount new entries added.";
}

/**
 * Handles single entry addition to the database
 *
 * @param PDO $conn Database connection object
 * @param array $stores Array of store names
 * @return string Success or error message
 */
function handleSingleEntry($conn, $stores) {
    $date = formatDate($_POST['date']);
    $total_registration = intval($_POST['total_registration']);

    if (!$date) {
        return "Invalid date format.";
    }

    // Extract store values from POST data
    $storeValues = [];
    foreach ($stores as $store) {
        $storeValues[] = isset($_POST[$store]) ? intval($_POST[$store]) : 0;
    }

    // Check if the date already exists in the database
    $stmt = $conn->prepare("SELECT COUNT(*) FROM loyalty_registration WHERE registration_date = ?");
    $stmt->execute([$date]);
    if ($stmt->fetchColumn() > 0) {
        return "Date already exists in the database.";
    }

    // Insert new data
    $query = "INSERT INTO loyalty_registration (registration_date, total_registration, " . implode(", ", $stores) . ") 
              VALUES (?, ?, " . implode(", ", array_fill(0, count($stores), "?")) . ")";
    $stmt = $conn->prepare($query);
    $stmt->execute(array_merge([$date, $total_registration], $storeValues));

    return "Entry added successfully!";
}

/**
 * Handles deletion of an entry from the database
 *
 * @param PDO $conn Database connection object
 * @return string Success or error message
 */
function handleDeleteEntry($conn) {
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM loyalty_registration WHERE id = ?");
    $stmt->execute([$id]);
    return "Entry deleted successfully!";
}

/**
 * Fetches data from the database based on optional date filters
 *
 * @param PDO $conn Database connection object
 * @return array Fetched data
 */
function fetchData($conn) {
    $query = "SELECT * FROM loyalty_registration";
    $params = [];

    if (isset($_POST['filter'])) {
        $start_date = formatDate($_POST['start_date']);
        $end_date = formatDate($_POST['end_date']);

        if ($start_date && $end_date) {
            $query .= " WHERE registration_date BETWEEN ? AND ?";
            $params = [$start_date, $end_date];
        }
    }

    $query .= " ORDER BY registration_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Prepares data for various charts
 *
 * @param array $data Raw data from the database
 * @return array Processed data for charts
 */
function prepareChartData($data) {
    $weeklyData = [];
    $monthlyData = [];
    $storeData = array_fill_keys(['GC', 'KAB', 'IBEX', 'MM', 'CM', 'CV', 'TP', 'NGW', 'ZBR', 'LM', 'KS'], 0);
    $totalRegistrations = 0;

    foreach ($data as $row) {
        $date = $row['registration_date'];
        $weekNumber = date('Y-W', strtotime($date));
        $monthKey = date('Y-m', strtotime($date));

        // Weekly data
        if (!isset($weeklyData[$weekNumber])) {
            $weeklyData[$weekNumber] = 0;
        }
        $weeklyData[$weekNumber] += $row['total_registration'];

        // Monthly data
        if (!isset($monthlyData[$monthKey])) {
            $monthlyData[$monthKey] = 0;
        }
        $monthlyData[$monthKey] += $row['total_registration'];

        // Store-wise data
        foreach ($storeData as $store => $value) {
            $storeData[$store] += $row[$store];
        }

        $totalRegistrations += $row['total_registration'];
    }

    // Calculate daily average
    $dailyAverage = count($data) > 0 ? $totalRegistrations / count($data) : 0;

    // Future projections (simple linear regression)
    $futureProjections = calculateFutureProjections($monthlyData);

    return [
        'weekly' => $weeklyData,
        'monthly' => $monthlyData,
        'storePerformance' => $storeData,
        'dailyAverage' => $dailyAverage,
        'futureProjections' => $futureProjections
    ];
}

/**
 * Calculates future projections using simple linear regression
 *
 * @param array $monthlyData Monthly registration data
 * @return array Future projections
 */
function calculateFutureProjections($monthlyData) {
    $xValues = array_keys($monthlyData);
    $yValues = array_values($monthlyData);
    $xValuesNumeric = array_map('strtotime', $xValues);
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

    $lastDate = end($xValues);
    $futureMonths = [];
    $projections = [];

    for ($i = 1; $i <= 12; $i++) {
        $futureMonth = date('Y-m', strtotime($lastDate . " +$i months"));
        $futureMonths[] = $futureMonth;
        $timestamp = strtotime($futureMonth);
        $projections[] = max(0, $slope * $timestamp + $intercept); // Ensure non-negative projections
    }

    return [
        'months' => $futureMonths,
        'values' => $projections
    ];
}

/**
 * Generates and outputs a CSV file with all data
 *
 * @param PDO $conn Database connection object
 */
function downloadCSV($conn) {
    $stmt = $conn->query("SELECT * FROM loyalty_registration ORDER BY registration_date ASC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="loyalty_data.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, array_keys($data[0])); // Header row

    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}
?>