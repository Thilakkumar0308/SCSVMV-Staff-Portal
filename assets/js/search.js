/* ===============================
   Navbar Student Search - Redirect
   =============================== */
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('navbarSearchForm');
    const searchInput = document.getElementById('navbarSearchInput');

    if (!searchForm || !searchInput) return;

    searchForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent normal form submission

        const studentId = searchInput.value.trim();
        if (!studentId) {
            alert('Please enter a registration number');
            searchInput.focus();
            return;
        }

        // Redirect to student_info.php with the student_id
        window.location.href = 'student_info.php?student_id=' + encodeURIComponent(studentId);
    });

    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchForm.dispatchEvent(new Event('submit'));
        }
    });

    // Optional: auto-focus the input when the page loads
    searchInput.focus();
});
