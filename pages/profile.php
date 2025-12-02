<?php
session_start();
require_once '../php/config/db_connect.php';

// Require authentication - fix: should redirect to login, not dashboard
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$conn = getDBConnection();
if (!$conn) {
    die('<div style="padding: 20px; background: #ffebee; color: #c62828; border-radius: 5px; margin: 20px;">Database connection error. Please check your configuration.</div>');
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: landingPage.html');
    exit;
}

// Handle delete account
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_account'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
    
    // Start transaction
    $conn->begin_transaction();
    try {
        // Delete from activity_log
        $stmt = $conn->prepare('DELETE FROM activity_log WHERE user_id = ?');
        if (!$stmt) {
            throw new Exception("Failed to prepare activity log delete: " . $conn->error);
        }
        $stmt->bind_param('i', $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete activity log: " . $stmt->error);
        }
        $stmt->close();
        
        // Delete from watchlist
        $stmt = $conn->prepare('DELETE FROM watchlist WHERE user_id = ?');
        if (!$stmt) {
            throw new Exception("Failed to prepare watchlist delete: " . $conn->error);
        }
        $stmt->bind_param('i', $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete watchlist: " . $stmt->error);
        }
        $stmt->close();
        
        // Delete from users
        $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
        if (!$stmt) {
            throw new Exception("Failed to prepare user delete: " . $conn->error);
        }
        $stmt->bind_param('i', $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete user: " . $stmt->error);
        }
        $stmt->close();
        
        $conn->commit();
        
        session_destroy();
        header('Location: landingPage.html?deleted=1');
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Failed to delete account: " . $e->getMessage();
    }
}

// Initialize variables
$username = 'User';
$created_at = null;
$total_items = 0;
$total_movies = 0;
$total_tv = 0;
$avg_rating = null;
$currently_watching_count = 0;
$finished_count = 0;
$want_to_watch_count = 0;
$recent_activity = [];

// Fetch user data
$stmt = $conn->prepare('SELECT username, created_at FROM users WHERE id = ?');
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    if ($stmt->execute()) {
        $stmt->bind_result($username, $created_at);
        $stmt->fetch();
    } else {
        error_log("Failed to fetch user data: " . $stmt->error);
    }
    $stmt->close();
}

// Total watchlist items
$stmt = $conn->prepare('SELECT COUNT(*) FROM watchlist WHERE user_id = ?');
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    if ($stmt->execute()) {
        $stmt->bind_result($total_items);
        $stmt->fetch();
    }
    $stmt->close();
}

// Total movies
$stmt = $conn->prepare("SELECT COUNT(*) FROM watchlist WHERE user_id = ? AND media_type = 'movie'");
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    if ($stmt->execute()) {
        $stmt->bind_result($total_movies);
        $stmt->fetch();
    }
    $stmt->close();
}

// Total TV shows
$stmt = $conn->prepare("SELECT COUNT(*) FROM watchlist WHERE user_id = ? AND media_type = 'tv'");
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    if ($stmt->execute()) {
        $stmt->bind_result($total_tv);
        $stmt->fetch();
    }
    $stmt->close();
}

// Average rating (only finished)
$stmt = $conn->prepare("SELECT AVG(user_rating) FROM watchlist WHERE user_id = ? AND status = 'finished' AND user_rating IS NOT NULL");
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    if ($stmt->execute()) {
        $stmt->bind_result($avg_rating);
        $stmt->fetch();
    }
    $stmt->close();
}

// Status counts
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM watchlist WHERE user_id = ? GROUP BY status");
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            switch($row['status']) {
                case 'watching':
                    $currently_watching_count = $row['count'];
                    break;
                case 'finished':
                    $finished_count = $row['count'];
                    break;
                case 'wantToWatch':
                    $want_to_watch_count = $row['count'];
                    break;
            }
        }
    }
    $stmt->close();
}

