<link rel="stylesheet" href="main.css">
<?php
// Include database connection
include 'dbconnections.php';

// Function to generate a random promo ID
function generatePromoID() {
    return mt_rand(100000, 999999); // Generates a random 6-digit number
}

// Handle adding a new promo file
if (isset($_POST['add_promo'])) {
    $promo_id = generatePromoID(); // Generate a random promo ID
    $promo_name = $_POST['promo_name'];
    $date_from = $_POST['date_from'];
    $date_to = $_POST['date_to'];

    // Inserting data into the database
    $sql = "INSERT INTO promofiles (promo_id, promo_name, date_from, date_to) 
            VALUES ('$promo_id', '$promo_name', '$date_from', '$date_to')";
    $conn->exec($sql);
}

// Handle deletion of a promo file

if (isset($_GET['delete_promo'])) {
    $promo_id = $_GET['delete_promo'];

    // Deleting related entries from the promorun table
    $deletePromoRun = $conn->prepare("DELETE FROM promorun WHERE promo_id = ?");
    $deletePromoRun->execute([$promo_id]);

    // Deleting data from the promofiles table
    $sql = "DELETE FROM promofiles WHERE promo_id='$promo_id'";
    $conn->exec($sql);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Promotions</title>
    <!-- Include CSS or other styling -->
    <link rel="stylesheet" href="main.css">
</head>
<body>
    <div class="header-container">
        <header>
            <?php include('header.php'); ?>
        </header>
    </div>
    <h2>Add Promo File</h2>
    <form method="post" action="">
        Promo Name: <input type="text" name="promo_name">
        Date From: <input type="date" name="date_from">
        Date To: <input type="date" name="date_to">
        <button type="submit" name="add_promo">Add Promo</button>
    </form>

    <h2>Promo Files</h2>
    <table border="1">
        <tr>
            <th>Promo ID</th>
            <th>Promo Name</th>
            <th>Date From</th>
            <th>Date To</th>
            <th>Actions</th>
        </tr>
        <?php
        // Fetch and display promo files
        $sql = "SELECT * FROM `promofiles` ORDER BY `promofiles`.`date_from` DESC";
        $result = $conn->query($sql);
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['promo_id'] . "</td>";
            echo "<td>" . $row['promo_name'] . "</td>";
            echo "<td>" . $row['date_from'] . "</td>";
            echo "<td>" . $row['date_to'] . "</td>";
            echo "<td>
					<a href='promotions.php?delete_promo=" . $row['promo_id'] . "'>Delete</a>
					<a href='promofile.php?id=" . $row['promo_id'] . "'>More</a>
				  </td>";
            echo "</tr>";
        }
        ?>
    </table>
</body>
</html>
