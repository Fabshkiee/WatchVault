<?php
session_start();

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
      
      <div class="dashboard-filter-group">
        <div class="active-pill"></div>
        <button class="filter-button dashboard-filter-button active" data-filter="all">All</button>
        <button class="filter-button dashboard-filter-button" data-filter="movie">Movies</button>
        <button class="filter-button dashboard-filter-button" data-filter="tv">TV Shows</button>
        <button class="filter-button dashboard-filter-button" data-filter="anime">Anime</button>
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

<script>
/* ---------- GLOBAL STATE ---------- */
let currentView = 'watchlist';
let lastStatus = 'all';
let watchlistItems = [];

/* ---------- SEARCH TMDB (AJAX) ---------- */
async function performSearch() {
    const query = document.getElementById('dashboard-main-search').value.trim();
    console.log('Searching for:', query);
    
    if (!query) {
        showToast('Please enter a search query');
        return;
    }

    const category = document.querySelector('.dashboard-filter-button.active')?.dataset.filter || 'all';
    const url = `../php/api/search_tmdb.php?q=${encodeURIComponent(query)}&category=${category}`;

    try {
        showToast('Searching...');
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data && data.error) {
            throw new Error(data.error);
        }
        
        if (!Array.isArray(data)) {
            throw new Error('Invalid response format from server');
        }
        
        displaySearchResults(data, query);
        
    } catch (error) {
        console.error('Search failed:', error);
        showToast('Search failed: ' + error.message);
        displaySearchResults([], query);
    }
}

/* ---------- LOAD WATCHLIST ---------- */
async function loadWatchlist(status = 'all') {
    console.log('Loading watchlist with status:', status);
    lastStatus = status;
    currentView = 'watchlist';
    
    const url = `../php/api/get_watchlist.php?status=${status}`;

    try {
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data && data.error) {
            throw new Error(data.error);
        }
        
        watchlistItems = Array.isArray(data) ? data : [];
        displayWatchlist(watchlistItems);
        
    } catch (error) {
        console.error('Failed to load watchlist:', error);
        showToast('Failed to load watchlist: ' + error.message);
        displayWatchlist([]);
    }
}

/* ---------- DISPLAY SEARCH RESULTS ---------- */
function displaySearchResults(items, query) {
    const container = document.getElementById('dashboard-content-sections');
    
    if (!items || items.length === 0) {
        container.innerHTML = `
            <div class="search-results-header">
                <div class="search-title-section">
                    <h2>Results for "${escapeHtml(query)}"</h2>
                    <button class="back-to-watchlist-btn" onclick="goBackToWatchlist()">← Back to Watchlist</button>
                </div>
                <p class="no-results">No results found for "${escapeHtml(query)}". Try a different search term.</p>
            </div>
        `;
        return;
    }

    let gridHtml = '';
    items.forEach(item => {
        const type = item.media_type || (item.title ? 'movie' : 'tv');
        const title = item.title || item.name || 'Untitled';
        const poster = item.poster_path ? `https://image.tmdb.org/t/p/w300${item.poster_path}` : '../../assets/no-poster.png';
        const year = (item.release_date || item.first_air_date || '').substring(0, 4);
        const escapedTitle = escapeHtml(title);
        
        gridHtml += `
            <div class="search-media-card" 
                 data-tmdb-id="${item.id}"
                 data-title="${escapedTitle}"
                 data-poster="${item.poster_path || ''}"
                 data-type="${type}"
                 data-overview="${escapeHtml(item.overview || 'No overview available')}"
                 data-release-date="${item.release_date || item.first_air_date || ''}"
                 data-backdrop="${item.backdrop_path || ''}">
                <img src="${poster}" alt="${escapedTitle}" class="search-media-poster" 
                     onerror="this.src='../../assets/no-poster.png'" />
                <div class="search-media-info">
                    <h3 class="search-media-title">${escapedTitle}</h3>
                    <p class="search-media-type">${type.toUpperCase()} ${year ? `(${year})` : ''}</p>
                </div>
            </div>
        `;
    });

    container.innerHTML = `
        <div class="search-results-header">
            <div class="search-title-section">
                <h2>Results for "${escapeHtml(query)}" (${items.length} found)</h2>
                <button class="back-to-watchlist-btn" onclick="goBackToWatchlist()">← Back to Watchlist</button>
            </div>
            <div class="search-results-grid">
                ${gridHtml}
            </div>
        </div>
    `;

    currentView = 'search';
}

