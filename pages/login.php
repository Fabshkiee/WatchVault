<?php
session_start();
require_once '../php/config/db_connect.php';

$error = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $conn = getDBConnection();
        
        if ($conn) {
            // Prepare statement to prevent SQL Injection
            $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            if ($stmt && $stmt->execute()) {
                $result = $stmt->get_result();

                if ($result && $result->num_rows === 1) {
                    $user = $result->fetch_assoc();

                    // Verify hashed password
                    if (password_verify($password, $user['password'])) {
                        // Password is correct, start session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];

                        // Redirect to dashboard
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $error = "Invalid password.";
                    }
                } else {
                    $error = "User not found.";
                }
            } else {
                $error = "Query error. Please try again.";
            }

            if ($stmt) { $stmt->close(); }
            closeDBConnection($conn);
        } else {
            $error = "Database connection error.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>WatchVault Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/global.css" />
    <link rel="stylesheet" href="../css/pages/login.css" />
</head>
<body>

  <div class="film-container">
    <div class="film-strip left"></div>
    <div class="film-strip right"></div>
  </div>

  <div class="overlay"></div>

    <div class="login-box glass-card">
    <div class="logo">
      <img src="../assets/watchvault-logo.svg" alt="">
    </div>
    <h1>WatchVault</h1>
    <div class="subtitle">Your personal media tracker</div>

    <?php if(!empty($error)): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
    <div class="success-msg" style="
         color:#4dff4d;
         background:rgba(77,255,77,0.1);
         border:1px solid rgba(77,255,77,0.3);
         padding:10px;
         border-radius:8px;
         margin-bottom:15px;
         font-size:13px;">
        Account created! You can now log in.
    </div>
    <?php endif; ?>


    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        <div class="username">
            <label class="field">Username</label>
        </div>
        <input type="text" name="username" placeholder="e.g. nathsarr boto" autocomplete="username" required>

        <div class="password">
            <label class="field">Password</label>
        </div>
        <input type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>

        <button type="submit" class="btn">Sign in</button>
    </form>

    <div class="signup">
    Don't have an account? <a href="register.php">Create one →</a>
    </div>

  </div>

</body>
</html>