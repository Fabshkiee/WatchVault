/* Utility Functions */

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function formatStatusTitle(status) {
  const statusMap = {
    'watching': 'Currently Watching',
    'wantToWatch': 'Want to Watch',
    'finished': 'Finished'
  };
  return statusMap[status] || 'Other';
}

function formatStatus(status) {
  const statusMap = {
    'watching': 'Currently Watching',
    'wantToWatch': 'Want to watch',
    'finished': 'Finished'
  };
  return statusMap[status] || status;
}

function showToast(message) {
  const toast = document.getElementById("toast-container");
  toast.innerHTML = `<div class="toast">${escapeHtml(message)}</div>`;
  setTimeout(() => toast.innerHTML = '', 3000);
}
