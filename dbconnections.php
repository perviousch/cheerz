<?php
$host = 'sql3.freemysqlhosting.net';
$dbname = 'sql3728775';
$username = 'sql3728775';
$password = 'zdxyhRP89d';
$port = 3306;

try {
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully";
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>


