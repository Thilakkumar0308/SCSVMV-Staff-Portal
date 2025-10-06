/* ===============================
   Sidebar Management - Responsive Dynamic Width
   =============================== */

function openSidebar() {
  const sidebar = document.getElementById("mySidebar");
  const mainContent = document.getElementById("main-content");

  if (sidebar && mainContent) {
    sidebar.classList.add('open');
    mainContent.classList.add('sidebar-open');
  }
}

function closeSidebar() {
  const sidebar = document.getElementById("mySidebar");
  const mainContent = document.getElementById("main-content");

  if (sidebar && mainContent) {
    sidebar.classList.remove('open');
    mainContent.classList.remove('sidebar-open');
  }
}

function toggleSidebar() {
  const sidebar = document.getElementById("mySidebar");
  const mainContent = document.getElementById("main-content");

  if (sidebar && mainContent) {
    if (sidebar.classList.contains('open')) {
      closeSidebar();
    } else {
      openSidebar();
    }
  }
}

function initializeSidebar() {
  const sidebarToggleBtn = document.getElementById('sidebarToggle');

  if (sidebarToggleBtn) {
    sidebarToggleBtn.addEventListener('click', toggleSidebar);
  }

  // Close sidebar on outside click (for small screens)
  document.addEventListener('click', function(e) {
    if (window.innerWidth <= 768) {
      const sidebar = document.getElementById("mySidebar");
      if (!e.target.closest('#mySidebar') && !e.target.closest('#sidebarToggle')) {
        if (sidebar && sidebar.classList.contains('open')) {
          closeSidebar();
        }
      }
    }
  });

  // Close sidebar on window resize (debounced for performance)
  window.addEventListener('resize', debounce(function() {
    closeSidebar();
  }, 250));
}

// Debounce utility function
function debounce(func, wait, immediate) {
  let timeout;
  return function() {
    const context = this, args = arguments;
    const later = function() {
      timeout = null;
      if (!immediate) func.apply(context, args);
    };
    const callNow = immediate && !timeout;
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
    if (callNow) func.apply(context, args);
  };
}

// Expose functions globally if needed
window.openSidebar = openSidebar;
window.closeSidebar = closeSidebar;
window.toggleSidebar = toggleSidebar;
window.initializeSidebar = initializeSidebar;

// Initialize sidebar behavior after DOM loads
document.addEventListener('DOMContentLoaded', initializeSidebar);
