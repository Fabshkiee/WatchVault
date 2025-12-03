<?php
session_start();
require_once '../php/config/db_connect.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $conn = getDBConnection();

        if ($conn) {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = "Username already taken.";
            } else {
                // Insert new user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                
                // Must use "ss" because there are TWO values
                $stmt->bind_param("ss", $username, $hashedPassword);

                if ($stmt->execute()) {
                    $success = "Account created successfully!";
                    header("Location: login.php?registered=1");
                    exit();
                } else {
                    $error = "Error creating account. Try again.";
                }
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
  
  <style> 
    .error-msg {
        color: #ff4d4d;
        background: rgba(255, 77, 77, 0.1);
        border: 1px solid rgba(255, 77, 77, 0.3);
        padding: 10px;
        border-radius: 8px;
        font-size: 13px;
        margin-bottom: 15px;
    }
    
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      background: #0d071b;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      overflow: hidden;
      position: relative;
      color: white;
    }

    .film-container {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      z-index: 1;
      pointer-events: none;
    }

    .film-strip {
      position: absolute;
      width: 400px;
      height: 400%;
      background: url(figmastrips.png) repeat-y; 
      background-size: 100% auto;
      opacity: 0.5;
      animation: scrollFilm 30s linear infinite;
    }

    .film-strip.left { left: 1%; }
    .film-strip.right { right: 1%; animation-delay: -15s; }

    @keyframes scrollFilm {
      0%   { transform: translateY(0); }
      100% { transform: translateY(-80%); }
    }

    .overlay {
      position: absolute;
      inset: 0;
      background: radial-gradient(rgba(127, 63, 255, 0.2), #0d071b 87%);
      z-index: 2;
      pointer-events: none;
    }
    
    .login-box {
      position: relative;
      z-index: 3;
      width: 380px;
      padding: 40px 35px;
      background: rgba(20, 14, 40, 0.75);
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(153, 75, 255, 0.25);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(165, 70, 255, 0.2);
      text-align: center;
      animation: fadeIn 1.2s ease-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(30px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .logo {
      width: 62px;
      height: 62px;
      background: linear-gradient(135deg, #a546ff, #c88cff);
      display: flex;
      justify-content: center;
      align-items: center;
      border-radius: 14px;
      margin: 0 auto 20px;
      font-size: 34px;
      box-shadow: 0 4px 15px rgba(165, 70, 255, 0.4);
    }

    h1 { color: #e0ccff; margin: 0 0 8px; font-size: 28px; font-weight: 700; letter-spacing: -0.5px; }
    .subtitle { color: #b8a7d1; margin-bottom: 32px; font-size: 14px; }
    .username, .password { text-align: left; color: #ddd; font-size: 13px; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    
    input {
      width: 100%;
      padding: 12px 14px;
      border-radius: 10px;
      border: 1px solid rgba(165, 70, 255, 0.3);
      outline: none;
      margin-bottom: 18px;
      background: rgba(13, 7, 27, 0.6);
      color: #fff;
      font-size: 15px;
      transition: all 0.3s ease;
    }

    input:focus {
      border-color: #a546ff;
      box-shadow: 0 0 0 3px rgba(165, 70, 255, 0.2);
      background: rgba(13, 7, 27, 0.9);
    }

    .btn {
      width: 100%;
      padding: 14px;
      border: none;
      border-radius: 10px;
      background: linear-gradient(90deg, #a546ff, #c88cff);
      color: white;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      margin-top: 10px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(165, 70, 255, 0.4);
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(184, 90, 255, 0.5);
    }

    .signup { margin-top: 20px; color: #b8a7d1; font-size: 13.5px; }
    .signup a { color: #e0ccff; text-decoration: none; font-weight: 600; }
    .signup a:hover { text-decoration: underline; }
  </style>
</head>
<body>

  <div class="film-container">
    <div class="film-strip left"></div>
    <div class="film-strip right"></div>
  </div>

  <div class="overlay"></div>

  <div class="login-box">
    <div class="logo">
      <img src="../assets/watchvault-logo.svg" alt="">
    </div>
    <h1>WatchVault</h1>
    <div class="subtitle">Your personal media tracker</div>

    <?php if(!empty($error)): ?>
        <div class="error-msg"><?php echo $error; ?></div>
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

        <button type="submit" class="btn">Create Account</button>
    </form>

    <div class="signup">
    Already have an account? <a href="login.php">Sign in</a>
    </div>

  </div>

</body>
</html>