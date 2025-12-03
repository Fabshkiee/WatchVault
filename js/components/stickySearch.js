/* Dashboard Sticky Search & Pill Animation */

function initStickySearch() {
  const searchForm = document.getElementById('main-search-form');
  const placeholder = document.getElementById('search-placeholder');
  const stickyThreshold = searchForm.offsetTop - 20;

  window.addEventListener('scroll', () => {
    if (window.scrollY > stickyThreshold) {
      searchForm.classList.add('sticky');
      placeholder.style.display = 'block';
    } else {
      searchForm.classList.remove('sticky');
      placeholder.style.display = 'none';
    }
  });
}

function movePill(button, pill) {
  if (!button || !pill) return;
  const group = button.parentElement;
  const groupRect = group.getBoundingClientRect();
  const buttonRect = button.getBoundingClientRect();
  pill.style.width = buttonRect.width + 'px';
  pill.style.left = (buttonRect.left - groupRect.left) + 'px';
}

function initPillAnimations() {
  setTimeout(() => {
    const activeFilterBtn = document.querySelector('.dashboard-filter-button.active');
    const filterPill = document.querySelector('.dashboard-filter-group .active-pill');
    if (activeFilterBtn && filterPill) movePill(activeFilterBtn, filterPill);

    const activeStatusBtn = document.querySelector('.dashboard-status-button.active');
    const statusPill = document.querySelector('.dashboard-status-buttons .active-pill');
    if (activeStatusBtn && statusPill) movePill(activeStatusBtn, statusPill);
  }, 100);
}

function handleNewSearch(e) {
  e.preventDefault();
  const query = document.getElementById('add-media-search').value.trim();
  if (query) {
    window.location.href = `search.php?q=${encodeURIComponent(query)}`;
  }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  initStickySearch();
  initPillAnimations();
});
