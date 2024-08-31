<?php
include 'dbconnections.php';

try {
    // Campaign List
    $sql = "SELECT * FROM campaigns ORDER BY date_from DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Marketing Material
    $sql = "SELECT * FROM campaign_materials WHERE campaign_id = ?";
    $stmt = $conn->prepare($sql);

    // File Upload
    if (isset($_POST["submit"])) {
        $campaign_id = $_POST["campaign_id"];
        $file_name = $_FILES["file"]["name"];
        $file_tmp = $_FILES["file"]["tmp_name"];
        $file_path = "uploads/" . $file_name;

        move_uploaded_file($file_tmp, $file_path);

        $sql = "INSERT INTO campaign_materials (campaign_id, name, file_path) VALUES (:campaign_id, :name, :file_path)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":campaign_id", $campaign_id, PDO::PARAM_INT);
        $stmt->bindParam(":name", $file_name, PDO::PARAM_STR);
        $stmt->bindParam(":file_path", $file_path, PDO::PARAM_STR);
        $stmt->execute();
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Marketing Calendar</title>
    <link rel="stylesheet" href="styles.css">
    <script src="scripts.js"></script>
    <!-- FullCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="header-container">
        <header>
            <?php include('header.php'); ?>
        </header>
    </div>

    <h2>Campaign List</h2>
    <table>
        <tr>
            <th>Name</th>
            <th>ID</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Status</th>
        </tr>
        <?php foreach ($campaigns as $campaign): ?>
            <tr>
                <td><?= $campaign["name"] ?></td>
                <td><?= $campaign["campaign_id"] ?></td>
                <td><?= $campaign["date_from"] ?></td>
                <td><?= $campaign["date_to"] ?></td>
                <td><?= ($campaign["date_to"] >= date("Y-m-d")) ? "Ongoing" : "Completed" ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Marketing Material</h2>
    <?php foreach ($campaigns as $campaign): ?>
        <h3><?= $campaign["name"] ?></h3>
        <ul>
            <?php
            $stmt->execute([$campaign["id"]]);
            $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($materials as $material):
            ?>
                <li><a href="<?= $material["file_path"] ?>" target="_blank"><?= $material["name"] ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>

    <h2>Social Media Post Scheduler</h2>
    <!-- Code to integrate with social media APIs -->

    <h2>File Upload</h2>
    <form method="post" enctype="multipart/form-data">
        <label for="campaign">Campaign:</label>
        <select name="campaign_id">
            <?php foreach ($campaigns as $campaign): ?>
                <option value="<?= $campaign["id"] ?>"><?= $campaign["name"] ?></option>
            <?php endforeach; ?>
        </select>
        <input type="file" name="file">
        <input type="submit" name="submit" value="Upload">
    </form>
	<div class="footer">
<?php
include 'footer.php'; // Include your footer file
?>
</div>
</body>
</html>
