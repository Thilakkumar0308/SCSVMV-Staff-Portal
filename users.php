<?php
$page_title = 'User Management';
require_once 'includes/header.php';

// Require admin or HOD role
if (!has_role('Admin') && !has_role('HOD')) {
    redirect('dashboard.php');
}


$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add') {
            $username = sanitize_input($_POST['username']);
            $password = $_POST['password'];
            $email = sanitize_input($_POST['email']);
            $role = $_POST['role'];
            $full_name = sanitize_input($_POST['full_name']);
            
            // Check if username already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $message = 'Username already exists';
                $message_type = 'danger';
            } else {
                // Check if email already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $message = 'Email already exists';
                    $message_type = 'danger';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, full_name) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $username, $hashed_password, $email, $role, $full_name);
                    
                    if ($stmt->execute()) {
                        $message = 'User created successfully';
                        $message_type = 'success';
                    } else {
                        $message = 'Error creating user: ' . $conn->error;
                        $message_type = 'danger';
                    }
                }
            }
        }
        
        elseif ($action == 'edit') {
            $id = $_POST['id'];
            $username = sanitize_input($_POST['username']);
            $email = sanitize_input($_POST['email']);
            $role = $_POST['role'];
            $full_name = sanitize_input($_POST['full_name']);
            $password = $_POST['password'];
            
            // Check if username already exists (excluding current user)
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->bind_param("si", $username, $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $message = 'Username already exists';
                $message_type = 'danger';
            } else {
                // Check if email already exists (excluding current user)
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->bind_param("si", $email, $id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $message = 'Email already exists';
                    $message_type = 'danger';
                } else {
                    if (!empty($password)) {
                        // Update with new password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET username=?, password=?, email=?, role=?, full_name=? WHERE id=?");
                        $stmt->bind_param("sssssi", $username, $hashed_password, $email, $role, $full_name, $id);
                    } else {
                        // Update without changing password
                        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=?, full_name=? WHERE id=?");
                        $stmt->bind_param("ssssi", $username, $email, $role, $full_name, $id);
                    }
                    
                    if ($stmt->execute()) {
                        $message = 'User updated successfully';
                        $message_type = 'success';
                    } else {
                        $message = 'Error updating user: ' . $conn->error;
                        $message_type = 'danger';
                    }
                }
            }
        }
        
        elseif ($action == 'delete') {
            $id = $_POST['id'];
            
            // Prevent deleting own account
            if ($id == $_SESSION['user_id']) {
                $message = 'You cannot delete your own account';
                $message_type = 'danger';
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $message = 'User deleted successfully';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting user: ' . $conn->error;
                    $message_type = 'danger';
                }
            }
        }
    }
}

// 🔹 Department filter (used when coming from HOD dashboard)
$users = [];

if (isset($_GET['department']) && is_numeric($_GET['department'])) {
    $department_filter = (int) $_GET['department'];
    // Prevent HODs from viewing other departments
    if (has_role('HOD')) {
        // Get HOD's actual department from DB
        $stmt = $conn->prepare("SELECT department_id FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $hod_dept = $stmt->get_result()->fetch_assoc()['department_id'] ?? null;

        if ($department_filter !== (int)$hod_dept) {
            // Redirect or show error
            redirect('users.php?department=' . urlencode($hod_dept));
            exit;
        }
    }

    // Only show teachers from that department
    $stmt = $conn->prepare("SELECT * FROM users WHERE role = 'Teacher' AND department_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $department_filter);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Default: show all users (admin view)
    $result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
}

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">User Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus me-1"></i>Add User
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

    <!-- Users Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="usersTable">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                    <span class="badge bg-info ms-1">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $user['role'] == 'Admin' ? 'danger' : 
                                        ($user['role'] == 'HOD' ? 'warning' : 
                                        ($user['role'] == 'Teacher' ? 'primary' : 'success')); ?>">
                                    <?php echo $user['role']; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['updated_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role *</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="Admin">Admin</option>
                            <option value="HOD">HOD</option>
                            <option value="Teacher">Teacher</option>
                            <option value="Student">Student</option>
                            <option value="DeptAdmin">DeptAdmin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        <div class="form-text">Password must be at least 6 characters long.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role *</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="Admin">Admin</option>
                            <option value="HOD">HOD</option>
                            <option value="Teacher">Teacher</option>
                            <option value="Student">Student</option>
                            <option value="DeptAdmin">DeptAdmin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="edit_password" name="password" minlength="6">
                        <div class="form-text">Leave blank to keep current password. Must be at least 6 characters long if provided.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
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
                Are you sure you want to delete this user? This action cannot be undone.
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
function editUser(user) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_password').value = '';
    
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function deleteUser(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Password validation
document.getElementById('password').addEventListener('input', function() {
    var password = this.value;
    if (password.length > 0 && password.length < 6) {
        this.setCustomValidity('Password must be at least 6 characters long');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('edit_password').addEventListener('input', function() {
    var password = this.value;
    if (password.length > 0 && password.length < 6) {
        this.setCustomValidity('Password must be at least 6 characters long');
    } else {
        this.setCustomValidity('');
    }
});

// Username validation (alphanumeric only)
document.getElementById('username').addEventListener('input', function() {
    this.value = this.value.replace(/[^a-zA-Z0-9_]/g, '');
});

document.getElementById('edit_username').addEventListener('input', function() {
    this.value = this.value.replace(/[^a-zA-Z0-9_]/g, '');
});

// Initialize DataTable
$(document).ready(function() {
    $('#usersTable').DataTable({
        "pageLength": 25,
        "order": [[ 0, "asc" ]],
        "columnDefs": [
            { "orderable": false, "targets": 6 }
        ]
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
