<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/db.php';
require_once 'includes/functions.php';
require_login();

$page_title = $page_title ?? ''; // fallback if not set
$user_full_name = htmlspecialchars($_SESSION['full_name'] ?? 'System Administrator');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?= $page_title ? $page_title . ' - ' : '' ?>SCSVMV University - Student Management System</title>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
<!-- FontAwesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
<!-- Custom CSS -->
<link href="assets/css/global.css" rel="stylesheet" />
<link href="assets/css/theme.css" rel="stylesheet" />

<link rel="icon" href="assets/img/logo.png" />
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light sticky-top d-flex justify-content-between px-3 py-2 shadow-sm">
    <div class="d-flex align-items-center">
        <button class="btn btn-light me-3" type="button" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <a class="navbar-logo d-flex align-items-center gap-2 text-decoration-none" href="dashboard.php">
            <img src="assets/img/logo.png" alt="Logo" style="height:60px;" class="univ-logo" />
            <div class="text-dark">
                <h5 class="mb-0 fw-bold">Sri Chandrasekharendra Saraswathi Viswa Mahavidyalaya</h5>
                <small>University Student Management System</small>
            </div>
        </a>
    </div>

  <form class="d-flex search-bar" id="navbarSearchForm" role="search" aria-label="Search form">
    <div class="input-group">
        <input
            class="form-control"
            type="search"
            id="navbarSearchInput"
            placeholder="Search by registration number..."
            aria-label="Search by registration number"
            required
        />
        <button class="btn btn-primary" type="submit" aria-label="Search">
            <i class="fas fa-search"></i>
        </button>
    </div>
</form>



    <!-- Profile Dropdown -->
    <div class="dropdown">
        <button
            class="btn btn-light dropdown-toggle"
            type="button"
            id="userDropdown"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-haspopup="true"
            aria-label="User menu"
        >
            <i class="fas fa-user me-1"></i><?= $user_full_name ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-id-card me-2"></i>View Profile</a></li>
            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
        </ul>
    </div>
</nav>

<!-- Sidebar -->
<div id="mySidebar" class="sidebar">
    <button type="button" class="btn-close close-btn" aria-label="Close sidebar" onclick="closeSidebar()"></button>
    <a href="dashboard.php">Dashboard</a>
    <a href="students.php">Students</a>
    <a href="leave.php">Leave Requests</a>
    <a href="onduty.php">On-Duty</a>
    <a href="disciplinary.php">Disciplinary</a>
    <a href="marks.php">Marks</a>
    <a href="search.php">Search</a>

    <?php if (has_role('Admin') || has_role('DeptAdmin') || has_role('HOD')): ?>
        <a href="class_management.php">Classes</a>
        <a href="attendance.php">Attendance</a>
        <a href="timetable.php">Timetable</a>
    <?php endif; ?>

    <a href="reports.php">Reports</a>

    <?php if (has_role('HOD')): ?>
        <a href="hod.php">HOD Dashboard</a>
    <?php endif; ?>

    <?php if (has_role('DeptAdmin') || has_role('Admin')): ?>
        <a href="department_management.php">Departments</a>
    <?php endif; ?>

    <?php if (has_role('Admin')): ?>
        <a href="users.php">Users</a>
        <a href="settings.php">Settings</a>
    <?php endif; ?>
</div>

<!-- Main Content Wrapper -->
<div id="main-content">

<!-- Your page content goes here -->

</div>

<!-- Bootstrap Bundle JS (Popper included) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="assets/js/sidebar.js"></script>
<script src="assets/js/profile.js"></script>

<script>
document.getElementById('sidebarToggle').addEventListener('click', () => {
    const sidebar = document.getElementById('mySidebar');
    const mainContent = document.getElementById('main-content');

    const isOpen = sidebar.classList.contains('open');

    if (isOpen) {
        sidebar.classList.remove('open');
        mainContent.classList.remove('sidebar-open');
    } else {
        sidebar.classList.add('open');
        mainContent.classList.add('sidebar-open');
    }
});



    // Close sidebar function (can also be called from sidebar close button)
    window.closeSidebar = () => {
        document.getElementById('mySidebar').classList.remove('open');
    };

    // Optionally, initialize Bootstrap dropdowns (but Bootstrap auto-init is usually enough)
});
</script>
<script src="assets/js/search.js"></script>

</body>
</html>
