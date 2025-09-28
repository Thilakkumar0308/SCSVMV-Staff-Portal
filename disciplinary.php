<?php
$page_title = 'Disciplinary Management';
require_once 'includes/header.php';

$allowed = has_any_role(['HOD','Admin']);
if (!$allowed) { redirect('dashboard.php'); }

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add') {
            if (!has_any_role(['HOD','Admin'])) { redirect('dashboard.php'); }
            $student_id = $_POST['student_id'];
            $action_type = $_POST['action_type'];
            $description = sanitize_input($_POST['description']);
            $action_date = $_POST['action_date'];
            $status = $_POST['status'];
            
            $stmt = $conn->prepare("INSERT INTO disciplinary_actions (student_id, action_type, description, action_date, imposed_by, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssis", $student_id, $action_type, $description, $action_date, $_SESSION['user_id'], $status);
            
            if ($stmt->execute()) {
                $message = 'Disciplinary action recorded successfully';
                $message_type = 'success';
            } else {
                $message = 'Error recording disciplinary action: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'edit') {
            if (!has_any_role(['HOD','Admin'])) { redirect('dashboard.php'); }
            $id = $_POST['id'];
            $action_type = $_POST['action_type'];
            $description = sanitize_input($_POST['description']);
            $action_date = $_POST['action_date'];
            $status = $_POST['status'];
            
            $stmt = $conn->prepare("UPDATE disciplinary_actions SET action_type=?, description=?, action_date=?, status=? WHERE id=?");
            $stmt->bind_param("ssssi", $action_type, $description, $action_date, $status, $id);
            
            if ($stmt->execute()) {
                $message = 'Disciplinary action updated successfully';
                $message_type = 'success';
            } else {
                $message = 'Error updating disciplinary action: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'delete') {
            if (!has_any_role(['HOD','Admin'])) { redirect('dashboard.php'); }
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM disciplinary_actions WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = 'Disciplinary action deleted successfully';
                $message_type = 'success';
            } else {
                $message = 'Error deleting disciplinary action: ' . $conn->error;
                $message_type = 'danger';
            }
        }
    }
}

// Get disciplinary actions with student information
$disciplinary_actions = [];
$query = "
    SELECT da.*, s.first_name, s.last_name, s.student_id, s.class_id, c.class_name, c.section,
           u.full_name as imposed_by_name
    FROM disciplinary_actions da 
    JOIN students s ON da.student_id = s.id 
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN users u ON da.imposed_by = u.id
    ORDER BY da.created_at DESC
