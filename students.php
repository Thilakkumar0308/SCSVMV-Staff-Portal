<?php
$page_title = 'Student Management';
require_once 'includes/header.php';

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add') {
            $student_id = sanitize_input($_POST['student_id']);
            $first_name = sanitize_input($_POST['first_name']);
            $last_name = sanitize_input($_POST['last_name']);
            $email = sanitize_input($_POST['email']);
            $phone = sanitize_input($_POST['phone']);
            $date_of_birth = $_POST['date_of_birth'];
            $gender = $_POST['gender'];
            $address = sanitize_input($_POST['address']);
            $class_id = $_POST['class_id'];
            $parent_name = sanitize_input($_POST['parent_name']);
            $parent_phone = sanitize_input($_POST['parent_phone']);
            $admission_date = $_POST['admission_date'];
            
            // Check if student ID already exists
            $stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
            $stmt->bind_param("s", $student_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $message = 'Student ID already exists';
                $message_type = 'danger';
            } else {
                $stmt = $conn->prepare("INSERT INTO students (student_id, first_name, last_name, email, phone, date_of_birth, gender, address, class_id, parent_name, parent_phone, admission_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssisss", $student_id, $first_name, $last_name, $email, $phone, $date_of_birth, $gender, $address, $class_id, $parent_name, $parent_phone, $admission_date);
                
                if ($stmt->execute()) {
                    $message = 'Student added successfully';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding student: ' . $conn->error;
                    $message_type = 'danger';
                }
            }
        }
        
        elseif ($action == 'edit') {
            $id = $_POST['id'];
            $student_id = sanitize_input($_POST['student_id']);
            $first_name = sanitize_input($_POST['first_name']);
            $last_name = sanitize_input($_POST['last_name']);
            $email = sanitize_input($_POST['email']);
            $phone = sanitize_input($_POST['phone']);
            $date_of_birth = $_POST['date_of_birth'];
            $gender = $_POST['gender'];
            $address = sanitize_input($_POST['address']);
            $class_id = $_POST['class_id'];
            $parent_name = sanitize_input($_POST['parent_name']);
            $parent_phone = sanitize_input($_POST['parent_phone']);
            $admission_date = $_POST['admission_date'];
            $status = $_POST['status'];
            
            $stmt = $conn->prepare("UPDATE students SET student_id=?, first_name=?, last_name=?, email=?, phone=?, date_of_birth=?, gender=?, address=?, class_id=?, parent_name=?, parent_phone=?, admission_date=?, status=? WHERE id=?");
            $stmt->bind_param("ssssssssissssi", $student_id, $first_name, $last_name, $email, $phone, $date_of_birth, $gender, $address, $class_id, $parent_name, $parent_phone, $admission_date, $status, $id);
            
            if ($stmt->execute()) {
                $message = 'Student updated successfully';
                $message_type = 'success';
            } else {
                $message = 'Error updating student: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'delete') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = 'Student deleted successfully';
                $message_type = 'success';
            } else {
                $message = 'Error deleting student: ' . $conn->error;
                $message_type = 'danger';
            }
        }
    }
}

// Get students with class information
$students = [];
$result = $conn->query("
    SELECT s.*, c.class_name, c.section 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    ORDER BY s.created_at DESC
");
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

// Get classes for dropdown
$classes = [];
$result = $conn->query("SELECT * FROM classes ORDER BY class_name, section");
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Student Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="fas fa-plus me-1"></i>Add Student
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

    <!-- Students Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="studentsTable">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Class</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['phone']); ?></td>
                            <td><?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $student['status'] == 'Active' ? 'success' : ($student['status'] == 'Inactive' ? 'warning' : 'info'); ?>">
                                    <?php echo $student['status']; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteStudent(<?php echo $student['id']; ?>)">
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

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="student_id" class="form-label">Student ID *</label>
                            <input type="text" class="form-control" id="student_id" name="student_id" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="class_id" class="form-label">Class *</label>
                            <select class="form-select" id="class_id" name="class_id" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="parent_name" class="form-label">Parent/Guardian Name</label>
                            <input type="text" class="form-control" id="parent_name" name="parent_name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="parent_phone" class="form-label">Parent/Guardian Phone</label>
                            <input type="tel" class="form-control" id="parent_phone" name="parent_phone">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="admission_date" class="form-label">Admission Date</label>
                        <input type="date" class="form-control" id="admission_date" name="admission_date">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_student_id" class="form-label">Student ID *</label>
                            <input type="text" class="form-control" id="edit_student_id" name="student_id" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_class_id" class="form-label">Class *</label>
                            <select class="form-select" id="edit_class_id" name="class_id" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="edit_date_of_birth" name="date_of_birth">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_gender" class="form-label">Gender</label>
                            <select class="form-select" id="edit_gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Address</label>
                        <textarea class="form-control" id="edit_address" name="address" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_parent_name" class="form-label">Parent/Guardian Name</label>
                            <input type="text" class="form-control" id="edit_parent_name" name="parent_name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_parent_phone" class="form-label">Parent/Guardian Phone</label>
                            <input type="tel" class="form-control" id="edit_parent_phone" name="parent_phone">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_admission_date" class="form-label">Admission Date</label>
                            <input type="date" class="form-control" id="edit_admission_date" name="admission_date">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Graduated">Graduated</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Student</button>
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
                Are you sure you want to delete this student? This action cannot be undone.
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
function editStudent(student) {
    document.getElementById('edit_id').value = student.id;
    document.getElementById('edit_student_id').value = student.student_id;
    document.getElementById('edit_first_name').value = student.first_name;
    document.getElementById('edit_last_name').value = student.last_name;
    document.getElementById('edit_email').value = student.email;
    document.getElementById('edit_phone').value = student.phone;
    document.getElementById('edit_date_of_birth').value = student.date_of_birth;
    document.getElementById('edit_gender').value = student.gender;
    document.getElementById('edit_address').value = student.address;
    document.getElementById('edit_class_id').value = student.class_id;
    document.getElementById('edit_parent_name').value = student.parent_name;
    document.getElementById('edit_parent_phone').value = student.parent_phone;
    document.getElementById('edit_admission_date').value = student.admission_date;
    document.getElementById('edit_status').value = student.status;
    
    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
}

function deleteStudent(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Initialize DataTable
$(document).ready(function() {
    $('#studentsTable').DataTable({
        "pageLength": 25,
        "order": [[ 0, "desc" ]]
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>

