<link rel="stylesheet" href="main.css">
<?php
include 'dbconnections.php'; // Include your database connection file

// Function to display all dispatches
function getAllDispatches($conn) {
    $stmt = $conn->query("SELECT d.id, d.dispatch_date, d.notes, dd.branch_id, b.name AS branch_name, m.name AS material_name, dd.quantity
                         FROM dispatches d
                         JOIN dispatch_details dd ON d.id = dd.dispatch_id
                         JOIN branches b ON dd.branch_id = b.id
                         JOIN materials m ON dd.material_id = m.id
                         ORDER BY d.dispatch_date DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to add a new dispatch
function addDispatch($conn, $dispatch_date, $notes) {
    $stmt = $conn->prepare("INSERT INTO dispatches (dispatch_date, notes) VALUES (?, ?)");
    $stmt->execute([$dispatch_date, $notes]);
    return $conn->lastInsertId();
}

// Function to add dispatch details
function addDispatchDetails($conn, $dispatch_id, $branch_id, $material_id, $quantity) {
    $stmt = $conn->prepare("INSERT INTO dispatch_details (dispatch_id, branch_id, material_id, quantity) VALUES (?, ?, ?, ?)");
    $stmt->execute([$dispatch_id, $branch_id, $material_id, $quantity]);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dispatch_date = $_POST["dispatch_date"];
    $notes = $_POST["notes"];
    $branch_ids = $_POST["branch_ids"];
    $material_ids = $_POST["material_ids"];
    $quantities = $_POST["quantities"];

    // Start a transaction
    $conn->beginTransaction();

    try {
        // Add dispatch
        $dispatch_id = addDispatch($conn, $dispatch_date, $notes);

        // Add dispatch details
        for ($i = 0; $i < count($branch_ids); $i++) {
            addDispatchDetails($conn, $dispatch_id, $branch_ids[$i], $material_ids[$i], $quantities[$i]);
        }

        // Commit the transaction
        $conn->commit();
        echo "Dispatch added successfully!";
    } catch (Exception $e) {
        // Roll back the transaction if an error occurred
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}

// Get all dispatches
$dispatches = getAllDispatches($conn);
?>
<!DOCTYPE html>
<html>

<head>
  <title>Inventory Management</title>
  
  <link rel="stylesheet" type="text/css" href="main.css">
  
  <script src="script.js"></script>
  <style>
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
<h1>Dispatch Management</h1>

<h2>Add New Dispatch</h2>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
    <label for="dispatch_date">Dispatch Date:</label>
    <input type="date" id="dispatch_date" name="dispatch_date" required><br>

    <label for="notes">Notes:</label>
    <textarea id="notes" name="notes"></textarea><br>

    <label>Branch and Material:</label>
    <div id="branch-material-containers">
        <div class="branch-material-container">
            <select name="branch_ids[]" required>
                <option value="">Select Branch</option>
                <?php
                $stmt = $conn->query("SELECT id, name FROM branches");
                $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($branches as $branch) {
                    echo '<option value="' . $branch['id'] . '">' . $branch['name'] . '</option>';
                }
                ?>
            </select>

            <select name="material_ids[]" required>
                <option value="">Select Material</option>
                <?php
                $stmt = $conn->query("SELECT id, name FROM materials");
                $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($materials as $material) {
                    echo '<option value="' . $material['id'] . '">' . $material['name'] . '</option>';
                }
                ?>
            </select>

            <input type="number" name="quantities[]" min="1" value="1" required>
        </div>
    </div>
    <button type="button" onclick="addBranchMaterialContainer()">Add More</button><br>

    <input type="submit" value="Submit">
</form>

<h2>Dispatch History</h2>
<table>
    <tr>
        <th>Dispatch Date</th>
        <th>Notes</th>
        <th>Branch</th>
        <th>Material</th>
        <th>Quantity</th>
    </tr>
    <?php foreach ($dispatches as $dispatch): ?>
        <tr>
            <td><?php echo $dispatch['dispatch_date']; ?></td>
            <td><?php echo $dispatch['notes']; ?></td>
            <td><?php echo $dispatch['branch_name']; ?></td>
            <td><?php echo $dispatch['material_name']; ?></td>
            <td><?php echo $dispatch['quantity']; ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<script>
    function addBranchMaterialContainer() {
        var container = document.createElement("div");
        container.classList.add("branch-material-container");

        var branchSelect = document.createElement("select");
        branchSelect.name = "branch_ids[]";
        branchSelect.required = true;
        var option = document.createElement("option");
        option.value = "";
        option.text = "Select Branch";
        branchSelect.add(option);
        <?php foreach ($branches as $branch): ?>
            var option = document.createElement("option");
            option.value = "<?php echo $branch['id']; ?>";
            option.text = "<?php echo $branch['name']; ?>";
            branchSelect.add(option);
        <?php endforeach; ?>
        container.appendChild(branchSelect);

        var materialSelect = document.createElement("select");
        materialSelect.name = "material_ids[]";
        materialSelect.required = true;
        var option = document.createElement("option");
        option.value = "";
        option.text = "Select Material";
        materialSelect.add(option);
        <?php foreach ($materials as $material): ?>
            var option = document.createElement("option");
            option.value = "<?php echo $material['id']; ?>";
            option.text = "<?php echo $material['name']; ?>";
            materialSelect.add(option);
        <?php endforeach; ?>
        container.appendChild(materialSelect);

        var quantityInput = document.createElement("input");
        quantityInput.type = "number";
        quantityInput.name = "quantities[]";
        quantityInput.min = "1";
        quantityInput.value = "1";
        quantityInput.required = true;
        container.appendChild(quantityInput);

        document.getElementById("branch-material-containers").appendChild(container);
    }
</script>

<?php
include 'footer.php'; // Include your footer file
?>
</body>
</html>