<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cheers Hypermarket</title>
    <link rel="stylesheet" href="main.css">
    <style>
        nav ul {
            list-style: none;
            padding: 0;
            text-align: center;
        }

        nav li {
            display: inline-block; /* Change from block to inline-block */
            margin: 10px 10px; /* Adjust margin */
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
</head>
<body>
    <header>
        <h1></h1>
        <nav>
            <ul>
			    <li><a href="index.php">Dashboard</a></li>
                <li><a href="dashboard.php">Products</a></li>
                <li><a href="promotions.php">Promotions</a></li>
				<li><a href="inventory.php">Inventory</a></li>
                <li><a href="tools.php">Tools</a></li>
            </ul>
        </nav>
    </header>
