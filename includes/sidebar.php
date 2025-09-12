<?php
// Sidebar navigation - can be included in pages that need it
?>
<div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>" href="students.php">
                    <i class="fas fa-users me-2"></i>Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'leave.php' ? 'active' : ''; ?>" href="leave.php">
                    <i class="fas fa-calendar-times me-2"></i>Leave Requests
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'onduty.php' ? 'active' : ''; ?>" href="onduty.php">
                    <i class="fas fa-calendar-check me-2"></i>On-Duty
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'disciplinary.php' ? 'active' : ''; ?>" href="disciplinary.php">
                    <i class="fas fa-exclamation-triangle me-2"></i>Disciplinary
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'marks.php' ? 'active' : ''; ?>" href="marks.php">
                    <i class="fas fa-chart-line me-2"></i>Marks
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-file-alt me-2"></i>Reports
                </a>
            </li>
            <?php if (has_role('Admin')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-user-cog me-2"></i>Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog me-2"></i>Settings
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