/* ---------- GO BACK TO WATCHLIST ---------- */
function goBackToWatchlist() {
    currentView = 'watchlist';
    document.getElementById('dashboard-main-search').value = '';
    loadWatchlist(lastStatus);
}

/* ---------- DISPLAY WATCHLIST ---------- */
function displayWatchlist(items) {
    const container = document.getElementById('dashboard-content-sections');
    
    if (!items || items.length === 0) {
        container.innerHTML = `
            <div class="watchlist-empty">
                <p>Your watchlist is empty. Start by searching for movies or TV shows above!</p>
            </div>
        `;
        return;
    }

    let gridHtml = '';
    items.forEach(item => {
        const poster = item.poster ? `https://image.tmdb.org/t/p/w300${item.poster}` : '../../assets/no-poster.png';
        const escapedTitle = escapeHtml(item.title);
        const year = item.release_date ? new Date(item.release_date).getFullYear() : '';
        
        gridHtml += `
            <div class="watchlist-media-card"
                 data-id="${item.id}"
                 data-tmdb-id="${item.tmdb_id}"
                 data-title="${escapedTitle}"
                 data-poster="${item.poster || ''}"
                 data-type="${item.type}"
                 data-overview="${escapeHtml(item.overview || '')}"
                 data-release-date="${item.release_date || ''}"
                 data-backdrop="${item.backdrop_path || ''}"
                 data-status="${item.status || 'wantToWatch'}"
                 data-rating="${item.rating || 0}"
                 data-review="${escapeHtml(item.review || '')}">
                <img src="${poster}" alt="${escapedTitle}" class="watchlist-media-poster" 
                     onerror="this.src='../../assets/no-poster.png'" />
                <div class="watchlist-media-info">
                    <h3 class="watchlist-media-title">${escapedTitle} ${year ? `(${year})` : ''}</h3>
                    <p class="watchlist-media-status">Status: ${formatStatus(item.status)}</p>
                    ${item.rating ? `<p class="watchlist-media-rating">Rating: ${item.rating}/10</p>` : ''}
                    ${item.review ? `<p class="watchlist-media-review">"${escapeHtml(item.review)}"</p>` : ''}
                </div>
                <button class="delete-card-btn" onclick="deleteFromCard(event, ${item.id})" title="Remove from watchlist">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        `;
    });

    container.innerHTML = `
        <div class="watchlist-grid">
            ${gridHtml}
        </div>
    `;
}

/* ---------- ADD TO WATCHLIST ---------- */
async function addToWatchlist(media) {
    console.log('Adding to watchlist:', media);
    
    try {
        const response = await fetch("../php/api/add_media.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(media)
        });
        
        const responseText = await response.text();
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Failed to parse JSON response:', e);
            showToast('Server returned invalid response');
            return;
        }
        
        if (data.success) {
            showToast(data.message || 'Added to your watchlist!');
            if (currentView === 'watchlist') {
                setTimeout(() => loadWatchlist(lastStatus), 1000);
            }
        } else {
            showToast(data.message || 'Failed to add to watchlist.');
        }
    } catch (error) {
        console.error('Add to watchlist error:', error);
        showToast('Failed to add to watchlist. Please try again.');
    }
}

/* ---------- UPDATE MEDIA ---------- */
async function updateMediaItem(id, updates) {
    try {
        const response = await fetch("../php/api/update_media.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id, ...updates })
        });
        
        const data = await response.json();
        showToast(data.message || 'Media updated successfully!');
        loadWatchlist(lastStatus);
    } catch (error) {
        console.error('Update error:', error);
        showToast('Failed to update media.');
    }
}

