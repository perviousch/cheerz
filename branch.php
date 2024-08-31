<?php
include 'dbconnections.php'; // Include your database connection file

session_start(); // Start the session

// Check if the user is already logged in
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $branch_id = $_SESSION['branch_id'];
} else {
    // Handle login form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Retrieve the user's branch_id based on the username
        $stmt = $conn->prepare("SELECT branch_id FROM user_login WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $branch_id = $result['branch_id'];

            // Verify the password (you should use a secure hashing algorithm like bcrypt)
            $stored_password = $conn->prepare("SELECT password FROM user_login WHERE username = ?");
            $stored_password->execute([$username]);
            $stored_password = $stored_password->fetchColumn();

            if (password_verify($password, $stored_password)) {
                // Password is correct, store the username and branch_id in the session
                $_SESSION['username'] = $username;
                $_SESSION['branch_id'] = $branch_id;
            } else {
                $login_error = "Invalid username or password";
            }
        } else {
            $login_error = "Invalid username or password";
        }
    }
}

// Retrieve dispatches for the logged-in user's branch
if (isset($_SESSION['branch_id'])) {
    $stmt = $conn->prepare("SELECT d.dispatch_date, d.notes, dd.quantity, m.name AS material_name
                            FROM dispatches d
                            JOIN dispatch_details dd ON d.id = dd.dispatch_id
                            JOIN materials m ON dd.material_id = m.id
                            WHERE dd.branch_id = ?
                            ORDER BY d.dispatch_date DESC");
    $stmt->execute([$_SESSION['branch_id']]);
    $dispatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Branch Login</title>
	<link rel="stylesheet" href="main.css">
</head>
<body>
    <?php if (isset($login_error)): ?>
        <p><?php echo $login_error; ?></p>
    <?php endif; ?>

    <?php if (!isset($_SESSION['username'])): ?>
        <h2>Branch Login</h2>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <br>
            <input type="submit" value="Login">
        </form>
    <?php else: ?>
        <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
        <h3>Dispatches for your branch:</h3>
        <?php if (!empty($dispatches)): ?>
            <table>
                <tr>
                    <th>Dispatch Date</th>
                    <th>Notes</th>
                    <th>Material</th>
                    <th>Quantity</th>
                </tr>
                <?php foreach ($dispatches as $dispatch): ?>
                    <tr>
                        <td><?php echo $dispatch['dispatch_date']; ?></td>
                        <td><?php echo $dispatch['notes']; ?></td>
                        <td><?php echo $dispatch['material_name']; ?></td>
                        <td><?php echo $dispatch['quantity']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No dispatches found for your branch.</p>
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    <?php endif; ?>
</body>
</html>