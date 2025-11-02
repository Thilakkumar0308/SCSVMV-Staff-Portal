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

/* ===============================
   Sidebar Management - Responsive Dynamic Width
   =============================== */

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('mySidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const closeBtn = document.querySelector('#mySidebar .close-btn');

    let isSidebarOpen = false; // track state

    const openSidebar = () => {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        isSidebarOpen = true;
    };

    const closeSidebar = () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        isSidebarOpen = false;
    };

    const toggleSidebar = () => isSidebarOpen ? closeSidebar() : openSidebar();

    // Toggle button
    sidebarToggle?.addEventListener('click', (e) => {
        e.stopPropagation(); // prevent triggering outer click
        toggleSidebar();
    });

    // Close button inside sidebar
    closeBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        closeSidebar();
    });

    // Overlay click
    overlay?.addEventListener('click', closeSidebar);

    // Outer click (click outside sidebar)
    document.addEventListener('click', (e) => {
        if (isSidebarOpen && !sidebar.contains(e.target) && e.target !== sidebarToggle) {
            closeSidebar();
        }
    });

    // Stop sidebar clicks from bubbling
    sidebar.addEventListener('click', (e) => e.stopPropagation());

    // Close on resize for desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) closeSidebar();
    });
});
</script>
