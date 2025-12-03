/* Watchlist Management */

let allWatchlistItems = [];
let currentCategory = 'all';
let currentStatus = 'all';
let searchQuery = '';

async function loadWatchlist() {
  const container = document.getElementById('dashboard-content-sections');
  const url = `../php/api/get_watchlist.php?status=all`;

  try {
    const response = await fetch(url);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const data = await response.json();
    if (data && data.error) throw new Error(data.error);

    allWatchlistItems = Array.isArray(data) ? data : [];
    renderWatchlist();
  } catch (error) {
    console.error('Failed to load watchlist:', error);
    showToast('Failed to load watchlist');
  }
}

function renderWatchlist() {
  const container = document.getElementById('dashboard-content-sections');
  container.innerHTML = '';

  let filteredItems = allWatchlistItems.filter(item => {
    if (currentCategory !== 'all' && item.type !== currentCategory) return false;
    if (searchQuery) {
      if (!item.title.toLowerCase().includes(searchQuery.toLowerCase())) return false;
    }
    return true;
  });

  const groups = {
    'watching': [],
    'wantToWatch': [],
    'finished': []
  };

  filteredItems.forEach(item => {
    if (groups[item.status]) {
      groups[item.status].push(item);
    } else {
      groups['wantToWatch'].push(item);
    }
  });

  const sectionsToShow = (currentStatus === 'all')
    ? ['watching', 'wantToWatch', 'finished']
    : [currentStatus];

  let hasContent = false;

  sectionsToShow.forEach(statusKey => {
    const items = groups[statusKey];

    if (items.length > 0) {
      hasContent = true;
      const sectionTitle = formatStatusTitle(statusKey);

      const section = document.createElement('section');
      section.className = 'media-section';

      let sectionHTML = `<h2 class="section-title">${sectionTitle} <span class="count-badge">${items.length}</span></h2>`;
      sectionHTML += `<div class="watchlist-grid">`;

      items.forEach(item => {
        sectionHTML += createCardHTML(item);
      });

      sectionHTML += `</div>`;
      section.innerHTML = sectionHTML;
      container.appendChild(section);
    }
  });

  if (!hasContent) {
    container.innerHTML = `
      <div class="watchlist-empty">
        <p>No items found.</p>
      </div>`;
  }
}

function createCardHTML(item) {
  // --- FIX: Robust Fallbacks for Missing Data ---
  const posterSrc = item.poster 
    ? `https://image.tmdb.org/t/p/w400${item.poster}` 
    : '../assets/no-poster.png'; // Fallback if API return null
    
  const title = item.title || 'Untitled';
  const escapedTitle = escapeHtml(title);
  
  // Safe date parsing
  const year = item.release_date 
    ? new Date(item.release_date).getFullYear() 
    : 'N/A';
    
  const typeLabel = item.type === 'tv' ? 'TV Show' : 'Movie';
  const rating = item.rating || 0;
  const overview = item.overview || 'No description available.';

  const ratingBadge = rating > 0 ? `
    <div class="watchlist-media-rating">
      <svg viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
      </svg>
      <span>${Number(rating).toFixed(1)}</span>
    </div>
  ` : '';

  // Use onerror to handle 404s even if variable wasn't null
  return `
    <div class="watchlist-media-card"
         data-id="${item.id}"
         data-tmdb-id="${item.tmdb_id}"
         data-title="${escapedTitle}"
         data-poster="${item.poster || ''}"
         data-type="${item.type}"
         data-overview="${escapeHtml(overview)}"
         data-release-date="${item.release_date || ''}"
         data-backdrop="${item.backdrop_path || ''}"
         data-status="${item.status || 'wantToWatch'}"
         data-rating="${item.rating || 0}"
         data-review="${escapeHtml(item.review || '')}">

      <div class="watchlist-poster-container">
        <img src="${posterSrc}" 
             alt="${escapedTitle}" 
             class="watchlist-media-poster"
             onerror="this.onerror=null; this.src='../assets/no-poster.png';" />
        <div class="watchlist-poster-overlay"></div>
        ${ratingBadge}

        <button class="delete-card-btn" onclick="deleteFromCard(event, ${item.id})" title="Remove from library">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>

      <div class="watchlist-media-info">
        <h3 class="watchlist-media-title">${escapedTitle}</h3>
        <p class="watchlist-media-meta">${typeLabel} • ${year}</p>
      </div>
    </div>
  `;
}

function initWatchlistFilters() {
  document.querySelectorAll('.dashboard-filter-button').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.dashboard-filter-button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      movePill(btn, document.querySelector('.dashboard-filter-group .active-pill'));
      currentCategory = btn.dataset.filter;
      renderWatchlist();
    });
  });

  document.querySelectorAll('.dashboard-status-button').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.dashboard-status-button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      movePill(btn, document.querySelector('.dashboard-status-buttons .active-pill'));
      currentStatus = btn.dataset.status;
      renderWatchlist();
    });
  });

  document.getElementById('dashboard-watchlist-search').addEventListener('input', (e) => {
    searchQuery = e.target.value.trim();
    renderWatchlist();
  });
}

function deleteFromCard(event, id) {
  event.stopPropagation();
  if (!confirm("Remove this from your library?")) return;

  fetch("../php/api/delete_media.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id })
  })
    .then(res => res.json())
    .then(data => {
      showToast(data.message);
      loadWatchlist();
    })
    .catch(err => console.error(err));
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
  loadWatchlist();
  initWatchlistFilters();
});