/* Media Details Modal Logic */

let currentMediaDetails = null;
let currentRating = 0;
let currentStatusModal = 'wantToWatch';
let modalMediaDetails = null;
let modalStatus = 'wantToWatch';
let tempRating = 0;

async function openMediaDetails(mediaData) {
  let tmdbDetails = null;
  
  // Fetch extra details from TMDB if available
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

  // Merge details
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

  // State Setup
  modalMediaDetails = details;
  currentRating = details.rating ? (details.rating / 2) : 0;
  modalStatus = details.status || 'wantToWatch';
  currentStatusModal = modalStatus;
  currentMediaDetails = details;

  // --- BAD FETCH HANDLERS ---
  const posterSrc = details.poster 
    ? `https://image.tmdb.org/t/p/original${details.poster}` 
    : '../assets/no-poster.png';

  const backdropSrc = details.backdrop 
    ? `https://image.tmdb.org/t/p/original${details.backdrop}` 
    : posterSrc;

  const overviewText = details.overview || "No description available for this title.";
  const titleText = details.title || "Unknown Title";

  // Generate HTML
  const container = document.getElementById('media-details-container');

  const genreHTML = (details.genres && details.genres.length)
    ? details.genres.slice(0, 5).map(g => `<span class="genre-tag">${escapeHtml(g.name || g)}</span>`).join('')
    : '';

  const year = details.release_date ? new Date(details.release_date).getFullYear() : 'N/A';
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

      <img src="${backdropSrc}"
           alt="${escapeHtml(titleText)}"
           class="media-backdrop"
           onerror="this.src='../assets/no-poster.png'" />

      <div class="media-details-content">
        <div class="media-title-section">
          <h1 class="media-title">${escapeHtml(titleText)}</h1>
          <div class="media-meta-primary">${metaPrimaryHTML}</div>
          <div class="media-genres">${genreHTML}</div>
        </div>

        <p class="media-description">
          ${escapeHtml(overviewText)}
        </p>

        <div class="media-bottom-row">
          <!-- Left Side: Finished/Rating Section -->
          <div class="finished-status-section ${modalStatus === 'finished' ? 'active' : ''}" id="finished-status-section">
            <div class="rating-section">
              <label class="rating-label">Your Rating</label>
              <div class="star-rating" id="star-rating">
                ${generateStarsStructure()}
                <span id="rating-text-score" class="rating-text-score"></span>
              </div>
            </div>
            <div class="review-section">
              <label class="review-label">Review</label>
              <textarea class="review-textarea" id="review-textarea" placeholder="Share your thoughts...">${details.review || ''}</textarea>
            </div>
          </div>

          <!-- Right Side: Actions -->
          <div class="media-watchlist-actions">
            <div class="watchlist-status-dropdown">
              <button class="status-dropdown-btn" onclick="toggleStatusDropdown()">
                <span id="selected-status-text">${formatStatus(modalStatus)}</span>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              <div class="status-dropdown-menu" id="status-dropdown-menu">
                <div class="status-option ${modalStatus === 'wantToWatch' ? 'selected' : ''}" onclick="selectStatus('wantToWatch')">
                  Want to watch
                </div>
                <div class="status-option ${modalStatus === 'watching' ? 'selected' : ''}" onclick="selectStatus('watching')">
                  Currently Watching
                </div>
                <div class="status-option ${modalStatus === 'finished' ? 'selected' : ''}" onclick="selectStatus('finished')">
                  Finished
                </div>
              </div>
            </div>

            <button class="add-to-list-btn" onclick="saveMediaDetails()">
              ${details.id ? 'Update My List' : 'Add to My List'}
            </button>
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
  setTimeout(() => { container.innerHTML = ''; }, 300);
}

function toggleStatusDropdown() {
  const dropdown = document.getElementById('status-dropdown-menu');
  const button = document.querySelector('.status-dropdown-btn');
  dropdown.classList.toggle('active');
  button.classList.toggle('active');
}

function selectStatus(status) {
  modalStatus = status;
  currentStatusModal = status;
  document.getElementById('selected-status-text').textContent = formatStatus(status);
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
  updateStarVisuals(currentRating);

  stars.forEach(star => {
    star.addEventListener('click', function (e) {
      const rect = this.getBoundingClientRect();
      const starIndex = parseInt(this.dataset.index);
      const isHalf = (e.clientX - rect.left) < (rect.width / 2);
      tempRating = isHalf ? starIndex - 0.5 : starIndex;
      updateStarVisuals(tempRating);
      currentRating = tempRating;
    });
  });
}

function updateStarVisuals(rating) {
  const stars = document.querySelectorAll('.star');
  const textScore = document.getElementById('rating-text-score');

  if (textScore) {
    const scoreOutOf10 = rating > 0 ? (rating * 2) : 0;
    textScore.textContent = scoreOutOf10 > 0
      ? (Number.isInteger(scoreOutOf10) ? `${scoreOutOf10}/10` : `${scoreOutOf10}/10`)
      : '';
  }

  stars.forEach((star, index) => {
    const starVal = index + 1;
    star.style.fill = 'rgba(255, 255, 255, 0.2)';
    star.style.color = 'rgba(255, 255, 255, 0.2)';
    if (rating >= starVal) {
      star.style.color = '#a546ff';
      star.style.fill = 'currentColor';
    } else if (rating >= starVal - 0.5) {
      star.style.color = '#a546ff';
      star.style.fill = 'url(#half-star-gradient)';
    } else {
      star.style.fill = 'currentColor';
    }
  });
}

// --- FIXED FUNCTION ---
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
    
    // Fix: Changed variable from 'res' to 'response' to match the next line
    const response = await fetch(endpoint, {
      method: "POST", 
      body: JSON.stringify(dataToSave)
    });
    
    const data = await response.json();
    
    if (data.success) {
        showToast(data.message);
        closeMediaDetails();
        
        // Fix: Guard check for loadWatchlist so it doesn't crash on Search Page
        if (typeof loadWatchlist === 'function') {
            loadWatchlist();
        }
    } else {
        showToast(data.message || "Error saving");
    }
  } catch (error) {
    console.error("Save Error:", error);
    showToast("An error occurred while saving.");
  }
}

// Global click listener
document.addEventListener('click', function (e) {
  const card = e.target.closest('.watchlist-media-card');
  if (card) {
    openMediaDetails({
      id: parseInt(card.dataset.id) || null, 
      tmdb_id: parseInt(card.dataset.tmdbId),
      title: card.dataset.title,
      poster: card.dataset.poster,
      type: card.dataset.type,
      overview: card.dataset.overview,
      release_date: card.dataset.releaseDate,
      backdrop: card.dataset.backdrop,
      status: card.dataset.status,
      rating: parseFloat(card.dataset.rating) || 0,
      review: card.dataset.review
    });
  }
});