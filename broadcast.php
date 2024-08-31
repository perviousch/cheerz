<?php
include 'dbconnections.php'; // Include your database connection file

session_start(); // Start the session

// Handle adding a new user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $branch_id = $_POST['branch_id'];

    // Hash the password (using bcrypt in this example)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new user into the user_login table
    $stmt = $conn->prepare("INSERT INTO user_login (username, password, branch_id) VALUES (?, ?, ?)");
    $stmt->execute([$username, $hashed_password, $branch_id]);

    echo "User added successfully!";
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Broadcast</title>
	<link rel="stylesheet" href="main.css">
    <style>
        /* Your CSS styles go here */
    </style>
</head>
<body>
    <div class="header-container">
        <header>
            <?php include('header.php'); ?>
        </header>
    </div>

    <h2>Add New User</h2>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <label for="branch_id">Branch:</label>
        <select id="branch_id" name="branch_id" required>
            <?php
            $stmt = $conn->query("SELECT id, name FROM branches");
            $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($branches as $branch) {
                echo "<option value='" . $branch['id'] . "'>" . $branch['name'] . "</option>";
            }
            ?>
        </select>
        <br>
        <input type="submit" name="add_user" value="Add User">
    </form>

    <!-- Your broadcast functionality goes here -->

    <div class="footer-container">
        <footer>
            <?php include('footer.php'); ?>
        </footer>
    </div>
</body>
</html>