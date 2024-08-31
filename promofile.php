<?php
include 'dbconnections.php';


// Fetch promo name based on ID
$promo_id = $_GET['id'];
$stmt = $conn->prepare("SELECT promo_name FROM promofiles WHERE promo_id = ?");
$stmt->execute([$promo_id]);
$promo_data = $stmt->fetch(PDO::FETCH_ASSOC);
$promo_name = $promo_data['promo_name'];

if (isset($_POST['import_csv'])) {
    if ($_FILES['csv_file']['size'] > 0) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');

        while (($data = fgetcsv($file)) !== false) {
            $product_code = $data[0];
            $product_name = $data[1];
            $catalogue_name = $data[2];
            $promo_id = $_GET['id']; // assuming $promo_id is obtained through URL parameters

            try {
                $stmt = $conn->prepare("INSERT INTO promorun (promo_id, product_code, product_name, catalogue_name) 
                                        VALUES (?, ?, ?, ?)");
                $stmt->execute([$promo_id, $product_code, $product_name, $catalogue_name]);
            } catch (PDOException $e) {
                if ($e->getCode() === '23000' && $e->errorInfo[1] === 1062) {
                    // Duplicate entry error, handle it as per your requirement (skipping the current iteration)
                    continue;
                } else {
                    throw $e; // Rethrow the exception if it's not a duplicate entry error
                }
            }
        }

        fclose($file);
    }
}

$sql = "SELECT products.product_code, products.product_name, products.catalogue_name, 
               products.category, products.supplier_name, products.last_update, products.image_url
        FROM products
        INNER JOIN promorun ON products.product_code = promorun.product_code
        WHERE promorun.promo_id = :promo_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':promo_id', $_GET['id']);
$stmt->execute();
?>
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
<body>
    <h1><?= $promo_name ?></h1>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="csv_file">
    <button type="submit" name="import_csv">Import CSV  FORMAT> |Product code|Product name|Catalogue name|Category|Supplier name|</button>
</form>

<table border="1">
    <tr>
        <th>Product Code</th>
        <th>Product Name</th>
        <th>Catalogue Name</th>
        <th>Category</th>
        <th>Supplier</th>
        <th>Last Updated</th>
        <th>Image</th>
    </tr>

    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) : ?>
        <tr>
            <td><?= $row['product_code'] ?></td>
            <td><?= $row['product_name'] ?></td>
            <td><?= $row['catalogue_name'] ?></td>
            <td><?= $row['category'] ?></td>
            <td><?= $row['supplier_name'] ?></td>
            <td><?= $row['last_update'] ?></td>
            <td>
                <?php if ($row['image_url']) : ?>
                    <img src="<?= $row['image_url'] ?>" height="50" width="50">
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>

</table>

</body>
</html>