<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$initial_query = isset($_GET['q']) ? $_GET['q'] : '';
$initial_category = isset($_GET['category']) ? $_GET['category'] : 'all';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search - WatchVault</title>
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/pages/dashboard.css">
  <link rel="stylesheet" href="../css/pages/media_details.css">
  <link rel="stylesheet" href="../css/pages/search.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body dir="ltr">
  <div class="page-container">
    <!-- Header -->
    <header class="site-header">
      <div class="header-container">
        <div class="logo-container">
            <a href="dashboard.php" style="text-decoration:none; display:flex; align-items:center; gap:0.75rem;">
                <div class="logo-icon"><img src="../assets/watchvault-logo.svg" alt=""></div>
                <span class="logo-text">WatchVault</span>
            </a>
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

    <!-- Search Hero -->
    <section class="dashboard-hero search-page-hero">
      <a href="dashboard.php" class="back-link">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 5px;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Back to Library
      </a>
      
      <h1 class="dashboard-hero-title">Search Results</h1>
      <p class="search-results-subtitle">Showing results for "<?php echo htmlspecialchars($initial_query); ?>"</p>
      
      <div id="search-placeholder" style="display: none; height: 3.5rem; margin: 2rem auto 0;"></div>

      <form class="dashboard-search-container" id="main-search-form" onsubmit="handleNewSearch(event)">
        <svg class="dashboard-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          type="text"
          id="add-media-search"
          name="q"
          value="<?php echo htmlspecialchars($initial_query); ?>"
          placeholder="Search for movies, TV shows, animes..."
          class="dashboard-search-input"
          autofocus
        />
      </form>
      
      <div class="dashboard-filter-group">
        <div class="active-pill"></div>
        <button class="filter-button dashboard-filter-button <?php echo $initial_category === 'all' ? 'active' : ''; ?>" data-filter="all">All</button>
        <button class="filter-button dashboard-filter-button <?php echo $initial_category === 'movie' ? 'active' : ''; ?>" data-filter="movie">Movies</button>
        <button class="filter-button dashboard-filter-button <?php echo $initial_category === 'tv' ? 'active' : ''; ?>" data-filter="tv">TV Shows</button>
      </div>
    </section>

    <!-- Results -->
    <main id="search-results-container" class="dashboard-content">
        <!-- Results populated here -->
    </main>
  </div>
  
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

  <!-- SCRIPTS -->
  <script src="../js/utils/helpers.js"></script>
  <script src="../js/components/stickySearch.js"></script>
  <script src="../js/components/mediaDetailsModal.js"></script>
  <script>
    // Initialize state
    let currentCategory = '<?php echo $initial_category; ?>';
    
    function movePill(button, pill) {
        if (!button || !pill) return;
        const group = button.parentElement;
        const groupRect = group.getBoundingClientRect();
        const buttonRect = button.getBoundingClientRect();
        pill.style.width = buttonRect.width + 'px';
        pill.style.left = (buttonRect.left - groupRect.left) + 'px';
    }

    // Initialize Pill
    setTimeout(() => {
        const activeFilterBtn = document.querySelector('.dashboard-filter-button.active');
        const filterPill = document.querySelector('.dashboard-filter-group .active-pill');
        if (activeFilterBtn && filterPill) movePill(activeFilterBtn, filterPill);
    }, 100);

    document.addEventListener('DOMContentLoaded', () => {
        const query = document.getElementById('add-media-search').value.trim();
        if(query) {
            performSearch(query, currentCategory);
        }
    });

    function handleNewSearch(e) {
        e.preventDefault();
        const query = document.getElementById('add-media-search').value.trim();
        if(query) {
            const newUrl = `search.php?q=${encodeURIComponent(query)}&category=${currentCategory}`;
            window.history.pushState({path: newUrl}, '', newUrl);
            performSearch(query, currentCategory);
        }
    }

    document.querySelectorAll('.dashboard-filter-button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.dashboard-filter-button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            movePill(btn, document.querySelector('.dashboard-filter-group .active-pill'));
            
            currentCategory = btn.dataset.filter;
            const query = document.getElementById('add-media-search').value.trim();
            if(query) performSearch(query, currentCategory);
        });
    });

    async function performSearch(query, category) {
        const container = document.getElementById('search-results-container');
        container.innerHTML = '<div style="text-align:center; padding:40px; color:var(--secondary);">Searching...</div>';

        try {
            const url = `../php/api/search_tmdb.php?q=${encodeURIComponent(query)}&category=${category}`;
            const response = await fetch(url);
            const data = await response.json();

            if (!data || data.length === 0) {
                container.innerHTML = `
                    <div class="watchlist-empty">
                        <p>No results found for "${escapeHtml(query)}"</p>
                    </div>`;
                return;
            }

            let gridHtml = '';
            data.forEach(item => {
                const type = item.media_type || (item.title ? 'movie' : 'tv');
                const title = item.title || item.name || 'Untitled';
                
                // Fallback for missing poster
                const poster = item.poster_path 
                    ? `https://image.tmdb.org/t/p/w400${item.poster_path}` 
                    : '../assets/no-poster.png';
                
                const year = (item.release_date || item.first_air_date || '').substring(0, 4) || 'N/A';
                const escapedTitle = escapeHtml(title);
                const typeLabel = type === 'tv' ? 'TV Show' : 'Movie';
                const overview = item.overview || 'No description available.';
                
                // NOTE: We use class "watchlist-media-card" so the global click listener picks it up
                gridHtml += `
                    <div class="watchlist-media-card" 
                         data-tmdb-id="${item.id}"
                         data-title="${escapedTitle}"
                         data-poster="${item.poster_path || ''}"
                         data-type="${type}"
                         data-overview="${escapeHtml(overview)}"
                         data-release-date="${item.release_date || item.first_air_date || ''}"
                         data-backdrop="${item.backdrop_path || ''}">
                         
                        <div class="watchlist-poster-container">
                            <img src="${poster}" 
                                 alt="${escapedTitle}" 
                                 class="watchlist-media-poster" 
                                 onerror="this.onerror=null; this.src='../assets/no-poster.png';" />
                            <div class="watchlist-poster-overlay"></div>
                        </div>

                        <div class="watchlist-media-info">
                            <h3 class="watchlist-media-title">${escapedTitle}</h3>
                            <p class="watchlist-media-meta">${typeLabel} • ${year}</p>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = `
                <div class="search-results-header">
                    <div class="watchlist-grid">
                        ${gridHtml}
                    </div>
                </div>
            `;

        } catch (error) {
            container.innerHTML = '<div style="text-align:center; color:red;">Error performing search.</div>';
            console.error(error);
        }
    }
  </script>
</body>
</html>