";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $disciplinary_actions[] = $row;
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
                <h1 class="h2">Disciplinary Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDisciplinaryModal">
                        <i class="fas fa-plus me-1"></i>Record Action
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
                        <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Resolved" <?php echo $status_filter == 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="Expired" <?php echo $status_filter == 'Expired' ? 'selected' : ''; ?>>Expired</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label">Action Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">All Types</option>
                        <option value="Warning" <?php echo $type_filter == 'Warning' ? 'selected' : ''; ?>>Warning</option>
                        <option value="Suspension" <?php echo $type_filter == 'Suspension' ? 'selected' : ''; ?>>Suspension</option>
                        <option value="Expulsion" <?php echo $type_filter == 'Expulsion' ? 'selected' : ''; ?>>Expulsion</option>
                        <option value="Detention" <?php echo $type_filter == 'Detention' ? 'selected' : ''; ?>>Detention</option>
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
                        <a href="disciplinary.php" class="btn btn-outline-secondary">Clear Filters</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Disciplinary Actions Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="disciplinaryTable">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Class</th>
                            <th>Action Type</th>
                            <th>Description</th>
                            <th>Action Date</th>
                            <th>Status</th>
                            <th>Imposed By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($disciplinary_actions as $action): ?>
                        <?php 
                        // Apply filters
                        if ($status_filter && $action['status'] != $status_filter) continue;
                        if ($type_filter && $action['action_type'] != $type_filter) continue;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($action['first_name'] . ' ' . $action['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($action['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($action['class_name'] . ' ' . $action['section']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $action['action_type'] == 'Warning' ? 'warning' : 
                                        ($action['action_type'] == 'Suspension' ? 'danger' : 
                                        ($action['action_type'] == 'Expulsion' ? 'dark' : 'info')); 
                                ?>">
                                    <?php echo $action['action_type']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?php echo htmlspecialchars($action['description']); ?>">
                                    <?php echo htmlspecialchars($action['description']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($action['action_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $action['status'] == 'Active' ? 'danger' : ($action['status'] == 'Resolved' ? 'success' : 'secondary'); ?>">
                                    <?php echo $action['status']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($action['imposed_by_name']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="editDisciplinary(<?php echo htmlspecialchars(json_encode($action)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteDisciplinary(<?php echo $action['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Disciplinary Action Modal -->
<div class="modal fade" id="addDisciplinaryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Disciplinary Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="add-form add-record-form">
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
                            <label for="action_type" class="form-label">Action Type *</label>
                            <select class="form-select" id="action_type" name="action_type" required>
                                <option value="">Select Type</option>
                                <option value="Warning">Warning</option>
                                <option value="Suspension">Suspension</option>
                                <option value="Expulsion">Expulsion</option>
                                <option value="Detention">Detention</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="action_date" class="form-label">Action Date *</label>
                            <input type="date" class="form-control" id="action_date" name="action_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Expired">Expired</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required placeholder="Please provide a detailed description of the disciplinary action..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Action</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Disciplinary Action Modal -->
<div class="modal fade" id="editDisciplinaryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Disciplinary Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_student_id" class="form-label">Student *</label>
                            <select class="form-select" id="edit_student_id" name="student_id" required disabled>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_id'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_action_type" class="form-label">Action Type *</label>
                            <select class="form-select" id="edit_action_type" name="action_type" required>
                                <option value="">Select Type</option>
                                <option value="Warning">Warning</option>
                                <option value="Suspension">Suspension</option>
                                <option value="Expulsion">Expulsion</option>
                                <option value="Detention">Detention</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_action_date" class="form-label">Action Date *</label>
                            <input type="date" class="form-control" id="edit_action_date" name="action_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_status" class="form-label">Status *</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Expired">Expired</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description *</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="4" required placeholder="Please provide a detailed description of the disciplinary action..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Action</button>
                </div>
            </form>
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
                Are you sure you want to delete this disciplinary action? This action cannot be undone.
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
function editDisciplinary(action) {
    document.getElementById('edit_id').value = action.id;
    document.getElementById('edit_student_id').value = action.student_id;
    document.getElementById('edit_action_type').value = action.action_type;
    document.getElementById('edit_action_date').value = action.action_date;
    document.getElementById('edit_status').value = action.status;
    document.getElementById('edit_description').value = action.description;
    
    new bootstrap.Modal(document.getElementById('editDisciplinaryModal')).show();
}

function deleteDisciplinary(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Date validation - Allow today's date but not future dates
document.getElementById('action_date').addEventListener('change', function() {
    var actionDate = new Date(this.value);
    var today = new Date();
    today.setHours(23, 59, 59, 999); // Set to end of today to allow today's date
    
    if (actionDate > today) {
        alert('Action date cannot be in the future');
        this.value = '';
    }
});

document.getElementById('edit_action_date').addEventListener('change', function() {
    var actionDate = new Date(this.value);
    var today = new Date();
    today.setHours(23, 59, 59, 999); // Set to end of today to allow today's date
    
    if (actionDate > today) {
        alert('Action date cannot be in the future');
        this.value = '';
    }
});

// Initialize DataTable
$(document).ready(function() {
    $('#disciplinaryTable').DataTable({
        "pageLength": 25,
        "order": [[ 0, "desc" ]],
        "columnDefs": [
            { "orderable": false, "targets": 8 }
        ]
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>

