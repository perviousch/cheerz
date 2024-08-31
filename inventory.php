<?php

// Connect to database
include 'dbconnections.php';

// Handle add item form submission
if(isset($_POST['add_item'])) {

  // Get form data
  $item_id = $_POST['item_id'];
  $item_name = $_POST['item_name'];
  $description = $_POST['description'];
  $storage_site = $_POST['storage_site'];
  $current_location = $_POST['current_location'];

  // Insert into inventory table
  $sql = "INSERT INTO inventory 
          (item_id, item_name, description, storage_site, current_location, added_date, updated_date)
          VALUES (?, ?, ?, ?, ?, NOW(), NOW())";

  $stmt = $conn->prepare($sql);
  $stmt->execute([$item_id, $item_name, $description, $storage_site, $current_location]);

  // Insert log entry 
  $sql = "INSERT INTO item_log
          (item_id, location, date)
          VALUES (?, ?, NOW())";

  $stmt = $conn->prepare($sql);
  $stmt->execute([$item_id, $current_location]);

}

// Get inventory items
$sql = "SELECT * FROM inventory";
$inventory_stmt = $conn->query($sql);

?>

<!DOCTYPE html>
<html>

<head>
  <title>Inventory Management</title>
  
  <link rel="stylesheet" type="text/css" href="main.css">
  
  <script src="script.js"></script>

</head>

<body>

  <div class="header-container">
    <header>
      <?php include('header.php'); ?> 
    </header>
  </div>

  <h2>Add Item</h2>

  <form method="post">
    <label for="item_id">Item ID:</label>
    <input type="text" id="item_id" name="item_id">

    <label for="item_name">Item Name:</label>
    <input type="text" id="item_name" name="item_name">

    <label for="description">Description:</label>
    <input type="text" id="description" name="description">

    <label for="storage_site">Storage Site:</label>
    <input type="text" id="storage_site" name="storage_site">

    <label for="current_location">Current Location:</label>
    <input type="text" id="current_location" name="current_location">

    <button type="submit" name="add_item">Add Item</button>
  </form>

  <h2>Inventory</h2>

  <form method="post" action="update_item.php">

    <table border="1">
      <tr>
        <th>Item ID</th>
        <th>Item Name</th>
        <th>Description</th>
        <th>Storage Site</th>
        <th>Current Location</th>
        <th>Added Date</th>
        <th>Updated Date</th>
        <th>Actions</th>
      </tr>

      <?php while($row = $inventory_stmt->fetch(PDO::FETCH_ASSOC)): ?>

        <tr>
          <td> HO-MK<?php echo $row['item_id']; ?></td>
          <td><?php echo $row['item_name']; ?></td>
          <td><?php echo $row['description']; ?></td>
          <td><?php echo $row['storage_site']; ?></td>
        
          <td>
            <input type="text" name="new_location[<?php echo $row['item_id']; ?>]"
                  value="<?php echo $row['current_location']; ?>">
          </td>
        
          <td><?php echo $row['added_date']; ?></td>
          <td><?php echo $row['updated_date']; ?></td>

          <td>
             <a href="check_history.php?id=<?php echo $row['item_id']; ?>">
               View History
             </a>
          </td>

        </tr>

      <?php endwhile; ?>

    </table>

    <button type="submit" name="update_items">Update Locations</button>

  </form>

 <h2>Search Location History by Date</h2>
<form method="post" action="">
    <label for="search_date">Enter Date:</label>
    <input type="date" id="search_date" name="search_date">
    <button type="submit" name="search_history">Search</button>
</form>

<?php
if(isset($_POST['search_history'])) {
    $search_date = $_POST['search_date'];
    $stmt = $conn->prepare("SELECT * FROM item_log WHERE date = ?");
    $stmt->execute([$search_date]);

    echo "<h2>Location History for Date: " . $search_date . "</h2>";
    echo "<table border='1'>
            <tr>
                <th>Log ID</th>
                <th>Product Code</th>
                <th>Location</th>
                <th>Date</th>
            </tr>";

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['log_id'] . "</td>";
        echo "<td>" . $row['item_id'] . "</td>";
        echo "<td>" . $row['location'] . "</td>";
        echo "<td>" . $row['date'] . "</td>";
        echo "</tr>";
    }

    echo "</table>";
}
?>

</body>

</html>