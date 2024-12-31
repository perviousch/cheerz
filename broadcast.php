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
  body {
  font-family: 'Montserrat', sans-serif;
  background: #1e293b;
  color: #f8fafc;
}

.app {
  min-width: 100vw;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
}

header { 
  display: flex;
  flex-direction: column;
  align-items: center;
  margin: 1rem;
  text-align: center;
  
  h1 {
    font-weight: 600;
    font-size: 2rem;
    margin-bottom: 0.5rem;
    
    @media (min-width: 768px) {
      font-size: 3rem;
    }
  }
  
  p {
    color: #94a3b8;
    margin-bottom: 0.5rem;
  }
  
  a {
    color: #7393c1;
  }
}

.tag-list {
  width: 100%;
  max-width: 90vw;
  display: flex;
  flex-shrink: 0;
  flex-direction: column;
  gap: 1rem 0;
  position: relative;
  padding: 1.5rem 0;
  overflow: hidden;
}

.loop-slider {
  .inner {
    display: flex;
    width: fit-content;
    animation-name: loop;
    animation-timing-function: linear;
    animation-iteration-count: infinite;
    animation-direction: var(--direction);
    animation-duration: var(--duration);
  }
}

.tag {
  display: flex;
  align-items: center;
  gap: 0 0.2rem;
  color: #e2e8f0;
  font-size: 0.9rem;
  background-color: #334155;
  border-radius: 0.4rem;
  padding: 0.7rem 1rem;
  margin-right: 1rem; // Must used margin-right instead of gap for the loop to be smooth
  box-shadow: 
    0 0.1rem 0.2rem rgb(0 0 0 / 20%),
    0 0.1rem 0.5rem rgb(0 0 0 / 30%),
    0 0.2rem 1.5rem rgb(0 0 0 / 40%);
  
  span {
    font-size: 1.2rem;
    color: #64748b;
  }
}

.fade {
  pointer-events: none;
  background: linear-gradient(90deg, #1e293b, transparent 30%, transparent 70%, #1e293b);
  position: absolute;
  inset: 0;
}

@keyframes loop {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-50%);
  }
}
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

<div class="app">
  <div class="tag-list">
    <div class="loop-slider" style="--duration:15951ms; --direction:normal;">
      <div class="inner">
        <div class="tag"><span>#</span> JavaScript</div>
        <div class="tag"><span>#</span> webdev</div>
        <div class="tag"><span>#</span> Typescript</div>
        <div class="tag"><span>#</span> Next.js</div>
        <div class="tag"><span>#</span> UI/UX</div>
        <!-- duplicated content -->
        <div class="tag"><span>#</span> JavaScript</div>
        <div class="tag"><span>#</span> webdev</div>
        <div class="tag"><span>#</span> Typescript</div>
        <div class="tag"><span>#</span> Next.js</div>
        <div class="tag"><span>#</span> UI/UX</div>
      </div>
    </div>
    <div class="loop-slider" style="--duration:19260ms; --direction:reverse;">
      <div class="inner">
        <div class="tag"><span>#</span> webdev</div>
        <div class="tag"><span>#</span> Gatsby</div>
        <div class="tag"><span>#</span> JavaScript</div>
        <div class="tag"><span>#</span> Tailwind</div>
        <div class="tag"><span>#</span> Typescript</div>
        <!-- duplicated content -->
        <div class="tag"><span>#</span> webdev</div>
        <div class="tag"><span>#</span> Gatsby</div>
        <div class="tag"><span>#</span> JavaScript</div>
        <div class="tag"><span>#</span> Tailwind</div>
        <div class="tag"><span>#</span> Typescript</div>
      </div>
    </div>
    <div class="loop-slider" style="--duration:10449ms; --direction:normal;">
      <div class="inner">
        <div class="tag"><span>#</span> animation</div>
        <div class="tag"><span>#</span> Tailwind</div>
        <div class="tag"><span>#</span> React</div>
        <div class="tag"><span>#</span> SVG</div>
        <div class="tag"><span>#</span> HTML</div>
        <!-- duplicated content -->
        <div class="tag"><span>#</span> animation</div>
        <div class="tag"><span>#</span> Tailwind</div>
        <div class="tag"><span>#</span> React</div>
        <div class="tag"><span>#</span> SVG</div>
        <div class="tag"><span>#</span> HTML</div>
      </div>
    </div>
    <div class="loop-slider" style="--duration:16638ms; --direction:reverse;">
      <div class="inner">
        <div class="tag"><span>#</span> Gatsby</div>
        <div class="tag"><span>#</span> HTML</div>
        <div class="tag"><span>#</span> CSS</div>
        <div class="tag"><span>#</span> React</div>
        <div class="tag"><span>#</span> Next.js</div>
        <!-- duplicated content -->
        <div class="tag"><span>#</span> Gatsby</div>
        <div class="tag"><span>#</span> HTML</div>
        <div class="tag"><span>#</span> CSS</div>
        <div class="tag"><span>#</span> React</div>
        <div class="tag"><span>#</span> Next.js</div>
      </div>
    </div>
    <div class="loop-slider" style="--duration:15936ms; --direction:normal;">
      <div class="inner">
        <div class="tag"><span>#</span> Next.js</div>
        <div class="tag"><span>#</span> React</div>
        <div class="tag"><span>#</span> webdev</div>
        <div class="tag"><span>#</span> Typescript</div>
        <div class="tag"><span>#</span> Gatsby</div>
        <!-- duplicated content -->
        <div class="tag"><span>#</span> Next.js</div>
        <div class="tag"><span>#</span> React</div>
        <div class="tag"><span>#</span> webdev</div>
        <div class="tag"><span>#</span> Typescript</div>
        <div class="tag"><span>#</span> Gatsby</div>
      </div>
    </div>
    <div class="fade"></div>
  </div>
</div>

    <div class="footer-container">
        <footer>
            <?php include('footer.php'); ?>
        </footer>
    </div>
</body>
</html>