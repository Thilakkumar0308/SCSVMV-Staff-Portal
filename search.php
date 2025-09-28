<?php
$page_title = 'Student Search';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Initialize variables
$search_term = '';
$redirect_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_term'])) {
    $search_term = sanitize_input($_POST['search_term']);
    if (!empty($search_term)) {
        // Redirect to student_info.php using student_id
        $redirect_url = "student_info.php?student_id=" . urlencode($search_term);
        header("Location: $redirect_url");
        exit;
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12 mt-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">Search Student</h4>
                    <p class="text-muted">Enter the registration number of the student</p>

                    <!-- Search Form (Optional) -->
                    <form id="studentSearchForm" class="d-flex justify-content-center mt-3" method="POST">
                        <input type="text" 
                               name="search_term" 
                               id="studentSearchInput"
                               class="form-control form-control-lg me-2" 
                               placeholder="Enter registration number..." 
                               value="<?= htmlspecialchars($search_term); ?>"
                               required
                               style="max-width: 400px;">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/* ===============================
   Student Search - Redirect to student_info.php
   =============================== */
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('studentSearchForm');
    const searchInput = document.getElementById('studentSearchInput');

    if (!searchForm || !searchInput) return;

    searchForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent normal form submission

        const studentId = searchInput.value.trim();
        if (!studentId) {
            alert('Please enter a registration number');
            searchInput.focus();
            return;
        }

        // Redirect to student_info.php
        window.location.href = 'student_info.php?student_id=' + encodeURIComponent(studentId);
    });

    searchInput.focus();
});
</script>

<?php require_once 'includes/footer.php'; ?>
