<?php
$page_title = 'On-Duty Management';
require_once 'includes/header.php';

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add') {
            $student_id = $_POST['student_id'];
            $event_name = sanitize_input($_POST['event_name']);
            $event_date = $_POST['event_date'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $venue = sanitize_input($_POST['venue']);
            $reason = sanitize_input($_POST['reason']);
            
            $stmt = $conn->prepare("INSERT INTO onduty_requests (student_id, event_name, event_date, start_time, end_time, venue, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $student_id, $event_name, $event_date, $start_time, $end_time, $venue, $reason);
            
            if ($stmt->execute()) {
                $message = 'On-duty request submitted successfully';
                $message_type = 'success';
            } else {
                $message = 'Error submitting on-duty request: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'approve') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("UPDATE onduty_requests SET status = 'Approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->bind_param("ii", $_SESSION['user_id'], $id);
            
            if ($stmt->execute()) {
                $message = 'On-duty request approved successfully';
                $message_type = 'success';
            } else {
                $message = 'Error approving on-duty request: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'reject') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("UPDATE onduty_requests SET status = 'Rejected', approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->bind_param("ii", $_SESSION['user_id'], $id);
            
            if ($stmt->execute()) {
                $message = 'On-duty request rejected successfully';
                $message_type = 'success';
            } else {
                $message = 'Error rejecting on-duty request: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'delete') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM onduty_requests WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = 'On-duty request deleted successfully';
                $message_type = 'success';
            } else {
                $message = 'Error deleting on-duty request: ' . $conn->error;
                $message_type = 'danger';
            }
        }
    }
}

// Get on-duty requests with student information
$onduty_requests = [];
$query = "
    SELECT odr.*, s.first_name, s.last_name, s.student_id, s.class_id, c.class_name, c.section,
           u.full_name as approved_by_name
    FROM onduty_requests odr 
    JOIN students s ON odr.student_id = s.id 
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN users u ON odr.approved_by = u.id
    ORDER BY odr.created_at DESC
";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $onduty_requests[] = $row;
}

// Get students for dropdown
$students = [];
$result = $conn->query("SELECT id, first_name, last_name, student_id FROM students WHERE status = 'Active' ORDER BY first_name, last_name");
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

// Filter options
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">On-Duty Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOndutyModal">
                        <i class="fas fa-plus me-1"></i>New On-Duty Request
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
                    <label for="date" class="form-label">Event Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
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
                        <a href="onduty.php" class="btn btn-outline-secondary">Clear Filters</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- On-Duty Requests Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="ondutyTable">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Class</th>
                            <th>Event Name</th>
                            <th>Event Date</th>
                            <th>Time</th>
                            <th>Venue</th>
                            <th>Status</th>
                            <th>Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($onduty_requests as $request): ?>
                        <?php 
                        // Apply filters
                        if ($status_filter && $request['status'] != $status_filter) continue;
                        if ($date_filter && $request['event_date'] != $date_filter) continue;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($request['class_name'] . ' ' . $request['section']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($request['event_name']); ?></strong>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($request['event_date'])); ?></td>
                            <td>
                                <?php echo date('g:i A', strtotime($request['start_time'])); ?> - 
                                <?php echo date('g:i A', strtotime($request['end_time'])); ?>
                            </td>
                            <td><?php echo htmlspecialchars($request['venue']); ?></td>
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
                                    <button class="btn btn-outline-success" onclick="approveOnduty(<?php echo $request['id']; ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="rejectOnduty(<?php echo $request['id']; ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($request['status'] != 'Pending' || has_role('Admin')): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteOnduty(<?php echo $request['id']; ?>)">
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

<!-- Add On-Duty Request Modal -->
<div class="modal fade" id="addOndutyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New On-Duty Request</h5>
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
                            <label for="event_name" class="form-label">Event Name *</label>
                            <input type="text" class="form-control" id="event_name" name="event_name" required placeholder="e.g., Sports Competition, Cultural Event">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="event_date" class="form-label">Event Date *</label>
                            <input type="date" class="form-control" id="event_date" name="event_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="venue" class="form-label">Venue *</label>
                            <input type="text" class="form-control" id="venue" name="venue" required placeholder="e.g., School Auditorium, Sports Complex">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_time" class="form-label">Start Time *</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_time" class="form-label">End Time *</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason *</label>
                        <textarea class="form-control" id="reason" name="reason" rows="4" required placeholder="Please provide a detailed reason for the on-duty request..."></textarea>
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

<!-- Approve On-Duty Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve On-Duty Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to approve this on-duty request?
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

<!-- Reject On-Duty Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject On-Duty Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to reject this on-duty request?
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
                Are you sure you want to delete this on-duty request? This action cannot be undone.
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
function approveOnduty(id) {
    document.getElementById('approve_id').value = id;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function rejectOnduty(id) {
    document.getElementById('reject_id').value = id;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function deleteOnduty(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Time validation
document.getElementById('start_time').addEventListener('change', function() {
    var startTime = this.value;
    var endTimeInput = document.getElementById('end_time');
    
    if (endTimeInput.value && endTimeInput.value <= startTime) {
        alert('End time must be after start time');
        endTimeInput.value = '';
    }
});

document.getElementById('end_time').addEventListener('change', function() {
    var endTime = this.value;
    var startTimeInput = document.getElementById('start_time');
    var startTime = startTimeInput.value;
    
    if (startTime && endTime <= startTime) {
        alert('End time must be after start time');
        this.value = '';
    }
});

// Date validation
document.getElementById('event_date').addEventListener('change', function() {
    var eventDate = new Date(this.value);
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (eventDate < today) {
        alert('Event date cannot be in the past');
        this.value = '';
    }
});

// Initialize DataTable
$(document).ready(function() {
    $('#ondutyTable').DataTable({
        "pageLength": 25,
        "order": [[ 0, "desc" ]],
        "columnDefs": [
            { "orderable": false, "targets": 9 }
        ]
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>

