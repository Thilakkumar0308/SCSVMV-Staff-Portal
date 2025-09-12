<?php
$page_title = 'System Settings';
require_once 'includes/header.php';

// Require admin role
require_role('Admin');

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'update_settings') {
            $school_name = sanitize_input($_POST['school_name']);
            $school_address = sanitize_input($_POST['school_address']);
            $academic_year = sanitize_input($_POST['academic_year']);
            $max_leave_days = (int)$_POST['max_leave_days'];
            $attendance_threshold = (int)$_POST['attendance_threshold'];
            
            // Validate inputs
            if ($max_leave_days < 1 || $max_leave_days > 365) {
                $message = 'Maximum leave days must be between 1 and 365';
                $message_type = 'danger';
            } elseif ($attendance_threshold < 1 || $attendance_threshold > 100) {
                $message = 'Attendance threshold must be between 1 and 100';
                $message_type = 'danger';
            } else {
                $settings = [
                    'school_name' => $school_name,
                    'school_address' => $school_address,
                    'academic_year' => $academic_year,
                    'max_leave_days' => $max_leave_days,
                    'attendance_threshold' => $attendance_threshold
                ];
                
                $success = true;
                foreach ($settings as $key => $value) {
                    if (!update_setting($key, $value)) {
                        $success = false;
                        break;
                    }
                }
                
                if ($success) {
                    $message = 'Settings updated successfully';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating settings';
                    $message_type = 'danger';
                }
            }
        }
        
        elseif ($action == 'add_class') {
            $class_name = sanitize_input($_POST['class_name']);
            $section = sanitize_input($_POST['section']);
            $academic_year = sanitize_input($_POST['academic_year']);
            
            $stmt = $conn->prepare("INSERT INTO classes (class_name, section, academic_year) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $class_name, $section, $academic_year);
            
            if ($stmt->execute()) {
                $message = 'Class added successfully';
                $message_type = 'success';
            } else {
                $message = 'Error adding class: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'edit_class') {
            $id = $_POST['id'];
            $class_name = sanitize_input($_POST['class_name']);
            $section = sanitize_input($_POST['section']);
            $academic_year = sanitize_input($_POST['academic_year']);
            
            $stmt = $conn->prepare("UPDATE classes SET class_name=?, section=?, academic_year=? WHERE id=?");
            $stmt->bind_param("sssi", $class_name, $section, $academic_year, $id);
            
            if ($stmt->execute()) {
                $message = 'Class updated successfully';
                $message_type = 'success';
            } else {
                $message = 'Error updating class: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'delete_class') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = 'Class deleted successfully';
                $message_type = 'success';
            } else {
                $message = 'Error deleting class: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'add_subject') {
            $subject_name = sanitize_input($_POST['subject_name']);
            $subject_code = sanitize_input($_POST['subject_code']);
            $class_id = $_POST['class_id'];
            
            $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, class_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $subject_name, $subject_code, $class_id);
            
            if ($stmt->execute()) {
                $message = 'Subject added successfully';
                $message_type = 'success';
            } else {
                $message = 'Error adding subject: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'edit_subject') {
            $id = $_POST['id'];
            $subject_name = sanitize_input($_POST['subject_name']);
            $subject_code = sanitize_input($_POST['subject_code']);
            $class_id = $_POST['class_id'];
            
            $stmt = $conn->prepare("UPDATE subjects SET subject_name=?, subject_code=?, class_id=? WHERE id=?");
            $stmt->bind_param("ssii", $subject_name, $subject_code, $class_id, $id);
            
            if ($stmt->execute()) {
                $message = 'Subject updated successfully';
                $message_type = 'success';
            } else {
                $message = 'Error updating subject: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'delete_subject') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = 'Subject deleted successfully';
                $message_type = 'success';
            } else {
                $message = 'Error deleting subject: ' . $conn->error;
                $message_type = 'danger';
            }
        }
    }
}

// Get current settings
$current_settings = [
    'school_name' => get_setting('school_name', 'ABC School'),
    'school_address' => get_setting('school_address', '123 Education Street, City, State'),
    'academic_year' => get_setting('academic_year', '2024-2025'),
    'max_leave_days' => get_setting('max_leave_days', '30'),
    'attendance_threshold' => get_setting('attendance_threshold', '75')
];

// Get classes
$classes = [];
$result = $conn->query("SELECT * FROM classes ORDER BY class_name, section");
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

// Get subjects with class information
$subjects = [];
$result = $conn->query("
    SELECT s.*, c.class_name, c.section 
    FROM subjects s 
    LEFT JOIN classes c ON s.class_id = c.id 
    ORDER BY s.subject_name
");
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">System Settings</h1>
            </div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Settings Tabs -->
    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                <i class="fas fa-cog me-1"></i>General Settings
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="classes-tab" data-bs-toggle="tab" data-bs-target="#classes" type="button" role="tab">
                <i class="fas fa-chalkboard-teacher me-1"></i>Classes
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="subjects-tab" data-bs-toggle="tab" data-bs-target="#subjects" type="button" role="tab">
                <i class="fas fa-book me-1"></i>Subjects
            </button>
        </li>
    </ul>

    <div class="tab-content" id="settingsTabsContent">
        <!-- General Settings Tab -->
        <div class="tab-pane fade show active" id="general" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_settings">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">School Information</h5>
                                <div class="mb-3">
                                    <label for="school_name" class="form-label">School Name *</label>
                                    <input type="text" class="form-control" id="school_name" name="school_name" 
                                           value="<?php echo htmlspecialchars($current_settings['school_name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="school_address" class="form-label">School Address *</label>
                                    <textarea class="form-control" id="school_address" name="school_address" rows="3" required><?php echo htmlspecialchars($current_settings['school_address']); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="academic_year" class="form-label">Academic Year *</label>
                                    <input type="text" class="form-control" id="academic_year" name="academic_year" 
                                           value="<?php echo htmlspecialchars($current_settings['academic_year']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-3">System Configuration</h5>
                                <div class="mb-3">
                                    <label for="max_leave_days" class="form-label">Maximum Leave Days per Year</label>
                                    <input type="number" class="form-control" id="max_leave_days" name="max_leave_days" 
                                           value="<?php echo $current_settings['max_leave_days']; ?>" min="1" max="365">
                                    <div class="form-text">Maximum number of leave days allowed per student per academic year.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="attendance_threshold" class="form-label">Attendance Threshold (%)</label>
                                    <input type="number" class="form-control" id="attendance_threshold" name="attendance_threshold" 
                                           value="<?php echo $current_settings['attendance_threshold']; ?>" min="1" max="100">
                                    <div class="form-text">Minimum attendance percentage required for students.</div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Save Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Classes Tab -->
        <div class="tab-pane fade" id="classes" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Manage Classes</h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addClassModal">
                        <i class="fas fa-plus me-1"></i>Add Class
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="classesTable">
                            <thead>
                                <tr>
                                    <th>Class Name</th>
                                    <th>Section</th>
                                    <th>Academic Year</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($class['section']); ?></td>
                                    <td><?php echo htmlspecialchars($class['academic_year']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($class['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editClass(<?php echo htmlspecialchars(json_encode($class)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteClass(<?php echo $class['id']; ?>)">
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

        <!-- Subjects Tab -->
        <div class="tab-pane fade" id="subjects" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Manage Subjects</h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="fas fa-plus me-1"></i>Add Subject
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="subjectsTable">
                            <thead>
                                <tr>
                                    <th>Subject Name</th>
                                    <th>Subject Code</th>
                                    <th>Class</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['class_name'] . ' ' . $subject['section']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($subject['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editSubject(<?php echo htmlspecialchars(json_encode($subject)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSubject(<?php echo $subject['id']; ?>)">
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
    </div>
</div>

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_class">
                    <div class="mb-3">
                        <label for="class_name" class="form-label">Class Name *</label>
                        <input type="text" class="form-control" id="class_name" name="class_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="section" class="form-label">Section *</label>
                        <input type="text" class="form-control" id="section" name="section" required>
                    </div>
                    <div class="mb-3">
                        <label for="academic_year" class="form-label">Academic Year *</label>
                        <input type="text" class="form-control" id="academic_year" name="academic_year" 
                               value="<?php echo $current_settings['academic_year']; ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Class Modal -->
<div class="modal fade" id="editClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_class">
                    <input type="hidden" name="id" id="edit_class_id">
                    <div class="mb-3">
                        <label for="edit_class_name" class="form-label">Class Name *</label>
                        <input type="text" class="form-control" id="edit_class_name" name="class_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_section" class="form-label">Section *</label>
                        <input type="text" class="form-control" id="edit_section" name="section" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_academic_year" class="form-label">Academic Year *</label>
                        <input type="text" class="form-control" id="edit_academic_year" name="academic_year" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_subject">
                    <div class="mb-3">
                        <label for="subject_name" class="form-label">Subject Name *</label>
                        <input type="text" class="form-control" id="subject_name" name="subject_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="subject_code" class="form-label">Subject Code *</label>
                        <input type="text" class="form-control" id="subject_code" name="subject_code" required>
                    </div>
                    <div class="mb-3">
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_subject">
                    <input type="hidden" name="id" id="edit_subject_id">
                    <div class="mb-3">
                        <label for="edit_subject_name" class="form-label">Subject Name *</label>
                        <input type="text" class="form-control" id="edit_subject_name" name="subject_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_subject_code" class="form-label">Subject Code *</label>
                        <input type="text" class="form-control" id="edit_subject_code" name="subject_code" required>
                    </div>
                    <div class="mb-3">
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modals -->
<div class="modal fade" id="deleteClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this class? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_class">
                    <input type="hidden" name="id" id="delete_class_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this subject? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_subject">
                    <input type="hidden" name="id" id="delete_subject_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editClass(classData) {
    document.getElementById('edit_class_id').value = classData.id;
    document.getElementById('edit_class_name').value = classData.class_name;
    document.getElementById('edit_section').value = classData.section;
    document.getElementById('edit_academic_year').value = classData.academic_year;
    
    new bootstrap.Modal(document.getElementById('editClassModal')).show();
}

function deleteClass(id) {
    document.getElementById('delete_class_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteClassModal')).show();
}

function editSubject(subjectData) {
    document.getElementById('edit_subject_id').value = subjectData.id;
    document.getElementById('edit_subject_name').value = subjectData.subject_name;
    document.getElementById('edit_subject_code').value = subjectData.subject_code;
    document.getElementById('edit_class_id').value = subjectData.class_id;
    
    new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
}

function deleteSubject(id) {
    document.getElementById('delete_subject_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteSubjectModal')).show();
}

// Subject code validation (uppercase)
document.getElementById('subject_code').addEventListener('input', function() {
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
});

document.getElementById('edit_subject_code').addEventListener('input', function() {
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
});

// Initialize DataTables
$(document).ready(function() {
    $('#classesTable').DataTable({
        "pageLength": 25,
        "order": [[ 0, "asc" ]],
        "columnDefs": [
            { "orderable": false, "targets": 4 }
        ]
    });
    
    $('#subjectsTable').DataTable({
        "pageLength": 25,
        "order": [[ 0, "asc" ]],
        "columnDefs": [
            { "orderable": false, "targets": 4 }
        ]
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>

