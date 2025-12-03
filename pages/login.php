<?php
session_start();
require_once '../php/config/db_connect.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';
$action_mode = 'login'; // Default view

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = getDBConnection();
    
    // Get inputs
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $action   = $_POST['action'] ?? 'login';
    $action_mode = $action; // Keep user on the same screen if error occurs

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } elseif (!$conn) {
        $error = "Database connection error.";
    } else {
        
        // ==========================================
        // REGISTER LOGIC
        // ==========================================
        if ($action === 'register') {
            // Check if username exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = "Username already taken.";
            } else {
                // Create account
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insertStmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $insertStmt->bind_param("ss", $username, $hashedPassword);
                
                if ($insertStmt->execute()) {
                    $success = "Account created successfully! Please log in with your credentials.";
                    // Clear the action mode so form resets, and clear username
                    $action_mode = 'login';
                    $username = '';
                    $insertStmt->close();
                } else {
                    $error = "Error creating account. Please try again.";
                    $insertStmt->close();
                }
            }
            $stmt->close();
        } 
        
        // ==========================================
        // LOGIN LOGIC
        // ==========================================
        else { 
            $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (password_verify($password, $user['password'])) {
                        // Login Success
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
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
            $stmt->close();
        }
        
        closeDBConnection($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>WatchVault</title>
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
    
    <h1 id="page-title">WatchVault</h1>
    <div class="subtitle" id="page-subtitle">Your personal media tracker</div>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" id="auth-form">
        <input type="hidden" name="action" id="action-input" value="<?php echo htmlspecialchars($action_mode); ?>">

        <div class="username">
            <label class="field">Username</label>
        </div>
        <input type="text" name="username" id="username" placeholder="John Doe" autocomplete="username" required value="<?php echo (isset($_POST['username']) && $action_mode === 'login') ? htmlspecialchars($_POST['username']) : ''; ?>">

        <div class="password">
            <label class="field">Password</label>
        </div>
        <input type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>

        <button type="submit" class="btn" id="submit-btn">
            <?php echo ($action_mode === 'register') ? 'Create Account' : 'Sign in'; ?>
        </button>
    </form>

    <div class="signup">
        <span id="toggle-text">
            <?php echo ($action_mode === 'register') ? 'Already have an account?' : "Don't have an account?"; ?>
        </span> 
        <a href="#" id="toggle-link">
            <?php echo ($action_mode === 'register') ? 'Sign in →' : 'Create one →'; ?>
        </a>
    </div>

  </div>

  <!-- Popup Notifications Container -->
  <div id="popup-container"></div>

  <script>
      const toggleLink = document.getElementById('toggle-link');
      const actionInput = document.getElementById('action-input');
      const submitBtn = document.getElementById('submit-btn');
      const pageTitle = document.getElementById('page-title');
      const pageSubtitle = document.getElementById('page-subtitle');
      const toggleText = document.getElementById('toggle-text');
      const usernameInput = document.getElementById('username');
      const passwordInput = document.querySelector('input[name="password"]');
      const authForm = document.getElementById('auth-form');
      const popupContainer = document.getElementById('popup-container');

      // State to track current mode (defaults to PHP state)
      let isRegisterMode = actionInput.value === 'register';

      // Popup Notification Function
      function showPopup(message, type = 'info') {
          const popup = document.createElement('div');
          popup.className = `popup popup-${type}`;
          popup.textContent = message;
          popupContainer.appendChild(popup);

          // Trigger animation
          setTimeout(() => popup.classList.add('show'), 10);

          // Auto-dismiss after 3 seconds
          const dismissTimer = setTimeout(() => {
              popup.classList.remove('show');
              setTimeout(() => popup.remove(), 300);
          }, 3000);

          // Store dismiss timer on popup so it can be cleared
          popup.dismissTimer = dismissTimer;
          return popup;
      }
      // Function to clear all existing popups
      function clearAllPopups() {
          const popups = popupContainer.querySelectorAll('.popup');
          popups.forEach(popup => {
              clearTimeout(popup.dismissTimer);
              popup.classList.remove('show');
              setTimeout(() => popup.remove(), 300);
          });
      }
      // Show error/success popups from PHP on page load
      window.addEventListener('load', () => {
          passwordInput.value = '';
          
          <?php if(!empty($error)): ?>
              showPopup("<?php echo htmlspecialchars($error); ?>", 'error');
          <?php endif; ?>

          <?php if(!empty($success)): ?>
              showPopup("<?php echo htmlspecialchars($success); ?>", 'success');
          <?php endif; ?>
      });

      toggleLink.addEventListener('click', (e) => {
          e.preventDefault();
          isRegisterMode = !isRegisterMode;

          // Clear form fields
          usernameInput.value = '';
          passwordInput.value = '';

         // Clear existing popups
         clearAllPopups();

          if (isRegisterMode) {
              // Switch to Register View
              actionInput.value = 'register';
              pageTitle.textContent = 'Join WatchVault';
              pageSubtitle.textContent = 'Start tracking your journey today';
              submitBtn.textContent = 'Create Account';
              toggleText.textContent = 'Already have an account?';
              toggleLink.textContent = 'Sign in →';
              showPopup('Switching to Register', 'info');
          } else {
              // Switch to Login View
              actionInput.value = 'login';
              pageTitle.textContent = 'WatchVault';
              pageSubtitle.textContent = 'Your personal media tracker';
              submitBtn.textContent = 'Sign in';
              toggleText.textContent = "Don't have an account?";
              toggleLink.textContent = 'Create one →';
              showPopup('Switching to Login', 'info');
          }
      });
  </script>

</body>
</html>