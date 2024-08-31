<?php
// Improved Dashboard with Secure Practices and Enhanced Functionalities

include 'dbconnections.php'; // Secure database connection

function handleAddProduct($conn) {
    $product_code = $_POST['product_code'];
    $product_name = $_POST['product_name'];
    $catalogue_name = $_POST['catalogue_name'];
    $category = $_POST['category'];
    $supplier_name = $_POST['supplier_name'];
    
    $stmt = $conn->prepare("INSERT INTO products (product_code, product_name, catalogue_name, category, supplier_name, last_update) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$product_code, $product_name, $catalogue_name, $category, $supplier_name]);
}

function handleImportCSV($conn) {
    $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
    
    while (($data = fgetcsv($file)) !== false) {
        list($product_code, $product_name, $catalogue_name, $category, $supplier_name) = $data;
        
        $checkExistsStmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE product_code = ?");
        $checkExistsStmt->execute([$product_code]);
        
        if ($checkExistsStmt->fetchColumn() == 0) {
            $stmt = $conn->prepare("INSERT INTO products (product_code, product_name, catalogue_name, category, supplier_name, last_update) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$product_code, $product_name, $catalogue_name, $category, $supplier_name]);
        }
    }
    
    fclose($file);
}

function handleUploadImage($conn) {
    // Assuming a column 'image_path' exists in your 'products' table to store the image file path.
    // Ensure to validate and sanitize file inputs in real-world applications.
    if (!empty($_FILES['product_image']['name'])) {
        $fileName = basename($_FILES['product_image']['name']);
        $fileTmpName = $_FILES['product_image']['tmp_name'];
        $path = "uploads/images/" . $fileName; // Ensure this directory exists and is writable
        
        if (move_uploaded_file($fileTmpName, $path)) {
            // Update product with image path
            $product_code = $_POST['product_code']; // Assuming you're using product_code to identify which product to update
            $stmt = $conn->prepare("UPDATE products SET image_path = ? WHERE product_code = ?");
            $stmt->execute([$path, $product_code]);
        }
    }
}

function handleDownloadImage($conn) {
    if (!empty($_POST['product_code'])) {
        $stmt = $conn->prepare("SELECT image_path FROM products WHERE product_code = ?");
        $stmt->execute([$_POST['product_code']]);
        $image_path = $stmt->fetchColumn();
        
        if ($image_path && file_exists($image_path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($image_path) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($image_path));
            readfile($image_path);
            exit;
        }
    }
}

// Handle Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_one'])) {
        handleAddProduct($conn);
    } elseif (isset($_POST['import_csv'])) {
        handleImportCSV($conn);
    } elseif (isset($_POST['upload_image'])) {
        handleUploadImage($conn);
    } elseif (isset($_POST['download_image'])) {
        handleDownloadImage($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="main.css">
</head>
<body>
<!-- Your HTML form content goes here -->
    <div class="header-container">
        <header>
            <?php include('header.php'); ?>
        </header>
    </div>


<form method="post" action="">
<table>
  <tr>
    <td><h4>Product code</h4></td>
    <td><input type="text" name="product_code"></td> 
  </tr>
  
  <tr>
    <td><h4>Product name</h4></td>
    <td><input type="text" name="product_name"></td>
  </tr>

  <tr>
    <td><h4>Catalogue name</h4></td>
    <td><input type="text" name="catalogue_name"></td>
  </tr>

  <tr>
    <td><h4>Category</h4></td>
    <td><input type="text" name="category"></td>
  </tr>

  <tr>
    <td><h4>Supplier name</h4></td>
    <td><input type="text" name="supplier_name"></td>
  </tr>

  <tr>
    <td colspan="2"><button type="submit" name="add_one">Add Product</button></td>
  </tr>

</table>
</form>

<form method="post" enctype="multipart/form-data" action="">
  <input type="file" name="csv_file">
  <button type="submit" name="import_csv">Import CSV</button>
</form>

<form method="get">
  <input type="text" name="search">
  <button type="submit">Search</button> 
  <button type="submit">View All</button> 
</form>

<table border="1">
  <tr>
    <th>Product Code</th>
    <th>Product Name</th>
	<th>catalogue name</th>
    <th>Category</th>
    <th>Supplier</th>
    <th>Last Updated</th>
    <th>Image</th>
    <th>Actions</th>
  </tr>

<?php

// Fetch and display products
if(isset($_GET['search'])) {
  $search = $_GET['search'];
  $sql = "SELECT * FROM products WHERE product_code LIKE ? OR product_name LIKE ? OR category LIKE ? OR supplier_name LIKE ?";
  $stmt = $conn->prepare($sql);
  $stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%"]);
} else {
  $sql = "SELECT * FROM products ORDER BY `products`.`last_update` DESC";
  $stmt = $conn->query($sql);  
}

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

  echo "<tr>";
  echo "<td>" . $row['product_code'] . "</td>";
  echo "<td>" . $row['product_name'] . "</td>";
  echo "<td>" . $row['catalogue_name'] . "</td>"; 
  echo "<td>" . $row['category'] . "</td>";
  echo "<td>" . $row['supplier_name'] . "</td>";
  echo "<td>" . $row['last_update'] . "</td>";
  
  echo "<td class='popup-container'>";
if($row['image_url']) {
    echo "<img height='50' src='". $row['image_url'] ."'>";
    echo "<div class='popup-content'>";
    echo "<img src='". $row['image_url'] ."' style='max-width: 100%;'>";
    echo "</div>";
    echo "<form method='post' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='product_code' value='". $row['product_code'] ."'>";
    echo "<input type='hidden' name='image_url' value='". $row['image_url'] ."'>";
    echo "<input type='file' name='product_image'>";
    echo "<button type='submit' name='download_image'>Download Image</button>";
    echo "<button type='submit' name='upload_image'>Upload</button>";
    echo "</form>";
  } else {
    echo "<form method='post' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='product_code' value='". $row['product_code'] ."'>";
    echo "<input type='file' name='product_image'>";
    echo "<button type='submit' name='upload_image'>Upload</button>";
	
    echo "</form>";
  }
  echo "</td>";

  echo "<td>";
  echo "<button name='update' value='". $row['product_code'] ."'>Update</button> ";
  echo "<button name='delete' value='". $row['product_code'] ."'>Delete</button>";
  echo "</td>";

  echo "</tr>";

}

?>

</table>
</div>
</body>
</html>
