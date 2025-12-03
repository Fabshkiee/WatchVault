<?php
session_start();

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - WatchVault</title>
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/pages/dashboard.css">
  <link rel="stylesheet" href="../css/pages/media_details.css">
  <!-- New Lightbar CSS -->
  <link rel="stylesheet" href="../css/lightbar.css"> 
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body dir="ltr"> <!-- Added dir="ltr" as required by lightbar CSS -->
  <div class="page-container">
    <!-- Header -->
    <header class="site-header">
      <div class="header-container">
        <div class="logo-container">
          <div class="logo-icon">
            <img src="../assets/watchvault-logo.svg" alt="">
          </div>
          <span class="logo-text" class="logo-text">WatchVault</span>
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

    <!-- NEW LIGHTBAR COMPONENT -->
    <div class="lightbar">
        <canvas class="particles"></canvas>
        <div class="lightbar-visual"></div>
    </div>

    <!-- Hero Section -->
    <section class="dashboard-hero">
      <h1 class="dashboard-hero-title">All your favorites in<br />one place.</h1>
      
      <div id="search-placeholder"></div>

      <!-- Redirects to search.php -->
      <form class="dashboard-search-container" id="main-search-form" onsubmit="handleNewSearch(event)">
        <svg class="dashboard-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          type="text"
          id="add-media-search"
          name="q"
          placeholder="Search for animes, movies, TV shows to add to your library... "
          class="dashboard-search-input"
        />
      </form>
      
      <!-- Category Filters -->
      <div class="dashboard-filter-group">
        <div class="active-pill"></div>
        <button class="filter-button dashboard-filter-button active" data-filter="all">All</button>
        <button class="filter-button dashboard-filter-button" data-filter="movie">Movies</button>
        <button class="filter-button dashboard-filter-button" data-filter="tv">TV Shows</button>
      </div>
    </section>

    <!-- Status & Local Search Section -->
    <section class="dashboard-status-filters">
      <!-- Status Filters -->
      <div class="dashboard-status-buttons">
        <div class="active-pill"></div>
        <button class="dashboard-status-button active" data-status="all">All Statuses</button>
        <button class="dashboard-status-button" data-status="watching">Currently Watching</button>
        <button class="dashboard-status-button" data-status="wantToWatch">Want to Watch</button>
        <button class="dashboard-status-button" data-status="finished">Finished</button>
      </div>

      <!-- Local Watchlist Search -->
      <div class="dashboard-watchlist-search">
        <svg class="dashboard-watchlist-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          type="text"
          id="dashboard-watchlist-search"
          placeholder="Filter inside your watchlist..."
          class="dashboard-watchlist-search-input"
        />
      </div>
    </section>

    <!-- Content Sections -->
    <main id="dashboard-content-sections" class="dashboard-content">
      <!-- Populated by JavaScript -->
    </main>
  </div>

  <!-- Injected Defs for Half-Star Gradient -->
  <svg style="width:0; height:0; position:absolute;" aria-hidden="true" focusable="false">
    <defs>
      <linearGradient id="half-star-gradient" x1="0%" y1="0%" x2="100%" y2="0%">
        <stop offset="50%" stop-color="#a546ff" />
        <stop offset="50%" stop-color="rgba(255, 255, 255, 0.2)" />
      </linearGradient>
    </defs>
  </svg>

  <div id="media-details-container"></div>
  <div id="toast-container"></div>

  <script src="../js/utils/helpers.js"></script>
  <script src="../js/components/stickySearch.js"></script>
  <script src="../js/components/watchlistManager.js"></script>
  <script src="../js/components/mediaDetailsModal.js"></script>
  <!-- New Lightbar JS -->
  <script src="../js/lightbar.js"></script>
</body>
</html>