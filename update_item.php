<?php

include 'dbconnections.php';

if(isset($_POST['update_items'])) {

  foreach($_POST['new_location'] as $item_id => $new_location) {

    // Update inventory table
    $sql = "UPDATE inventory 
            SET current_location = :location, 
                updated_date = NOW()
            WHERE item_id = :id";
    
    $stmt = $conn->prepare($sql);
    
    $stmt->bindParam(':id', $item_id);
    $stmt->bindParam(':location', $new_location);
    
    $stmt->execute();

    // Insert log entry  
    $sql = "INSERT INTO item_log (item_id, location, date) 
            VALUES (:id, :location, NOW())";
            
    $stmt = $conn->prepare($sql);

    $stmt->bindParam(':id', $item_id);
    $stmt->bindParam(':location', $new_location);

    $stmt->execute();

  }
  
  header("Location: inventory.php");
  exit();

}

?>