/* ---------- DELETE MEDIA ---------- */
async function deleteMediaItem(id) {
    if (!confirm("Are you sure you want to remove this from your watchlist?")) return;

    try {
        const response = await fetch("../php/api/delete_media.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id })
        });
        
        const data = await response.json();
        showToast(data.message || 'Media removed from your library');
        loadWatchlist(lastStatus);
    } catch (error) {
        console.error('Delete error:', error);
        showToast('Failed to delete media.');
    }
}

/* ---------- DELETE FROM CARD ---------- */
function deleteFromCard(event, id) {
    event.stopPropagation(); // Prevent opening the modal
    deleteMediaItem(id);
}

/* ---------- DELETE FROM MODAL ---------- */
function deleteFromModal() {
    if (!currentMediaDetails || !currentMediaDetails.id) return;
    
    if (confirm("Are you sure you want to remove this from your watchlist?")) {
        deleteMediaItem(currentMediaDetails.id);
        closeMediaDetails(); // Close the modal after deletion
    }
}

/* ---------- FORMAT STATUS FOR DISPLAY ---------- */
function formatStatus(status) {
    const statusMap = {
        'watching': 'Currently Watching',
        'wantToWatch': 'Want to watch',
        'finished': 'Finished'
    };
    return statusMap[status] || status;
}

/* ---------- LOCAL WATCHLIST SEARCH ---------- */
function handleWatchlistSearch() {
    const query = document.getElementById('dashboard-watchlist-search').value.trim().toLowerCase();
    if (currentView !== 'watchlist') return;

    const filtered = watchlistItems.filter(item => 
        item.title.toLowerCase().includes(query)
    );
    displayWatchlist(filtered);
}

/* ---------- FORM SUBMIT (MAIN SEARCH) ---------- */
function handleDashboardSearch(e) {
    e.preventDefault();
    performSearch();
}

/* ---------- FILTER BUTTONS (CATEGORY) ---------- */
document.querySelectorAll('.dashboard-filter-button').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.dashboard-filter-button').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        movePill(btn, document.querySelector('.dashboard-filter-group .active-pill'));

        if (currentView === 'search') {
            performSearch();
        }
    });
});

/* ---------- STATUS BUTTONS ---------- */
document.querySelectorAll('.dashboard-status-button').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.dashboard-status-button').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        movePill(btn, document.querySelector('.dashboard-status-buttons .active-pill'));

        loadWatchlist(btn.dataset.status);
    });
});

/* ---------- TOAST ---------- */
function showToast(message) {
    const toast = document.getElementById("toast-container");
    toast.innerHTML = `<div class="toast">${escapeHtml(message)}</div>`;
    setTimeout(() => toast.innerHTML = '', 3000);
}

/* ---------- UTILS ---------- */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/* ---------- INITIAL LOAD ---------- */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard loaded, initializing...');
    loadWatchlist();
});

/* ---------- WATCHLIST SEARCH LISTENER ---------- */
document.getElementById('dashboard-watchlist-search').addEventListener('input', handleWatchlistSearch);
</script>

<script>
/* ========================================
   MEDIA DETAILS MODAL JAVASCRIPT
   ======================================== */

let currentMediaDetails = null;
let currentRating = 0;
let currentStatus = 'wantToWatch';

/* ========================================
   OPEN MEDIA DETAILS MODAL
   ======================================== */
