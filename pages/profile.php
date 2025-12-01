<?php
session_start();
require_once '../php/config/db_connect.php';

// Require authentication
if (empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$conn = getDBConnection();
if (!$conn) {
    die('Database connection error');
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf_token = $_SESSION['csrf_token'];

// Fetch username
$username = 'User';
if ($stmt = $conn->prepare('SELECT username FROM users WHERE id = ?')) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($username);
    $stmt->fetch();
    $stmt->close();
}

// Total watchlist items
$total_items = 0;
if ($stmt = $conn->prepare('SELECT COUNT(*) FROM watchlist WHERE user_id = ?')) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($total_items);
    $stmt->fetch();
    $stmt->close();
}

// Total movies
$total_movies = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) FROM watchlist WHERE user_id = ? AND media_type = 'movie'")) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($total_movies);
    $stmt->fetch();
    $stmt->close();
}

// Total TV shows
$total_tv = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) FROM watchlist WHERE user_id = ? AND media_type = 'tv'")) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($total_tv);
    $stmt->fetch();
    $stmt->close();
}

// Average rating (only finished)
$avg_rating = null;
if ($stmt = $conn->prepare("SELECT AVG(user_rating) FROM watchlist WHERE user_id = ? AND status = 'finished' AND user_rating IS NOT NULL")) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($avg_rating);
    $stmt->fetch();
    $stmt->close();
}

// Currently watching list
$currently_watching = [];
if ($stmt = $conn->prepare('
    SELECT id, title, poster_path, media_type, tmdb_movie_id, added_at 
    FROM watchlist 
    WHERE user_id = ? AND status = "watching"
    ORDER BY updated_at DESC
')) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        $currently_watching = $res->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}

// Recent activity (last 10)
$recent_activity = [];
if ($stmt = $conn->prepare('
    SELECT action_type, description, created_at 
    FROM activity_log 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
')) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        $recent_activity = $res->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo htmlspecialchars($username); ?> — Profile • WatchVault</title>
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/pages/dashboard.css">
  <style>
    .profile-container { max-width: 980px; margin: 28px auto; padding: 18px; }
    .profile-header { display:flex; align-items:center; justify-content:space-between; gap:16px; }
    .profile-stats { display:flex; gap:18px; margin-top:16px; flex-wrap:wrap; }
    .stat { background:#fff; padding:12px 16px; border-radius:8px; box-shadow:0 1px 4px rgba(0,0,0,0.06); min-width:120px; }
    .currently-watching-list, .recent-activity { margin-top:22px; }
    .cw-item { display:flex; gap:12px; align-items:center; padding:8px 0; border-bottom:1px solid #eee; }
    .cw-item img { width:56px; height:80px; object-fit:cover; border-radius:4px; }
    .delete-user { background:#ff4d4f; color:#fff; border:none; padding:8px 12px; border-radius:6px; cursor:pointer; }
    .muted { color:#6b7280; font-size:0.95rem; }
    ul.activity { padding-left:0; list-style:none; margin:0; }
    ul.activity li { padding:8px 0; border-bottom:1px solid #f1f1f1; }
  </style>
</head>
<body>
  <div class="page-container">
    <header class="site-header">
      <div class="header-container">
        <div class="logo-container">
          <div class="logo-icon"><img src="../assets/watchvault-logo.svg" alt=""></div>
          <span class="logo-text">WatchVault</span>
        </div>
        <div class="user-container">
          <a href="dashboard.php" class="header-link">Dashboard</a>
        </div>
      </div>
    </header>

    <main class="profile-container">
      <div class="profile-header">
        <div>
          <h1><?php echo htmlspecialchars($username); ?></h1>
          <p class="muted">Member since your account creation</p>
        </div>
        <div>
          <form id="delete-user-form" method="POST" action="api/delete_user.php" onsubmit="return confirmDelete();">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <button type="submit" class="delete-user">Delete account</button>
          </form>
        </div>
      </div>

      <div class="profile-stats">
        <div class="stat">
          <strong>Total Items</strong>
          <div><?php echo (int)$total_items; ?></div>
        </div>
        <div class="stat">
          <strong>Movies</strong>
          <div><?php echo (int)$total_movies; ?></div>
        </div>
        <div class="stat">
          <strong>TV Shows</strong>
          <div><?php echo (int)$total_tv; ?></div>
        </div>
        <div class="stat">
          <strong>Average Rating</strong>
          <div><?php echo $avg_rating !== null ? number_format((float)$avg_rating, 1) : '—'; ?></div>
        </div>
      </div>

      <section class="currently-watching-list">
        <h2>Currently Watching</h2>
        <?php if (empty($currently_watching)): ?>
          <p class="muted">No items currently marked as watching.</p>
        <?php else: ?>
          <?php foreach ($currently_watching as $item): ?>
            <div class="cw-item">
              <?php if (!empty($item['poster_path'])): ?>
                <img src="<?php echo htmlspecialchars($item['poster_path']); ?>" alt="">
              <?php else: ?>
                <div style="width:56px;height:80px;background:#ddd;border-radius:4px;"></div>
              <?php endif; ?>
              <div>
                <div><strong><?php echo htmlspecialchars($item['title']); ?></strong></div>
                <div class="muted">
                  Type: <?php echo htmlspecialchars($item['media_type']); ?>
                  • Added: <?php echo htmlspecialchars(date('M j, Y', strtotime($item['added_at']))); ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>

      <section class="recent-activity">
        <h2>Recent Activity</h2>
        <?php if (empty($recent_activity)): ?>
          <p class="muted">No recent activity.</p>
        <?php else: ?>
          <ul class="activity">
            <?php foreach ($recent_activity as $act): ?>
              <li>
                <div><strong><?php echo htmlspecialchars($act['action_type']); ?></strong> — <?php echo htmlspecialchars($act['description']); ?></div>
                <div class="muted"><?php echo htmlspecialchars(date('M j, Y \a\t g:ia', strtotime($act['created_at']))); ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <script>
    function confirmDelete() {
      return confirm('Delete your account? This action is permanent and will remove your watchlist and activity.');
    }
  </script>
</body>
</html>