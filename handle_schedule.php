<?php
include 'dbconnections.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get parameters from URL
    $type = $_GET['type'];
    $name = $_GET['name'];
    $dateFrom = $_GET['date_from'];
    $dateTo = $_GET['date_to'];

    // Handle different types of schedules
    if ($type === 'promo') {
        // Generate a random promo_id between 100000 to 999999
        $promo_id = mt_rand(100000, 999999);
        
        // Insert data into promofiles table
        $stmt = $conn->prepare("INSERT INTO promofiles (promo_id, promo_name, date_from, date_to) VALUES (?, ?, ?, ?)");
        $stmt->execute([$promo_id, $name, $dateFrom, $dateTo]);
    } elseif ($type === 'campaign') {
        // Generate a random campaign_id between 100000 to 999999
        $campaign_id = mt_rand(100000, 999999);
        
        // Insert data into campaigns table
        $stmt = $conn->prepare("INSERT INTO campaigns (campaign_id, name, date_from, date_to) VALUES (?, ?, ?, ?)");
        $stmt->execute([$campaign_id, $name, $dateFrom, $dateTo]);
    } elseif ($type === 'post') {
        // Generate a random post_id between 10000000 to 99999999
        $post_id = mt_rand(10000000, 99999999);
        
        // Insert data into posts table
        $stmt = $conn->prepare("INSERT INTO posts (post_id, name, date_from, date_to) VALUES (?, ?, ?, ?)");
        $stmt->execute([$post_id, $name, $dateFrom, $dateTo]);
    }

    // Redirect back to index.php or any other desired page
    header('Location: index.php');
    exit();
} else {
    // Handle invalid request method
    echo 'Invalid request method.';
}
?>
