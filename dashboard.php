<link rel="stylesheet" href="main.css">
<?php

// Include database connection
include 'dbconnections.php'; 

?>

<?php

// Connect to database

// Handle add one product
if(isset($_POST['add_one'])) {

  $product_code = $_POST['product_code'];
  $product_name = $_POST['product_name'];
  $catalogue_name = $_POST['catalogue_name'];
  $category = $_POST['category'];
  $supplier_name = $_POST['supplier_name'];
  
  $stmt = $conn->prepare("INSERT INTO products (product_code, product_name, catalogue_name, category, supplier_name, last_update) 
                          VALUES (?, ?, ?, ?, ?, NOW())");
  $stmt->execute([$product_code, $product_name, $catalogue_name, $category, $supplier_name]);
}

// Handle CSV import
if(isset($_POST['import_csv'])) {
    $file = fopen($_FILES['csv_file']['tmp_name'], 'r');

    while (($data = fgetcsv($file)) !== false) {
        $product_code = $data[0];
        $product_name = $data[1];
        $catalogue_name = $data[2];
        $category = $data[3];
        $supplier_name = $data[4];

        // Check if product_code already exists
        $checkExistsStmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE product_code = ?");
        $checkExistsStmt->execute([$product_code]);
        $count = $checkExistsStmt->fetchColumn();

        if ($count == 0) {
            // If product_code doesn't exist, insert the record
            $stmt = $conn->prepare("INSERT INTO products (product_code, product_name, catalogue_name, category, supplier_name, last_update) 
                                VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$product_code, $product_name, $catalogue_name, $category, $supplier_name]);
        }
        // Otherwise, skip inserting (duplicate found)
    }

    fclose($file);
}

// Handle image upload
if(isset($_POST['upload_image'])) {

  $fileName = $_FILES['product_image']['name'];
  $fileTmpName = $_FILES['product_image']['tmp_name'];
  $fileType = $_FILES['product_image']['type'];
  
  $productId = $_POST['product_code'];
  
  $fileExt = explode('.', $fileName);
  $fileActualExt = strtolower(end($fileExt));
  
  $allowed = array('jpg', 'jpeg', 'png', 'webp');
  
  if(in_array($fileActualExt, $allowed)) {
    $imageUrl = 'images/'.$fileName;
    move_uploaded_file($fileTmpName, $imageUrl);
    
    $sql = "UPDATE products SET image_url='$imageUrl' WHERE product_code=$productId";
    $conn->exec($sql);
  }
}

// Download image
if(isset($_POST['download_image'])) {

  $product_code = $_POST['product_code'];

  // Use prepared statements to avoid SQL injection
  $stmt = $conn->prepare("SELECT product_name, image_url FROM products WHERE product_code = ?");
  $stmt->execute([$product_code]);
  $product = $stmt->fetch();

  if ($product) {
    $product_name = $product['product_name'];
    $image_path = $product['image_url'];

    // Get file extension 
    $image_parts = explode(".", $image_path);
    $image_ext = strtolower(end($image_parts));

    // Determine MIME type based on file extension
    switch ($image_ext) {
      case 'jpg':
      case 'jpeg':
        $mime_type = 'image/jpeg';
        break;
      case 'png':
        $mime_type = 'image/png';
        break;
      case 'webp':
        $mime_type = 'image/webp';
        break;
      default:
        // Unsupported file type
        die("Unsupported file type.");
    }

    // Set headers
    header('Content-Type: '.$mime_type); 
    header('Content-Disposition: attachment; filename="'.urlencode($product_name).'.'.$image_ext.'"');

    // Output image
    readfile($image_path);
    exit;
  } else {
    echo "Product not found.";
  }
}






?>

<!DOCTYPE html>
<html>
<head>
    <title>Product Dashboard</title>
    <link rel="stylesheet" href="main.css"> 
	<script>
    document.addEventListener('DOMContentLoaded', function () {
        var images = document.querySelectorAll('.popup-container');

        images.forEach(function (image) {
            var popup = image.querySelector('.popup-content');

            image.addEventListener('mouseover', function () {
                popup.style.visibility = 'visible';
                popup.style.opacity = '1';
            });

            image.addEventListener('mouseout', function () {
                popup.style.visibility = 'hidden';
                popup.style.opacity = '0';
            });
        });
    });
</script>
   <style>
        body {
            font-family: monospace;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        .header-container {
            position: relative;
        }

        header {
            position: relative;
            width: 100%;
            background-color: #fff;
            z-index: 1000;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
            transition: position 0.3s ease; /* Add transition for smooth effect */
        }

        .fixed-header {
            position: fixed;
            top: 0;
        }

        nav ul {
            list-style: none;
            padding: 0;
            text-align: center;
        }

        nav li {
            display: inline-block;
            margin: 0 10px;
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        nav li:hover {
            background-color: #6ca0fa;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }

        nav a {
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var header = document.querySelector('header');
            var headerContainer = document.querySelector('.header-container');
            var headerHeight = header.offsetHeight;

            window.addEventListener('scroll', function() {
                if (window.scrollY > headerHeight) {
                    headerContainer.classList.add('fixed-header');
                } else {
                    headerContainer.classList.remove('fixed-header');
                }
            });
        });
    </script>
</head>
<body>
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