// Check if activity_log table exists before querying
$table_check = $conn->query("SHOW TABLES LIKE 'activity_log'");
if ($table_check && $table_check->num_rows > 0) {
    // Recent activity (last 10)
    $stmt = $conn->prepare('
        SELECT action_type, description, created_at 
        FROM activity_log 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ');
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($res) {
                $recent_activity = $res->fetch_all(MYSQLI_ASSOC);
            }
        }
        $stmt->close();
    }
} else {
    // Table doesn't exist, show message
    $activity_table_missing = true;
}

// Function to format activity messages
function formatActivityMessage($action_type) {
    $messages = [
        'add_to_watchlist' => 'Added to Watchlist',
        'update_status' => 'Status Updated',
        'delete_from_watchlist' => 'Removed from Library',
        'rate_item' => 'Rated Item',
        'review_item' => 'Added Review'
    ];
    return $messages[$action_type] ?? ucfirst(str_replace('_', ' ', $action_type));
}

// Function to format activity description
function formatActivityDescription($description) {
    if (strlen($description) > 60) {
        return htmlspecialchars(substr($description, 0, 57) . '...');
    }
    return htmlspecialchars($description);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Profile • WatchVault</title>
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/pages/dashboard.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .profile-container { 
        max-width: 980px; 
        margin: 28px auto; 
        padding: 18px;
    }
    
    /* Profile Header with Enhanced Design */
    .profile-header-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 40px 30px;
        color: white;
        position: relative;
        overflow: hidden;
        margin-bottom: 30px;
        box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
    }
    
    .profile-header-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
        background-size: cover;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        font-weight: 600;
        border: 4px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(10px);
    }
    
    .user-name {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
        font-family: 'Poppins', sans-serif;
        text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .user-meta {
        display: flex;
        gap: 20px;
        margin-bottom: 25px;
        font-size: 14px;
        opacity: 0.9;
    }
    
    .user-meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .header-actions {
        display: flex;
        gap: 12px;
        position: relative;
        z-index: 2;
    }
    
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 25px 20px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border: 1px solid rgba(0,0,0,0.05);
        position: relative;
        overflow: hidden;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.12);
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea20, #764ba220);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
    }
    
    .stat-icon svg {
        width: 24px;
        height: 24px;
        color: #667eea;
    }
    
    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #333;
        margin-bottom: 5px;
        line-height: 1;
    }
    
    .stat-label {
        font-size: 14px;
        color: #666;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Activity Section */
    .recent-activity { 
        background: white; 
        padding: 30px; 
        border-radius: 20px; 
        box-shadow: 0 8px 25px rgba(0,0,0,0.08); 
        margin-bottom: 30px; 
        border: 1px solid rgba(0,0,0,0.05);
    }
    
    .activity-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .activity-header h2 {
        margin: 0;
        color: #333;
        font-size: 22px;
        font-weight: 700;
    }
    
    ul.activity { 
        padding-left:0; 
        list-style:none; 
        margin:0; 
    }
    
    .activity-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        padding: 18px;
        border-radius: 12px;
        margin-bottom: 12px;
        background: #f8f9ff;
        border-left: 4px solid #667eea;
        transition: all 0.3s ease;
    }
    
    .activity-item:hover {
        background: #f0f2ff;
        transform: translateX(5px);
    }
    
    .activity-icon {
        width: 40px;
        height: 40px;
        min-width: 40px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }
    
    .activity-icon svg {
        width: 20px;
        height: 20px;
    }
    
    .activity-content {
        flex: 1;
    }
    
    .activity-type {
        font-weight: 600;
        color: #333;
        font-size: 15px;
        margin-bottom: 4px;
    }
    
    .activity-description {
        font-size: 13px;
        color: #666;
        line-height: 1.5;
        margin-bottom: 6px;
    }
    
    .activity-time {
        font-size: 12px;
        color: #888;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    /* Buttons */
    .logout-btn, .delete-btn { 
        padding: 12px 24px; 
        border-radius: 12px; 
        cursor: pointer; 
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
        font-weight: 600;
        border: none;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        z-index: 1;
    }
    
    .logout-btn {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(10px);
    }
    
    .logout-btn:hover { 
        background: rgba(255, 255, 255, 0.3); 
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(255, 255, 255, 0.2);
    }
    
    .delete-btn {
        background: rgba(255, 77, 79, 0.9);
        color: white;
        border: 2px solid rgba(255, 77, 79, 0.3);
        backdrop-filter: blur(10px);
    }
    
    .delete-btn:hover { 
        background: rgba(255, 77, 79, 1); 
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(255, 77, 79, 0.2);
    }
    
    /* Messages */
    .error-message { 
        background: #fff2f0; 
        border: 1px solid #ffccc7; 
        color: #cf1322; 
        padding: 12px 16px; 
        border-radius: 8px; 
        margin-bottom: 20px; 
        font-size: 14px;
    }
    
    .success-message { 
        background: #f6ffed; 
        border: 1px solid #b7eb8f; 
        color: #52c41a; 
        padding: 12px 16px; 
        border-radius: 8px; 
        margin-bottom: 20px; 
        font-size: 14px;
    }
    
    .info-message {
        background: #e6f7ff;
        border: 1px solid #91d5ff;
        color: #096dd9;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
    }
    
    /* Empty States */
    .no-items { 
        text-align: center; 
        padding: 60px 20px; 
        color: #999; 
        font-style: italic;
        background: #fafafa;
        border-radius: 12px;
        margin: 20px 0;
    }
    
    .no-items h3 {
        color: #666;
        margin-bottom: 10px;
        font-weight: 500;
    }
    
    .dashboard-link {
        display: inline-block;
        margin-top: 15px;
        padding: 10px 20px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        text-decoration: none;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .dashboard-link:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .profile-header-card {
            padding: 30px 20px;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            font-size: 32px;
        }
        
        .user-name {
            font-size: 28px;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .header-actions {
            flex-direction: column;
            width: 100%;
        }
        
        .logout-btn, .delete-btn {
            width: 100%;
            text-align: center;
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .user-meta {
            flex-direction: column;
            gap: 10px;
        }
    }
  </style>
</head>
<body>
  <div class="page-container">
    <header class="site-header">
      <div class="header-container">
        <div class="logo-container">
          <div class="logo-icon"><img src="../assets/watchvault-logo.svg" alt="WatchVault"></div>
          <span class="logo-text">WatchVault</span>
        </div>
        <div class="user-container">
          <a href="dashboard.php" class="user-avatar">
          <svg width="5" height="5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </a>
        </div>
      </div>
    </header>

    <main class="profile-container">
      <?php if(isset($error)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      
      <?php if(isset($_GET['deleted'])): ?>
        <div class="success-message">Your account has been deleted successfully.</div>
      <?php endif; ?>
      
      <!-- Enhanced Profile Header -->
      <div class="profile-header-card">
        <div class="user-avatar">
          <?php echo strtoupper(substr($username, 0, 1)); ?>
        </div>
        
        <h1 class="user-name"><?php echo htmlspecialchars($username); ?></h1>
        
        <div class="user-meta">
          <div class="user-meta-item">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM2 2a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1H2z"/>
              <path d="M2.5 4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H3a.5.5 0 0 1-.5-.5V4zM11 7.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm-5 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1z"/>
            </svg>
            <span>Member since <?php echo $created_at ? htmlspecialchars(date('F Y', strtotime($created_at))) : 'Recently'; ?></span>
          </div>
          
          <div class="user-meta-item">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm1-8a1 1 0 0 0-1-1H4a1 1 0 1 0 0 2h4a1 1 0 0 0 1-1z"/>
            </svg>
            <span>User ID: #<?php echo $user_id; ?></span>
          </div>
        </div>
        
        <div class="header-actions">
          <form method="GET" action="" style="margin:0; flex:1;">
            <button type="submit" name="logout" value="1" class="logout-btn">
              <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 8px;">
                <path fill-rule="evenodd" d="M6 12.5a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2A1.5 1.5 0 0 1 6.5 2h8A1.5 1.5 0 0 1 16 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 5 12.5v-2a.5.5 0 0 1 1 0v2z"/>
                <path fill-rule="evenodd" d="M.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L1.707 7.5H10.5a.5.5 0 0 1 0 1H1.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3z"/>
              </svg>
              Logout
            </button>
          </form>
          
          <form id="delete-user-form" method="POST" onsubmit="return confirmDelete();" style="margin:0; flex:1;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="delete_account" value="1">
            <button type="submit" class="delete-btn">
              <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 8px;">
                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
              </svg>
              Delete Account
            </button>
          </form>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
          <div class="stat-value"><?php echo (int)$total_items; ?></div>
          <div class="stat-label">Total Items</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
            </svg>
          </div>
          <div class="stat-value"><?php echo (int)$total_movies; ?></div>
          <div class="stat-label">Movies</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </div>
          <div class="stat-value"><?php echo (int)$total_tv; ?></div>
          <div class="stat-label">TV Shows</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </svg>
          </div>
          <div class="stat-value"><?php echo $avg_rating !== null ? number_format((float)$avg_rating, 1) . '/10' : '—'; ?></div>
          <div class="stat-label">Avg Rating</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
          </div>
          <div class="stat-value"><?php echo (int)$currently_watching_count; ?></div>
          <div class="stat-label">Watching Now</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="stat-value"><?php echo (int)$finished_count; ?></div>
          <div class="stat-label">Completed</div>
        </div>
      </div>

      <!-- Recent Activity -->
      <section class="recent-activity">
        <div class="activity-header">
          <h2>Recent Activity</h2>
          <a href="dashboard.php" class="dashboard-link">Go to Dashboard</a>
        </div>
        
        <?php if(isset($activity_table_missing)): ?>
          <div class="info-message">
            Activity logging is not enabled. The activity_log table does not exist in the database.
          </div>
        <?php elseif (empty($recent_activity)): ?>
          <div class="no-items">
            <h3>No activity yet</h3>
            <p>Start building your watchlist to see your activity here.</p>
            <a href="dashboard.php" class="dashboard-link">Start Adding Items</a>
          </div>
        <?php else: ?>
          <ul class="activity">
            <?php foreach ($recent_activity as $act): ?>
              <?php 
                // Get icon based on action type
                $icon = '';
                switch($act['action_type']) {
                    case 'add_to_watchlist':
                        $icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />';
                        break;
                    case 'update_status':
                        $icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />';
                        break;
                    case 'delete_from_watchlist':
                        $icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />';
                        break;
                    case 'rate_item':
                        $icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />';
                        break;
                    default:
                        $icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />';
                }
              ?>
              
              <li class="activity-item">
                <div class="activity-icon">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                    <?php echo $icon; ?>
                  </svg>
                </div>
                <div class="activity-content">
                  <div class="activity-type">
                    <?php echo formatActivityMessage($act['action_type']); ?>
                  </div>
                  <div class="activity-description">
                    <?php echo formatActivityDescription($act['description']); ?>
                  </div>
                  <div class="activity-time">
                    <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 4px;">
                      <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                      <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                    </svg>
                    <?php echo htmlspecialchars(date('M j, Y \a\t g:i A', strtotime($act['created_at']))); ?>
                  </div>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <script>
    function confirmDelete() {
      return confirm('Are you sure you want to delete your account? This action is permanent and will remove all your watchlist items and activity history.');
    }
    
    // Animate stat cards on scroll
    const observerOptions = {
      threshold: 0.1
    };
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, observerOptions);
    
    // Add animation to stat cards
    document.querySelectorAll('.stat-card').forEach((card, index) => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(20px)';
      card.style.transition = `all 0.5s ease ${index * 0.1}s`;
      observer.observe(card);
    });
    
    // Add animation to activity items
    document.querySelectorAll('.activity-item').forEach((item, index) => {
      item.style.opacity = '0';
      item.style.transform = 'translateX(-20px)';
      item.style.transition = `all 0.4s ease ${index * 0.05}s`;
      setTimeout(() => {
        item.style.opacity = '1';
        item.style.transform = 'translateX(0)';
      }, 100 + (index * 50));
    });
  </script>
</body>
</html>