<?php
$page_title = 'Admin Dashboard - Department Management';
require_once 'includes/header.php';

// Require admin role
require_role('Admin');

$message = '';
$message_type = '';

// Get department-wise statistics
$departments = [];
$result = $conn->query("
    SELECT d.*, 
           COUNT(DISTINCT s.id) as total_students,
           COUNT(DISTINCT c.id) as total_classes,
           COUNT(DISTINCT lr.id) as pending_leaves,
           COUNT(DISTINCT odr.id) as pending_onduty,
           COUNT(DISTINCT da.id) as active_disciplinary,
           u.full_name as hod_name
    FROM departments d
    LEFT JOIN classes c ON d.id = c.department_id
    LEFT JOIN students s ON c.id = s.class_id
    LEFT JOIN leave_requests lr ON s.id = lr.student_id AND lr.status = 'Pending'
    LEFT JOIN onduty_requests odr ON s.id = odr.student_id AND odr.status = 'Pending'
    LEFT JOIN disciplinary_actions da ON s.id = da.student_id AND da.status = 'Active'
    LEFT JOIN users u ON d.hod_id = u.id
    GROUP BY d.id
    ORDER BY d.department_name
");

while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

// Get overall statistics
$overall_stats = [];
$result = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'Active'");
$overall_stats['total_students'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM departments");
$overall_stats['total_departments'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM leave_requests WHERE status = 'Pending'");
$overall_stats['pending_leaves'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM onduty_requests WHERE status = 'Pending'");
$overall_stats['pending_onduty'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM disciplinary_actions WHERE status = 'Active'");
$overall_stats['active_disciplinary'] = $result->fetch_assoc()['total'];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-university me-2"></i>Admin Dashboard - Department Management
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                            <i class="fas fa-plus me-1"></i>Add Department
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

   <!-- Overall Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <a href="students.php" class="text-decoration-none">
            <div class="card dashboard-card gradient-blue">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Total Students</h6>
                        <h3><?php echo $overall_stats['total_students']; ?></h3>
                    </div>
                    <i class="fas fa-users fa-2x text-gray-300"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="department_management.php" class="text-decoration-none">
            <div class="card dashboard-card gradient-green">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Total Departments</h6>
                        <h3><?php echo $overall_stats['total_departments']; ?></h3>
                    </div>
                    <i class="fas fa-building fa-2x text-gray-300"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="leave.php" class="text-decoration-none">
            <div class="card dashboard-card gradient-yellow">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Pending Leaves</h6>
                        <h3><?php echo $overall_stats['pending_leaves']; ?></h3>
                    </div>
                    <i class="fas fa-calendar-times fa-2x text-gray-300"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="onduty.php" class="text-decoration-none">
            <div class="card dashboard-card gradient-purple">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Pending On-Duty</h6>
                        <h3><?php echo $overall_stats['pending_onduty']; ?></h3>
                    </div>
                    <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                </div>
            </div>
        </a>
    </div>
</div>


    <!-- Department-wise Management -->
    <div class="card shadow">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-building me-2"></i>Department-wise Management
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="departmentsTable">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>HOD</th>
                            <th>Students</th>
                            <th>Classes</th>
                            <th>Pending Leaves</th>
                            <th>Pending On-Duty</th>
                            <th>Active Disciplinary</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($dept['department_name']); ?></strong>
                                <?php if ($dept['description']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($dept['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($dept['hod_name']): ?>
                                    <span class="badge bg-success"><?php echo htmlspecialchars($dept['hod_name']); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning">No HOD Assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?php echo $dept['total_students']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo $dept['total_classes']; ?></span>
                            </td>
                            <td>
                                <?php if ($dept['pending_leaves'] > 0): ?>
                                    <span class="badge bg-warning"><?php echo $dept['pending_leaves']; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($dept['pending_onduty'] > 0): ?>
                                    <span class="badge bg-warning"><?php echo $dept['pending_onduty']; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($dept['active_disciplinary'] > 0): ?>
                                    <span class="badge bg-danger"><?php echo $dept['active_disciplinary']; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="students.php?department=<?php echo $dept['id']; ?>" class="btn btn-outline-primary" title="View Students">
                                        <i class="fas fa-users"></i>
                                    </a>
                                    <a href="leave.php?department=<?php echo $dept['id']; ?>" class="btn btn-outline-warning" title="View Leaves">
                                        <i class="fas fa-calendar-times"></i>
                                    </a>
                                    <a href="onduty.php?department=<?php echo $dept['id']; ?>" class="btn btn-outline-info" title="View On-Duty">
                                        <i class="fas fa-calendar-check"></i>
                                    </a>
                                    <a href="disciplinary.php?department=<?php echo $dept['id']; ?>" class="btn btn-outline-danger" title="View Disciplinary">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </a>
                                    <a href="reports.php?department=<?php echo $dept['id']; ?>" class="btn btn-outline-success" title="Department Report">
                                        <i class="fas fa-chart-bar"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="ajax/manage_department.php" class="add-form add-department-form">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="department_name" class="form-label">Department Name *</label>
                        <input type="text" class="form-control" id="department_name" name="department_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="hod_id" class="form-label">Assign HOD</label>
                        <select class="form-select" id="hod_id" name="hod_id">
                            <option value="">Select HOD</option>
                            <?php
                            $result = $conn->query("SELECT id, full_name FROM users WHERE role = 'HOD' ORDER BY full_name");
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['full_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Department</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Add Department Modal -->
<?php require_once 'includes/footer.php'; ?>