function openMediaDetails(mediaData) {
  currentMediaDetails = mediaData;
  currentRating = mediaData.rating ? Math.round(mediaData.rating / 2) : 0;
  currentStatus = mediaData.status || 'wantToWatch';
  
  const container = document.getElementById('media-details-container');
  
  const modalHTML = `
    <div class="media-details-modal">
      <button class="modal-close-btn" onclick="closeMediaDetails()">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
      
      <img src="${mediaData.backdrop ? `https://image.tmdb.org/t/p/original${mediaData.backdrop}` : mediaData.poster ? `https://image.tmdb.org/t/p/original${mediaData.poster}` : '../../assets/no-poster.png'}" 
           alt="${escapeHtml(mediaData.title)}" 
           class="media-backdrop"
           onerror="this.src='../../assets/no-poster.png'" />
      
      <div class="media-details-content">
        <div class="media-title-section">
          <h1 class="media-title">${escapeHtml(mediaData.title)}</h1>
          
          <div class="media-meta-info">
            ${mediaData.rating ? `
              <div class="media-rating-badge">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                ${(mediaData.rating / 2).toFixed(1)} (16,521)
              </div>
            ` : ''}
            <span class="media-year">${mediaData.release_date ? new Date(mediaData.release_date).getFullYear() : ''}</span>
          </div>
          
          <div class="media-genres">
            <span class="genre-tag">Drama</span>
            <span class="genre-tag">Crime</span>
          </div>
        </div>
        
        <p class="media-description">
          ${escapeHtml(mediaData.overview || 'No description available.')}
        </p>
        
        <div class="media-actions-section">
          <div class="media-watchlist-actions">
            <div class="watchlist-status-dropdown">
              <button class="status-dropdown-btn" onclick="toggleStatusDropdown()">
                <span id="selected-status-text">${formatStatus(currentStatus)}</span>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              
              <div class="status-dropdown-menu" id="status-dropdown-menu">
                <div class="status-option ${currentStatus === 'wantToWatch' ? 'selected' : ''}" onclick="selectStatus('wantToWatch')">
                  Want to watch
                </div>
                <div class="status-option ${currentStatus === 'watching' ? 'selected' : ''}" onclick="selectStatus('watching')">
                  Currently Watching
                </div>
                <div class="status-option ${currentStatus === 'finished' ? 'selected' : ''}" onclick="selectStatus('finished')">
                  Finished
                </div>
              </div>
            </div>
            
            <div class="finished-status-section ${currentStatus === 'finished' ? 'active' : ''}" id="finished-status-section">
              <div class="rating-section">
                <label class="rating-label">Your Rating</label>
                <div class="star-rating" id="star-rating">
                  ${generateStars(currentRating)}
                </div>
              </div>
              
              <div class="review-section">
                <label class="review-label">Review</label>
                <textarea class="review-textarea" 
                          id="review-textarea" 
                          placeholder="Share your thoughts...">${mediaData.review || ''}</textarea>
              </div>
            </div>
            
            <div class="media-actions-buttons">
              <button class="add-to-list-btn" onclick="saveMediaDetails()">
                ${mediaData.id ? 'Update My List' : 'Add to My List'}
              </button>
              ${mediaData.id ? `
                <button class="delete-from-modal-btn" onclick="deleteFromModal()" title="Remove from watchlist">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              ` : ''}
            </div>
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
  const container = document.getElementById('media-details-container');
  container.classList.remove('active');
  document.body.style.overflow = '';
  
  setTimeout(() => {
    container.innerHTML = '';
  }, 300);
}

document.addEventListener('click', function(e) {
  if (e.target.id === 'media-details-container') {
    closeMediaDetails();
  }
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeMediaDetails();
  }
});

function toggleStatusDropdown() {
  const dropdown = document.getElementById('status-dropdown-menu');
  const button = document.querySelector('.status-dropdown-btn');
  
  dropdown.classList.toggle('active');
  button.classList.toggle('active');
}

function selectStatus(status) {
  currentStatus = status;
  
  document.getElementById('selected-status-text').textContent = formatStatus(status);
  
  document.querySelectorAll('.status-option').forEach(opt => {
    opt.classList.remove('selected');
  });
  event.target.classList.add('selected');
  
  const finishedSection = document.getElementById('finished-status-section');
  if (status === 'finished') {
    finishedSection.classList.add('active');
  } else {
    finishedSection.classList.remove('active');
  }
  
  toggleStatusDropdown();
}

document.addEventListener('click', function(e) {
  const dropdown = document.getElementById('status-dropdown-menu');
  const button = document.querySelector('.status-dropdown-btn');
  
  if (dropdown && !dropdown.contains(e.target) && !button?.contains(e.target)) {
    dropdown.classList.remove('active');
    button?.classList.remove('active');
  }
});

