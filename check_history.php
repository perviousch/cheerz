<link rel="stylesheet" href="main.css">
<?php

include 'dbconnections.php'; 
// Get item ID 
$item_id = $_GET['id'];

// First, fetch the item description and name
$item_sql = "SELECT description, item_name FROM `cheersproducts`.`inventory` WHERE `item_id` = :id";
$item_stmt = $conn->prepare($item_sql);
$item_stmt->bindParam(':id', $item_id);
$item_stmt->execute();
$item_info = $item_stmt->fetch(PDO::FETCH_ASSOC);

// Now fetch the location history
$sql = "SELECT * FROM item_log WHERE item_id = :id ";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $item_id);
$stmt->execute();

include('header.php');

// Display the new header with description and item name
echo "<h2>Location History for Item: \"{$item_info['description']}\" {$item_info['item_name']}</h2>"; 

// Table header
echo "<table border='1'>";
echo "<tr>";
echo "<th>Log ID</th>";
echo "<th>Item ID</th>";  
echo "<th>Date</th>";
echo "<th>Location</th>";
echo "</tr>";

// Output data rows
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  echo "<tr>";
  echo "<td>".$row['log_id']."</td>";
  echo "<td>".$row['item_id']."</td>";
  echo "<td>".$row['date']."</td>";
  echo "<td>".$row['location']."</td>";
  echo "</tr>";
}

echo "</table>";

?>