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
<body>
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

      
      <form class="dashboard-search-container" id="main-search-form" onsubmit="handleSearchSubmit(event)">
        <svg class="dashboard-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          type="text"
          id="main-search-input"
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

  <script>
    /* ---------- STICKY SEARCH LOGIC ---------- */
    const searchForm = document.getElementById('main-search-form');
    const placeholder = document.getElementById('search-placeholder');
    
    // Calculate threshold based on initial position
    const stickyThreshold = searchForm ? (searchForm.offsetTop - 20) : 250;

    window.addEventListener('scroll', () => {
        if (!searchForm) return;

        if (window.scrollY > stickyThreshold) {
            searchForm.classList.add('sticky');
            if (placeholder) placeholder.style.display = 'block';
        } else {
            searchForm.classList.remove('sticky');
            if (placeholder) placeholder.style.display = 'none';
        }
    });


    // Pill Animation Logic
    function movePill(button, pill) {
        if (!button || !pill) return;
        const group = button.parentElement;
        const groupRect = group.getBoundingClientRect();
        const buttonRect = button.getBoundingClientRect();
        pill.style.width = buttonRect.width + 'px';
        pill.style.left = (buttonRect.left - groupRect.left) + 'px';
    }

    // Initialize state
    let currentCategory = '<?php echo $initial_category; ?>';
    
    // Initialize Pill
    setTimeout(() => {
        const activeFilterBtn = document.querySelector('.dashboard-filter-button.active');
        const filterPill = document.querySelector('.dashboard-filter-group .active-pill');
        if (activeFilterBtn && filterPill) movePill(activeFilterBtn, filterPill);
    }, 100);

    // Perform search on load if query exists
    document.addEventListener('DOMContentLoaded', () => {
        const query = document.getElementById('main-search-input').value.trim();
        if(query) {
            performSearch(query, currentCategory);
        }
    });

    // Handle Form Submit
    function handleSearchSubmit(e) {
        e.preventDefault();
        const query = document.getElementById('main-search-input').value.trim();
        if(query) {
            // Update URL without reload
            const newUrl = `search.php?q=${encodeURIComponent(query)}&category=${currentCategory}`;
            window.history.pushState({path: newUrl}, '', newUrl);
            performSearch(query, currentCategory);
        }
    }

    // Handle Category Click
    document.querySelectorAll('.dashboard-filter-button').forEach(btn => {
        btn.addEventListener('click', () => {
            // UI
            document.querySelectorAll('.dashboard-filter-button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            movePill(btn, document.querySelector('.dashboard-filter-group .active-pill'));
            
            // Logic
            currentCategory = btn.dataset.filter;
            const query = document.getElementById('main-search-input').value.trim();
            if(query) performSearch(query, currentCategory);
        });
    });

    // Execute API Search
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
                const poster = item.poster_path ? `https://image.tmdb.org/t/p/w400${item.poster_path}` : '../../assets/no-poster.png';
                const year = (item.release_date || item.first_air_date || '').substring(0, 4);
                const escapedTitle = escapeHtml(title);
                const typeLabel = type === 'tv' ? 'TV Show' : 'Movie';
                
                // Using "watchlist-media-card" classes to match dashboard design exactly
                gridHtml += `
                    <div class="watchlist-media-card" 
                         data-tmdb-id="${item.id}"
                         data-title="${escapedTitle}"
                         data-poster="${item.poster_path || ''}"
                         data-type="${type}"
                         data-overview="${escapeHtml(item.overview || 'No overview available')}"
                         data-release-date="${item.release_date || item.first_air_date || ''}"
                         data-backdrop="${item.backdrop_path || ''}">
                         
                        <div class="watchlist-poster-container">
                            <img src="${poster}" alt="${escapedTitle}" class="watchlist-media-poster" 
                                 onerror="this.src='../../assets/no-poster.png'" />
                            <div class="watchlist-poster-overlay"></div>
                        </div>

                        <div class="watchlist-media-info">
                            <h3 class="watchlist-media-title">${escapedTitle}</h3>
                            <p class="watchlist-media-meta">${typeLabel} • ${year}</p>
                        </div>
                    </div>
                `;
            });

            // Use watchlist-grid for same layout spacing
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

    // Utility
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function showToast(message) {
        const toast = document.getElementById("toast-container");
        toast.innerHTML = `<div class="toast">${escapeHtml(message)}</div>`;
        setTimeout(() => toast.innerHTML = '', 3000);
    }

    // Modal Integration - Updated to match Dashboard
    let currentMediaDetails = null;
    let currentRating = 0;
    let currentStatusModal = 'wantToWatch';
    
    // Define all modal functions (same as Dashboard for consistency)
    let modalMediaDetails = null; 
    let modalStatus = 'wantToWatch';
    let tempRating = 0;

    async function openMediaDetails(mediaData) {
      // If we have a TMDB id, fetch richer details
      let tmdbDetails = null;
      if (mediaData.tmdb_id) {
        try {
          const resp = await fetch(`../php/api/get_tmdb_details.php?id=${encodeURIComponent(mediaData.tmdb_id)}&type=${encodeURIComponent(mediaData.type || 'movie')}`);
          if (resp.ok) {
            const json = await resp.json();
            if (!json.status_code) tmdbDetails = json;
          }
        } catch (err) {
          console.warn('Failed to fetch TMDB details', err);
        }
      }

      // Merge TMDB details into the mediaData object
      const details = Object.assign({}, mediaData);
      if (tmdbDetails) {
        details.overview = tmdbDetails.overview || details.overview;
        details.backdrop = tmdbDetails.backdrop_path || details.backdrop || tmdbDetails.poster_path || details.poster;
        details.poster = tmdbDetails.poster_path || details.poster;
        details.release_date = tmdbDetails.release_date || tmdbDetails.first_air_date || details.release_date;
        details.genres = tmdbDetails.genres || details.genres || [];
        details.tmdb_rating = tmdbDetails.vote_average || null;
        details.tmdb_vote_count = tmdbDetails.vote_count || null;
      }

      modalMediaDetails = details;
      modalStatus = 'wantToWatch'; // New item default
      currentRating = 0; // New item default
      currentStatusModal = modalStatus;
      currentMediaDetails = details;
      tempRating = 0;

      const container = document.getElementById('media-details-container');

      // Build genre tags as PILLS
      const genreHTML = (details.genres && details.genres.length)
        ? details.genres.slice(0,5).map(g => `<span class="genre-tag">${escapeHtml(g.name || g)}</span>`).join('')
        : '';

      // TMDB Rating + Year Block
      const year = details.release_date ? new Date(details.release_date).getFullYear() : '';
      const tmdbRatingVal = details.tmdb_rating ? Number(details.tmdb_rating).toFixed(1) : '';
      const tmdbCount = details.tmdb_vote_count ? `(${details.tmdb_vote_count.toLocaleString()})` : '';
      
      let metaPrimaryHTML = '';
      if (tmdbRatingVal) {
          metaPrimaryHTML += `
            <div class="tmdb-rating">
               <span class="tmdb-logo-text">TMDB</span>
               <span>${tmdbRatingVal} ${tmdbCount}</span>
            </div>`;
      }
      if (tmdbRatingVal && year) metaPrimaryHTML += `<span class="meta-separator">•</span>`;
      if (year) metaPrimaryHTML += `<span class="media-year">${year}</span>`;

      const modalHTML = `
        <div class="media-details-modal">
          <button class="modal-close-btn" onclick="closeMediaDetails()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <img src="${details.backdrop ? `https://image.tmdb.org/t/p/original${details.backdrop}` : details.poster ? `https://image.tmdb.org/t/p/original${details.poster}` : '../../assets/no-poster.png'}"
               alt="${escapeHtml(details.title)}"
               class="media-backdrop"
               onerror="this.src='../../assets/no-poster.png'" />

          <div class="media-details-content">
            <div class="media-title-section">
              <h1 class="media-title">${escapeHtml(details.title)}</h1>
              <div class="media-meta-primary">${metaPrimaryHTML}</div>
              <div class="media-genres">${genreHTML}</div>
            </div>

            <p class="media-description">
              ${escapeHtml(details.overview || 'No description available.')}
            </p>

            <div class="media-bottom-row">
                
                <!-- Left Side: Finished/Rating Section -->
                <div class="finished-status-section" id="finished-status-section">
                    <div class="rating-section">
                        <label class="rating-label">Your Rating</label>
                        <div class="star-rating" id="star-rating">
                          ${generateStarsStructure()}
                          <span id="rating-text-score" class="rating-text-score"></span>
                        </div>
                    </div>
                    <div class="review-section">
                        <label class="review-label">Review</label>
                        <textarea class="review-textarea" id="review-textarea" placeholder="Share your thoughts..."></textarea>
                    </div>
                </div>

                <!-- Right Side: Actions -->
                <div class="media-watchlist-actions">
                    <div class="watchlist-status-dropdown">
                      <button class="status-dropdown-btn" onclick="toggleStatusDropdown()">
                        <span id="selected-status-text">Want to watch</span>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                      </button>
                      <div class="status-dropdown-menu" id="status-dropdown-menu">
                        <div class="status-option selected" onclick="selectStatus('wantToWatch')">Want to watch</div>
                        <div class="status-option" onclick="selectStatus('watching')">Currently Watching</div>
                        <div class="status-option" onclick="selectStatus('finished')">Finished</div>
                      </div>
                    </div>

                    <button class="add-to-list-btn" onclick="saveMediaDetails()">Add to Library</button>
                </div>

            </div>
          </div>
        </div>
      `;

      container.innerHTML = modalHTML;
      container.classList.add('active');
      document.body.style.overflow = 'hidden';
      
      initializeStarRating();
    }

    function closeMediaDetails() {
        document.getElementById('media-details-container').classList.remove('active');
        document.body.style.overflow = '';
    }

    function toggleStatusDropdown() {
        document.getElementById('status-dropdown-menu').classList.toggle('active');
        document.querySelector('.status-dropdown-btn').classList.toggle('active');
    }

    function selectStatus(status) {
        modalStatus = status;
        currentStatusModal = status;
        const map = {'wantToWatch': 'Want to watch', 'watching': 'Currently Watching', 'finished': 'Finished'};
        document.getElementById('selected-status-text').textContent = map[status];
        
        document.querySelectorAll('.status-option').forEach(opt => opt.classList.remove('selected'));
        event.target.classList.add('selected');
        
        const finishedSection = document.getElementById('finished-status-section');
        if (status === 'finished') finishedSection.classList.add('active');
        else finishedSection.classList.remove('active');
        
        toggleStatusDropdown();
    }
    
    function generateStarsStructure() {
      let starsHTML = '';
      for (let i = 1; i <= 5; i++) {
        starsHTML += `<svg class="star" data-index="${i}" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>`;
      }
      return starsHTML;
    }

    function initializeStarRating() {
      const stars = document.querySelectorAll('.star');
      const starContainer = document.getElementById('star-rating');
      const textScore = document.getElementById('rating-text-score');

      // Set initial state
      updateStarVisuals(currentRating);

      stars.forEach(star => {
        // Click to set
        star.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const starIndex = parseInt(this.dataset.index);
            
            // Check if cursor is on left half (0-50%)
            const isHalf = (e.clientX - rect.left) < (rect.width / 2);
            
            // Calculate temp rating (e.g., 3.5 or 4.0)
            tempRating = isHalf ? starIndex - 0.5 : starIndex;
            
            updateStarVisuals(tempRating);
            currentRating = tempRating; // Update global rating
        });
      });
    }

    function updateStarVisuals(rating) {
      const stars = document.querySelectorAll('.star');
      const textScore = document.getElementById('rating-text-score');
      
      // Update Text Score (e.g. 7.5/10)
      if (textScore) {
          const scoreOutOf10 = rating > 0 ? (rating * 2) : 0;
          textScore.textContent = scoreOutOf10 > 0 
              ? (Number.isInteger(scoreOutOf10) ? `${scoreOutOf10}/10` : `${scoreOutOf10}/10`) 
              : ''; 
      }

      stars.forEach((star, index) => {
        const starVal = index + 1; // 1, 2, 3, 4, 5
        
        // Reset styles
        star.style.fill = 'rgba(255, 255, 255, 0.2)';
        star.style.color = 'rgba(255, 255, 255, 0.2)';
        
        if (rating >= starVal) {
            // Full Star
            star.style.color = '#a546ff';
            star.style.fill = 'currentColor';
        } else if (rating >= starVal - 0.5) {
            // Half Star
            star.style.color = '#a546ff'; 
            // Apply the gradient via fill URL referencing the injected SVG definition
            star.style.fill = 'url(#half-star-gradient)';
        } else {
            // Empty Star
            star.style.fill = 'currentColor';
        }
      });
    }

    async function saveMediaDetails() {
        const review = document.getElementById('review-textarea')?.value || '';
        const dataToSave = { 
            ...currentMediaDetails, 
            status: currentStatusModal,
            rating: currentStatusModal === 'finished' ? currentRating * 2 : null,
            review: currentStatusModal === 'finished' ? review : ''
        };
        try {
            const endpoint = currentMediaDetails.id ? "../php/api/update_media.php" : "../php/api/add_media.php";
            await fetch(endpoint, {
                method: "POST", body: JSON.stringify(dataToSave)
            });
            const data = await res.json();
            showToast(data.message);
            closeMediaDetails();
            loadWatchlist(); // Refresh everything
            showToast("Saved successfully!");
        } catch (error) {
            console.error(error);
        }
    }

    // Updated click listener to target watchlist-media-card
    document.addEventListener('click', function(e) {
        const card = e.target.closest('.watchlist-media-card');
        if (card) {
            openMediaDetails({
                tmdb_id: parseInt(card.dataset.tmdbId),
                title: card.dataset.title,
                poster: card.dataset.poster,
                type: card.dataset.type,
                overview: card.dataset.overview,
                release_date: card.dataset.releaseDate,
                backdrop: card.dataset.backdrop,
                status: card.dataset.status, // Fixed from watchlistCard
                rating: parseFloat(card.dataset.rating) || 0,
                review: card.dataset.review
            });
        }
    });
  </script>
</body>
</html>