function generateStars(rating) {
  let starsHTML = '';
  for (let i = 1; i <= 5; i++) {
    const isActive = i <= rating ? 'active' : '';
    starsHTML += `
      <svg class="star ${isActive}" data-rating="${i}" viewBox="0 0 20 20" fill="currentColor">
        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
      </svg>
    `;
  }
  return starsHTML;
}

function initializeStarRating() {
  const stars = document.querySelectorAll('.star');
  
  stars.forEach(star => {
    star.addEventListener('click', function() {
      currentRating = parseInt(this.dataset.rating);
      updateStarDisplay();
    });
    
    star.addEventListener('mouseenter', function() {
      const hoverRating = parseInt(this.dataset.rating);
      stars.forEach((s, index) => {
        if (index < hoverRating) {
          s.style.color = '#a546ff';
          s.style.transform = 'scale(1.1)';
        } else {
          s.style.color = 'rgba(255, 255, 255, 0.2)';
          s.style.transform = 'scale(1)';
        }
      });
    });
  });
  
  const starContainer = document.getElementById('star-rating');
  if (starContainer) {
    starContainer.addEventListener('mouseleave', function() {
      updateStarDisplay();
    });
  }
}

function updateStarDisplay() {
  const stars = document.querySelectorAll('.star');
  stars.forEach((star, index) => {
    if (index < currentRating) {
      star.classList.add('active');
      star.style.color = '#a546ff';
      star.style.transform = 'scale(1)';
    } else {
      star.classList.remove('active');
      star.style.color = 'rgba(255, 255, 255, 0.2)';
      star.style.transform = 'scale(1)';
    }
  });
}

async function saveMediaDetails() {
  const review = document.getElementById('review-textarea')?.value || '';
  
  const dataToSave = {
    ...currentMediaDetails,
    status: currentStatus,
    rating: currentStatus === 'finished' ? currentRating * 2 : null,
    review: currentStatus === 'finished' ? review : ''
  };
  
  try {
    if (currentMediaDetails.id) {
      await updateMediaItem(currentMediaDetails.id, {
        status: dataToSave.status,
        rating: dataToSave.rating,
        review: dataToSave.review
      });
    } else {
      await addToWatchlist(dataToSave);
    }
    
    closeMediaDetails();
    
    if (typeof loadWatchlist === 'function') {
      loadWatchlist(lastStatus);
    }
  } catch (error) {
    console.error('Failed to save media details:', error);
    showToast('Failed to save. Please try again.');
  }
}

document.addEventListener('click', function(e) {
  const searchCard = e.target.closest('.search-media-card');
  if (searchCard) {
    const mediaData = {
      tmdb_id: parseInt(searchCard.dataset.tmdbId),
      title: searchCard.dataset.title,
      poster: searchCard.dataset.poster,
      type: searchCard.dataset.type,
      overview: searchCard.dataset.overview,
      release_date: searchCard.dataset.releaseDate,
      backdrop: searchCard.dataset.backdrop
    };
    openMediaDetails(mediaData);
  }
});

document.addEventListener('click', function(e) {
  const watchlistCard = e.target.closest('.watchlist-media-card');
  if (watchlistCard) {
    const mediaData = {
      id: parseInt(watchlistCard.dataset.id),
      tmdb_id: parseInt(watchlistCard.dataset.tmdbId),
      title: watchlistCard.dataset.title,
      poster: watchlistCard.dataset.poster,
      type: watchlistCard.dataset.type,
      overview: watchlistCard.dataset.overview,
      release_date: watchlistCard.dataset.releaseDate,
      backdrop: watchlistCard.dataset.backdrop,
      status: watchlistCard.dataset.status,
      rating: parseFloat(watchlistCard.dataset.rating) || 0,
      review: watchlistCard.dataset.review
    };
    openMediaDetails(mediaData);
  }
});
</script>

</html>