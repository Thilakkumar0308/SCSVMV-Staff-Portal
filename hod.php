<?php
$page_title = 'HOD Dashboard';
require_once 'includes/header.php';

// Check if user has HOD role
if (!has_role('HOD') && !has_role('Admin')) {
    redirect('dashboard.php');
}

$message = '';
$message_type = '';

// Get HOD's department
$hod_department = null;
$stmt = $conn->prepare("SELECT department_id FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $hod_department = $row['department_id'];
}

// Get HOD statistics
$stats = [];

// Total students in HOD's department
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM students s 
    JOIN classes c ON s.class_id = c.id 
    WHERE c.department_id = ?
");
$stmt->bind_param("i", $hod_department);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_students'] = $result->fetch_assoc()['total'];

// Pending leave requests for HOD's department
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM leave_requests lr 
    JOIN students s ON lr.student_id = s.id 
    JOIN classes c ON s.class_id = c.id 
    WHERE c.department_id = ? AND lr.status = 'Pending'
");
$stmt->bind_param("i", $hod_department);
$stmt->execute();
$result = $stmt->get_result();
$stats['pending_leaves'] = $result->fetch_assoc()['total'];

// Pending on-duty requests for HOD's department
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM onduty_requests odr 
    JOIN students s ON odr.student_id = s.id 
    JOIN classes c ON s.class_id = c.id 
    WHERE c.department_id = ? AND odr.status = 'Pending'
");
$stmt->bind_param("i", $hod_department);
$stmt->execute();
$result = $stmt->get_result();
$stats['pending_onduty'] = $result->fetch_assoc()['total'];

// Recent disciplinary actions in HOD's department
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM disciplinary_actions da 
    JOIN students s ON da.student_id = s.id 
    JOIN classes c ON s.class_id = c.id 
    WHERE c.department_id = ? AND da.status = 'Active'
");
$stmt->bind_param("i", $hod_department);
$stmt->execute();
$result = $stmt->get_result();
$stats['active_disciplinary'] = $result->fetch_assoc()['total'];

// Get recent leave requests for HOD's department
$recent_leaves = [];
$stmt = $conn->prepare("
    SELECT lr.*, s.first_name, s.last_name, s.student_id, c.class_name, c.section
    FROM leave_requests lr 
    JOIN students s ON lr.student_id = s.id 
    JOIN classes c ON s.class_id = c.id 
    WHERE c.department_id = ?
    ORDER BY lr.created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $hod_department);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_leaves[] = $row;
}

// Get recent on-duty requests for HOD's department
$recent_onduty = [];
$stmt = $conn->prepare("
    SELECT odr.*, s.first_name, s.last_name, s.student_id, c.class_name, c.section
    FROM onduty_requests odr 
    JOIN students s ON odr.student_id = s.id 
    JOIN classes c ON s.class_id = c.id 
    WHERE c.department_id = ?
    ORDER BY odr.created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $hod_department);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_onduty[] = $row;
}

// Get department performance (average marks)
$stmt = $conn->prepare("
    SELECT AVG((m.marks_obtained / m.total_marks) * 100) as avg_percentage
    FROM marks m 
    JOIN students s ON m.student_id = s.id 
    JOIN classes c ON s.class_id = c.id 
    WHERE c.department_id = ?
");
$stmt->bind_param("i", $hod_department);
$stmt->execute();
$result = $stmt->get_result();
$stats['department_avg'] = $result->fetch_assoc()['avg_percentage'];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-user-tie me-2"></i>HOD Dashboard
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download me-1"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- HOD Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Department Students
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['total_students']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Leaves
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['pending_leaves']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-times fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Pending On-Duty
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['pending_onduty']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Department Average
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['department_avg'], 1); ?>%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Leave Requests -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Leave Requests</h6>
                    <a href="leave.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_leaves)): ?>
                        <p class="text-muted">No recent leave requests</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Class</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_leaves as $leave): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($leave['class_name'] . ' ' . $leave['section']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $leave['leave_type'] == 'Medical' ? 'danger' : ($leave['leave_type'] == 'Personal' ? 'info' : 'warning'); ?>">
                                                <?php echo $leave['leave_type']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d', strtotime($leave['start_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $leave['status'] == 'Approved' ? 'success' : ($leave['status'] == 'Rejected' ? 'danger' : 'warning'); ?>">
                                                <?php echo $leave['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($leave['status'] == 'Pending'): ?>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-success" onclick="approveLeave(<?php echo $leave['id']; ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="rejectLeave(<?php echo $leave['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent On-Duty Requests -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent On-Duty Requests</h6>
                    <a href="onduty.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_onduty)): ?>
                        <p class="text-muted">No recent on-duty requests</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Class</th>
                                        <th>Event</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_onduty as $onduty): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($onduty['first_name'] . ' ' . $onduty['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($onduty['class_name'] . ' ' . $onduty['section']); ?></td>
                                        <td><?php echo htmlspecialchars($onduty['event_name']); ?></td>
                                        <td><?php echo date('M d', strtotime($onduty['event_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $onduty['status'] == 'Approved' ? 'success' : ($onduty['status'] == 'Rejected' ? 'danger' : 'warning'); ?>">
                                                <?php echo $onduty['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($onduty['status'] == 'Pending'): ?>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-success" onclick="approveOnduty(<?php echo $onduty['id']; ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="rejectOnduty(<?php echo $onduty['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="students.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-users me-2"></i>Manage Students
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="leave.php" class="btn btn-outline-warning w-100">
                                <i class="fas fa-calendar-times me-2"></i>Leave Requests
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="onduty.php" class="btn btn-outline-info w-100">
                                <i class="fas fa-calendar-check me-2"></i>On-Duty Requests
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="reports.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-chart-bar me-2"></i>Department Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function approveLeave(id) {
    if (confirm('Approve this leave request?')) {
        // AJAX call to approve leave
        fetch('ajax/approve_leave.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&action=approve'
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function rejectLeave(id) {
    if (confirm('Reject this leave request?')) {
        // AJAX call to reject leave
        fetch('ajax/approve_leave.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&action=reject'
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function approveOnduty(id) {
    if (confirm('Approve this on-duty request?')) {
        // AJAX call to approve on-duty
        fetch('ajax/approve_onduty.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&action=approve'
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function rejectOnduty(id) {
    if (confirm('Reject this on-duty request?')) {
        // AJAX call to reject on-duty
        fetch('ajax/approve_onduty.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&action=reject'
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
