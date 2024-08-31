<link rel="stylesheet" href="main.css">
<?php
include 'dbconnections.php'; // Include your database connection file

// Handle adding a new branch
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["branch_name"])) {
    $branch_name = $_POST["branch_name"];
    $branch_address = $_POST["branch_address"];
    $branch_city = $_POST["branch_city"];
    $branch_state = $_POST["branch_state"];
    $branch_zip = $_POST["branch_zip"];

    $stmt = $conn->prepare("INSERT INTO branches (name, address, city, state, zip) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$branch_name, $branch_address, $branch_city, $branch_state, $branch_zip]);

    echo "Branch added successfully!";
}

// Handle adding a new material
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["material_name"])) {
    $material_name = $_POST["material_name"];
    $material_description = $_POST["material_description"];

    $stmt = $conn->prepare("INSERT INTO materials (name, description) VALUES (?, ?)");
    $stmt->execute([$material_name, $material_description]);

    echo "Material added successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tools</title>
    <style>
        .tools-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Adjust the number of columns as needed */
            gap: 20px; /* Adjust the gap between buttons */
            padding: 20px;
        }

        .tool-button {
            padding: 10px;
            text-align: center;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .tool-button:hover {
            background-color: #ddd;
        }
footer {
    position: fixed;
    left: 0;
    bottom: 0;
    width: 100%;
    background-color: #333;
    color: #fff;
    text-align: center;
    padding: 10px;
}
    </style>
</head>
<body>
    <div class="header-container">
        <header>
            <?php include('header.php'); ?>
			<link rel="stylesheet" href="main.css">
        </header>
    </div>

    <div class="tools-container">
        <a href="task.php" class="tool-button">Task Manager</a>
        <a href="campaign.php" class="tool-button">Campaign Manager</a>
        <a href="broadcast.php" class="tool-button">Broadcast</a>
        <a href="dispatch.php" class="tool-button">Dispatch</a>
		<a href="loyalty.php" class="tool-button">loyalty program</a>
    </div>
	<h1 align="center">Aspect Ratio Calculator</h1>
    <form method="post" action="">
        <label for="width">Original Width:</label>
        <input type="number" id="width" name="width" required><br><br>

        <label for="height">Original Height:</label>
        <input type="number" id="height" name="height" required><br><br>

        <label for="maxWidth">Max Width:</label>
        <input type="number" id="maxWidth" name="maxWidth" required><br><br>

        <label for="maxHeight">Max Height:</label>
        <input type="number" id="maxHeight" name="maxHeight" required><br><br>

        <input type="submit" name="submit" value="Calculate">
    </form>

    <?php
    if (isset($_POST['submit'])) {
        $width = $_POST['width'];
        $height = $_POST['height'];
        $maxWidth = $_POST['maxWidth'];
        $maxHeight = $_POST['maxHeight'];

        function scaleDimensions($width, $height, $maxWidth, $maxHeight) {
            // Calculate the aspect ratio
            $aspectRatio = $width / $height;

            // Determine the new dimensions within the constraints
            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = $maxWidth / $aspectRatio;
            } else {
                $newWidth = $width;
                $newHeight = $height;
            }

            if ($newHeight > $maxHeight) {
                $newHeight = $maxHeight;
                $newWidth = $maxHeight * $aspectRatio;
            }

            return array('width' => round($newWidth, 2), 'height' => round($newHeight, 2));
        }

        $result = scaleDimensions($width, $height, $maxWidth, $maxHeight);
        echo "<h2>Scaled Dimensions</h2>";
        echo "Original Dimensions: {$width} x {$height}<br>";
        echo "Scaled Dimensions: {$result['width']} x {$result['height']}";
    }
    ?>
    <div class="form-container">
        <h2>Add Branch</h2>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
            <label for="branch_name">Branch Name:</label>
            <input type="text" id="branch_name" name="branch_name" required>
            <br>
            <label for="branch_address">Address:</label>
            <input type="text" id="branch_address" name="branch_address" required>
            <br>
            <label for="branch_city">City:</label>
            <input type="text" id="branch_city" name="branch_city" required>
            <br>
            <label for="branch_state">State:</label>
            <input type="text" id="branch_state" name="branch_state" required>
            <br>
            <label for="branch_zip">Zip Code:</label>
            <input type="text" id="branch_zip" name="branch_zip" required>
            <br>
            <input type="submit" value="Add Branch">
        </form>
    </div>

    <div class="form-container">
        <h2>Add Material</h2>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
            <label for="material_name">Material Name:</label>
            <input type="text" id="material_name" name="material_name" required>
            <br>
            <label for="material_description">Description:</label>
            <textarea id="material_description" name="material_description"></textarea>
            <br>
            <input type="submit" value="Add Material">
        </form>
    </div>
<div class="footer">
<?php
include 'footer.php'; // Include your footer file
?>
</div>
</body>
</html>