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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tools</title>
    <link rel="stylesheet" href="main.css">
    <style>
        .tools-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
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

        .content-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            padding: 20px;
        }

        .section {
            flex-basis: calc(33.33% - 20px);
            margin-bottom: 20px;
        }

        .form-container {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }

        .form-container h2 {
            margin-top: 0;
        }

        .form-container form {
            display: flex;
            flex-direction: column;
        }

        .form-container input[type="text"],
        .form-container input[type="number"],
        .form-container textarea {
            margin-bottom: 10px;
            padding: 5px;
        }

        .form-container input[type="submit"] {
            align-self: flex-start;
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
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
        </header>
    </div>

    <div class="tools-container">
        <a href="task.php" class="tool-button">Task Manager</a>
        <a href="campaign.php" class="tool-button">Campaign Manager</a>
        <a href="broadcast.php" class="tool-button">Broadcast</a>
        <a href="dispatch.php" class="tool-button">Dispatch</a>
        <a href="loyalty.php" class="tool-button">Loyalty Program</a>
        <a href="checklists.php" class="tool-button">Checklists</a>
    </div>

    <div class="content-container">
        <div class="section">
            <div class="form-container">
                <h2>Aspect Ratio Calculator</h2>
                <form method="post" action="">
                    <input type="number" id="width" name="width" placeholder="Original Width" required>
                    <input type="number" id="height" name="height" placeholder="Original Height" required>
                    <input type="number" id="maxWidth" name="maxWidth" placeholder="Max Width" required>
                    <input type="number" id="maxHeight" name="maxHeight" placeholder="Max Height" required>
                    <input type="submit" name="submit" value="Calculate">
                </form>
                <?php
                if (isset($_POST['submit'])) {
                    $width = $_POST['width'];
                    $height = $_POST['height'];
                    $maxWidth = $_POST['maxWidth'];
                    $maxHeight = $_POST['maxHeight'];

                    function scaleDimensions($width, $height, $maxWidth, $maxHeight) {
                        $aspectRatio = $width / $height;
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
                    echo "<p>Original: {$width} x {$height}<br>Scaled: {$result['width']} x {$result['height']}</p>";
                }
                ?>
            </div>
        </div>

        <div class="section">
            <div class="form-container">
                <h2>Add Branch</h2>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                    <input type="text" id="branch_name" name="branch_name" placeholder="Branch Name" required>
                    <input type="text" id="branch_address" name="branch_address" placeholder="Address" required>
                    <input type="text" id="branch_city" name="branch_city" placeholder="City" required>
                    <input type="text" id="branch_state" name="branch_state" placeholder="State" required>
                    <input type="text" id="branch_zip" name="branch_zip" placeholder="Zip Code" required>
                    <input type="submit" value="Add Branch">
                </form>
            </div>
        </div>

        <div class="section">
            <div class="form-container">
                <h2>Add Material</h2>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                    <input type="text" id="material_name" name="material_name" placeholder="Material Name" required>
                    <textarea id="material_description" name="material_description" placeholder="Description"></textarea>
                    <input type="submit" value="Add Material">
                </form>
            </div>
        </div>
    </div>

    <footer>
        <?php include 'footer.php'; ?>
    </footer>
</body>
</html>