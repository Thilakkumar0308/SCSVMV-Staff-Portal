<?php
$page_title = 'Department Management';
require_once 'includes/header.php';

// Check if user has permission (Admin or DeptAdmin)
if (!has_role('Admin') && !has_role('DeptAdmin')) {
    redirect('dashboard.php');
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add') {
            $department_name = sanitize_input($_POST['department_name']);
            $description = sanitize_input($_POST['description']);
            $hod_id = !empty($_POST['hod_id']) ? $_POST['hod_id'] : null;
            
            $stmt = $conn->prepare("INSERT INTO departments (department_name, description, hod_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $department_name, $description, $hod_id);
            
            if ($stmt->execute()) {
                $message = 'Department added successfully';
                $message_type = 'success';
            } else {
                $message = 'Error adding department: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'edit') {
            $id = $_POST['id'];
            $department_name = sanitize_input($_POST['department_name']);
            $description = sanitize_input($_POST['description']);
            $hod_id = !empty($_POST['hod_id']) ? $_POST['hod_id'] : null;
            
            $stmt = $conn->prepare("UPDATE departments SET department_name=?, description=?, hod_id=? WHERE id=?");
            $stmt->bind_param("ssii", $department_name, $description, $hod_id, $id);
            
            if ($stmt->execute()) {
                $message = 'Department updated successfully';
                $message_type = 'success';
            } else {
                $message = 'Error updating department: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'delete') {
            // Enforce: only Admins can delete departments
            if (!has_role('Admin')) {
                $message = 'Only Admin users can delete departments.';
                $message_type = 'danger';
                return;
            }
            $id = $_POST['id'];
            
            // Check if department has classes or users
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM classes WHERE department_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $class_count = $result->fetch_assoc()['count'];
            
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE department_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_count = $result->fetch_assoc()['count'];
            
            if ($class_count > 0 || $user_count > 0) {
                $message = 'Cannot delete department. It has ' . $class_count . ' classes and ' . $user_count . ' users assigned. Please reassign them first.';
                $message_type = 'warning';
            } else {
                $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $message = 'Department deleted successfully';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting department: ' . $conn->error;
                    $message_type = 'danger';
                }
            }
        }
    }
}

// ✅ FIXED: Get ALL departments with statistics (removed WHERE + LIMIT)
$query = "
    SELECT d.*, 
           u.full_name as hod_name,
           COUNT(DISTINCT c.id) as class_count,
           COUNT(DISTINCT s.id) as student_count,
           COUNT(DISTINCT u2.id) as user_count
    FROM departments d
    LEFT JOIN users u ON d.hod_id = u.id
    LEFT JOIN classes c ON d.id = c.department_id
    LEFT JOIN students s ON c.id = s.class_id AND s.status = 'Active'
    LEFT JOIN users u2 ON d.id = u2.department_id
    GROUP BY d.id
    ORDER BY d.department_name
";

$result = $conn->query($query);
$departments = [];
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

// Get all HODs for dropdown
$hod_users = [];
$result = $conn->query("SELECT id, full_name FROM users WHERE role = 'HOD' ORDER BY full_name");
while ($row = $result->fetch_assoc()) {
    $hod_users[] = $row;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Department Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                        <i class="fas fa-plus me-1"></i>Add Department
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

    <!-- Departments Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="departmentsTable">
                    <thead>
                        <tr>
                            <th>Department Name</th>
                            <th>Description</th>
                            <th>HOD</th>
                            <th>Classes</th>
                            <th>Students</th>
                            <th>Users</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($dept['department_name']); ?></strong></td>
                            <td>
                                <?php if ($dept['description']): ?>
                                    <?php echo htmlspecialchars(substr($dept['description'], 0, 50)); ?>
                                    <?php if (strlen($dept['description']) > 50): ?>...<?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">No description</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($dept['hod_name']): ?>
                                    <span class="badge bg-success"><?php echo htmlspecialchars($dept['hod_name']); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning">No HOD Assigned</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-primary"><?php echo $dept['class_count']; ?></span></td>
                            <td><span class="badge bg-info"><?php echo $dept['student_count']; ?></span></td>
                            <td><span class="badge bg-secondary"><?php echo $dept['user_count']; ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($dept['created_at'])); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="editDepartment(<?php echo htmlspecialchars(json_encode($dept)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="students.php?department=<?php echo $dept['id']; ?>" class="btn btn-outline-info" title="View Students">
                                        <i class="fas fa-users"></i>
                                    </a>
                                    <a href="class_management.php?department=<?php echo $dept['id']; ?>" class="btn btn-outline-success" title="View Classes">
                                        <i class="fas fa-chalkboard"></i>
                                    </a>
                                    <?php if (has_role('Admin')): ?>
                                    <button class="btn btn-outline-danger" onclick="deleteDepartment(<?php echo $dept['id']; ?>, '<?php echo htmlspecialchars($dept['department_name']); ?>', <?php echo $dept['class_count']; ?>, <?php echo $dept['user_count']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
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
            <form method="POST" class="add-form add-department-form">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="department_name" class="form-label">Department Name *</label>
                        <input type="text" class="form-control" id="department_name" name="department_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter department description..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="hod_id" class="form-label">Assign HOD</label>
                        <select class="form-select" id="hod_id" name="hod_id">
                            <option value="">Select HOD (Optional)</option>
                            <?php foreach ($hod_users as $hod): ?>
                            <option value="<?php echo $hod['id']; ?>">
                                <?php echo htmlspecialchars($hod['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
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

<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="add-form edit-department-form">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_department_name" class="form-label">Department Name *</label>
                        <input type="text" class="form-control" id="edit_department_name" name="department_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" placeholder="Enter department description..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_hod_id" class="form-label">Assign HOD</label>
                        <select class="form-select" id="edit_hod_id" name="hod_id">
                            <option value="">Select HOD (Optional)</option>
                            <?php foreach ($hod_users as $hod): ?>
                            <option value="<?php echo $hod['id']; ?>">
                                <?php echo htmlspecialchars($hod['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Department</button>
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
                <p>Are you sure you want to delete the department "<span id="delete_department_name"></span>"?</p>
                <div id="delete_warning" class="alert alert-warning" style="display: none;">
                    <strong>Warning:</strong> This department has <span id="delete_class_count"></span> classes and <span id="delete_user_count"></span> users assigned. You must reassign them first before deleting.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="submit" class="btn btn-danger" id="delete_confirm_btn">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editDepartment(dept) {
    document.getElementById('edit_id').value = dept.id;
    document.getElementById('edit_department_name').value = dept.department_name;
    document.getElementById('edit_description').value = dept.description || '';
    document.getElementById('edit_hod_id').value = dept.hod_id || '';
    
    new bootstrap.Modal(document.getElementById('editDepartmentModal')).show();
}

function deleteDepartment(id, name, classCount, userCount) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_department_name').textContent = name;
    
    const warningDiv = document.getElementById('delete_warning');
    const confirmBtn = document.getElementById('delete_confirm_btn');
    
    if (classCount > 0 || userCount > 0) {
        document.getElementById('delete_class_count').textContent = classCount;
        document.getElementById('delete_user_count').textContent = userCount;
        warningDiv.style.display = 'block';
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Cannot Delete';
    } else {
        warningDiv.style.display = 'none';
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Delete';
    }
    
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Initialize DataTable
$(document).ready(function() {
    $('#departmentsTable').DataTable({
        "pageLength": 25,
        "order": [[ 0, "asc" ]],
        "columnDefs": [
            { "orderable": false, "targets": 7 }
        ]
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
