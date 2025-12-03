<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - WatchVault</title>
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/pages/dashboard.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
  <div class="page-container">
    <!-- Header -->
    <header class="site-header">
      <div class="header-container">
        <div class="logo-container">
          <div class="logo-icon">
            <img src="../assets/watchvault-logo.svg" alt="">
          </div>
          <span class="logo-text">WatchVault</span>
        </div>
        <div class="user-container">
          <a href="profile.php" class="user-avatar">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </a>
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="dashboard-hero">
      <h1 class="dashboard-hero-title">All your favorites in<br />one place.</h1>
      
      <form class="dashboard-search-container" onsubmit="handleDashboardSearch(event)">
        <svg class="dashboard-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          type="text"
          id="dashboard-main-search"
          name="q"
          placeholder="Search for animes, movies, TV shows to add to your library..."
          class="dashboard-search-input"
        />
      </form>
      
      <script>
        function handleDashboardSearch(e) {
          e.preventDefault();
          const query = document.getElementById('dashboard-main-search').value.trim();
          if (query) {
            const activeFilter = document.querySelector('.dashboard-filter-button.active')?.dataset.filter || 'all';
            window.location.href = `search.php?q=${encodeURIComponent(query)}&category=${activeFilter}`;
          }
        }
      </script>

      <div class="dashboard-filter-group">
        <div class="active-pill"></div>
        <button class="filter-button dashboard-filter-button active" data-filter="all">All</button>
        <button class="filter-button dashboard-filter-button" data-filter="movie">Movies</button>
        <button class="filter-button dashboard-filter-button" data-filter="tv">TV Shows</button>
      </div>
    </section>

    <!-- Status Filters -->
    <section class="dashboard-status-filters">
      <div class="dashboard-status-buttons">
        <div class="active-pill"></div>
        <button class="dashboard-status-button active" data-status="all">All Statuses</button>
        <button class="dashboard-status-button" data-status="watching">Currently Watching</button>
        <button class="dashboard-status-button" data-status="wantToWatch">Want to Watch</button>
        <button class="dashboard-status-button" data-status="finished">Finished</button>
      </div>

      <div class="dashboard-watchlist-search">
        <svg class="dashboard-watchlist-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          type="text"
          id="dashboard-watchlist-search"
          placeholder="Search inside your watchlist..."
          class="dashboard-watchlist-search-input"
        />
      </div>
    </section>

    <!-- Content Sections -->
    <main id="dashboard-content-sections" class="dashboard-content">
      <!-- Populated by JavaScript -->
    </main>
  </div>

  <div id="media-details-container"></div>
  <div id="toast-container"></div>
</body>

<script>
  // Simple moving pill animation
  function movePill(button, pill) {
    if (!button || !pill) return;
    const group = button.parentElement;
    const groupRect = group.getBoundingClientRect();
    const buttonRect = button.getBoundingClientRect();
    
    pill.style.width = buttonRect.width + 'px';
    pill.style.left = (buttonRect.left - groupRect.left) + 'px';
  }
  
  // Initialize and animate filter buttons
  document.querySelectorAll('.dashboard-filter-button').forEach(btn => {
    btn.addEventListener('click', async () => {
      categoryFilter = btn.dataset.filter;
      document.querySelectorAll('.dashboard-filter-button').forEach(b => {
        b.classList.toggle('active', b.dataset.filter === categoryFilter);
      });
      
      const pill = document.querySelector('.dashboard-filter-group .active-pill');
      movePill(btn, pill);
    });
  });
  
  // Initialize and animate status buttons
  document.querySelectorAll('.dashboard-status-button').forEach(btn => {
    btn.addEventListener('click', async () => {
      statusFilter = btn.dataset.status;
      document.querySelectorAll('.dashboard-status-button').forEach(b => {
        b.classList.toggle('active', b.dataset.status === statusFilter);
      });
      
      const pill = document.querySelector('.dashboard-status-buttons .active-pill');
      movePill(btn, pill);
    });
  });
  
  // Initialize pill positions on load
  setTimeout(() => {
    const activeFilterBtn = document.querySelector('.dashboard-filter-button.active');
    const filterPill = document.querySelector('.dashboard-filter-group .active-pill');
    if (activeFilterBtn && filterPill) movePill(activeFilterBtn, filterPill);
    
    const activeStatusBtn = document.querySelector('.dashboard-status-button.active');
    const statusPill = document.querySelector('.dashboard-status-buttons .active-pill');
    if (activeStatusBtn && statusPill) movePill(activeStatusBtn, statusPill);
  }, 100);
</script>


</html>