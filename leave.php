<?php
$page_title = 'Leave Management';
require_once 'includes/header.php';

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add') {
            $student_id = $_POST['student_id'];
            $leave_type = $_POST['leave_type'];
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $reason = sanitize_input($_POST['reason']);
            
            $stmt = $conn->prepare("INSERT INTO leave_requests (student_id, leave_type, start_date, end_date, reason) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $student_id, $leave_type, $start_date, $end_date, $reason);
            
            if ($stmt->execute()) {
                $message = 'Leave request submitted successfully';
                $message_type = 'success';
            } else {
                $message = 'Error submitting leave request: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'approve') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("UPDATE leave_requests SET status = 'Approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->bind_param("ii", $_SESSION['user_id'], $id);
            
            if ($stmt->execute()) {
                $message = 'Leave request approved successfully';
                $message_type = 'success';
            } else {
                $message = 'Error approving leave request: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'reject') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("UPDATE leave_requests SET status = 'Rejected', approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->bind_param("ii", $_SESSION['user_id'], $id);
            
            if ($stmt->execute()) {
                $message = 'Leave request rejected successfully';
                $message_type = 'success';
            } else {
                $message = 'Error rejecting leave request: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'delete') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM leave_requests WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = 'Leave request deleted successfully';
                $message_type = 'success';
            } else {
                $message = 'Error deleting leave request: ' . $conn->error;
                $message_type = 'danger';
            }
        }
    }
}

// Get leave requests with student information
$leave_requests = [];
$query = "
    SELECT lr.*, s.first_name, s.last_name, s.student_id, s.class_id, c.class_name, c.section,
           u.full_name as approved_by_name
    FROM leave_requests lr 
    JOIN students s ON lr.student_id = s.id 
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN users u ON lr.approved_by = u.id
    ORDER BY lr.created_at DESC
";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $leave_requests[] = $row;
}

// Get students for dropdown
$students = [];
$result = $conn->query("SELECT id, first_name, last_name, student_id FROM students WHERE status = 'Active' ORDER BY first_name, last_name");
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

// Filter options
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Leave Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLeaveModal">
                        <i class="fas fa-plus me-1"></i>New Leave Request
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo $status_filter == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?php echo $status_filter == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label">Leave Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">All Types</option>
                        <option value="Medical" <?php echo $type_filter == 'Medical' ? 'selected' : ''; ?>>Medical</option>
                        <option value="Personal" <?php echo $type_filter == 'Personal' ? 'selected' : ''; ?>>Personal</option>
                        <option value="Emergency" <?php echo $type_filter == 'Emergency' ? 'selected' : ''; ?>>Emergency</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary">Filter</button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <a href="leave.php" class="btn btn-outline-secondary">Clear Filters</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Leave Requests Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="leaveTable">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Class</th>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days</th>
                            <th>Status</th>
                            <th>Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leave_requests as $request): ?>
                        <?php 
                        // Apply filters
                        if ($status_filter && $request['status'] != $status_filter) continue;
                        if ($type_filter && $request['leave_type'] != $type_filter) continue;
                        
                        $start_date = new DateTime($request['start_date']);
                        $end_date = new DateTime($request['end_date']);
                        $days = $start_date->diff($end_date)->days + 1;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($request['class_name'] . ' ' . $request['section']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $request['leave_type'] == 'Medical' ? 'danger' : ($request['leave_type'] == 'Personal' ? 'info' : 'warning'); ?>">
                                    <?php echo $request['leave_type']; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($request['start_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($request['end_date'])); ?></td>
                            <td><?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $request['status'] == 'Approved' ? 'success' : ($request['status'] == 'Rejected' ? 'danger' : 'warning'); ?>">
                                    <?php echo $request['status']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?php echo htmlspecialchars($request['reason']); ?>">
                                    <?php echo htmlspecialchars($request['reason']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($request['status'] == 'Pending' && (has_role('Admin') || has_role('Teacher'))): ?>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-success" onclick="approveLeave(<?php echo $request['id']; ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="rejectLeave(<?php echo $request['id']; ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($request['status'] != 'Pending' || has_role('Admin')): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteLeave(<?php echo $request['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($request['approved_by_name']): ?>
                                <small class="text-muted d-block">
                                    By: <?php echo htmlspecialchars($request['approved_by_name']); ?>
                                </small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Leave Request Modal -->
<div class="modal fade" id="addLeaveModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Leave Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="student_id" class="form-label">Student *</label>
                            <select class="form-select" id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_id'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="leave_type" class="form-label">Leave Type *</label>
                            <select class="form-select" id="leave_type" name="leave_type" required>
                                <option value="">Select Type</option>
                                <option value="Medical">Medical</option>
                                <option value="Personal">Personal</option>
                                <option value="Emergency">Emergency</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date *</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">End Date *</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason *</label>
                        <textarea class="form-control" id="reason" name="reason" rows="4" required placeholder="Please provide a detailed reason for the leave request..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Approve Leave Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Leave Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to approve this leave request?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="id" id="approve_id">
                    <button type="submit" class="btn btn-success">Approve</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Reject Leave Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Leave Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to reject this leave request?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="id" id="reject_id">
                    <button type="submit" class="btn btn-danger">Reject</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this leave request? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function approveLeave(id) {
    document.getElementById('approve_id').value = id;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function rejectLeave(id) {
    document.getElementById('reject_id').value = id;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function deleteLeave(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Date validation
document.getElementById('start_date').addEventListener('change', function() {
    var startDate = new Date(this.value);
    var endDateInput = document.getElementById('end_date');
    
    if (endDateInput.value) {
        var endDate = new Date(endDateInput.value);
        if (endDate < startDate) {
            endDateInput.value = this.value;
        }
    }
    
    endDateInput.min = this.value;
});

document.getElementById('end_date').addEventListener('change', function() {
    var endDate = new Date(this.value);
    var startDateInput = document.getElementById('start_date');
    var startDate = new Date(startDateInput.value);
    
    if (endDate < startDate) {
        alert('End date cannot be before start date');
        this.value = startDateInput.value;
    }
});

// Initialize DataTable
$(document).ready(function() {
    $('#leaveTable').DataTable({
        "pageLength": 25,
        "order": [[ 0, "desc" ]],
        "columnDefs": [
            { "orderable": false, "targets": 9 }
        ]
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>

