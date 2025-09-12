<?php
$page_title = 'Attendance Management';
require_once 'includes/header.php';

// Check if user has permission (Admin, DeptAdmin, or HOD)
if (!has_role('Admin') && !has_role('DeptAdmin') && !has_role('HOD')) {
    redirect('dashboard.php');
}

$message = '';
$message_type = '';

// Get user's department (for HOD)
$user_department = null;
if (has_role('HOD')) {
    $stmt = $conn->prepare("SELECT department_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_department = $row['department_id'];
    }
}

// Get filter parameters
$class_filter = isset($_GET['class']) ? $_GET['class'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$subject_filter = isset($_GET['subject']) ? $_GET['subject'] : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'mark_attendance') {
            $class_id = $_POST['class_id'];
            $subject_id = $_POST['subject_id'];
            $attendance_date = $_POST['attendance_date'];
            $attendance_data = $_POST['attendance'];
            
            $success_count = 0;
            $error_count = 0;
            
            foreach ($attendance_data as $student_id => $status) {
                $remarks = isset($_POST['remarks'][$student_id]) ? sanitize_input($_POST['remarks'][$student_id]) : '';
                
                // Check if attendance already exists for this student, class, date, and subject
                $stmt = $conn->prepare("SELECT id FROM attendance WHERE student_id = ? AND class_id = ? AND attendance_date = ? AND subject_id = ?");
                $stmt->bind_param("iisi", $student_id, $class_id, $attendance_date, $subject_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Update existing attendance
                    $stmt = $conn->prepare("UPDATE attendance SET status = ?, remarks = ?, marked_by = ?, updated_at = NOW() WHERE student_id = ? AND class_id = ? AND attendance_date = ? AND subject_id = ?");
                    $stmt->bind_param("ssiisi", $status, $remarks, $_SESSION['user_id'], $student_id, $class_id, $attendance_date, $subject_id);
                } else {
                    // Insert new attendance
                    $stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, subject_id, attendance_date, status, remarks, marked_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iissssi", $student_id, $class_id, $subject_id, $attendance_date, $status, $remarks, $_SESSION['user_id']);
                }
                
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
            
            if ($error_count == 0) {
                $message = "Attendance marked successfully for $success_count students";
                $message_type = 'success';
            } else {
                $message = "Attendance marked for $success_count students, $error_count errors occurred";
                $message_type = 'warning';
            }
        }
    }
}

// Get classes based on user role
if (has_role('Admin')) {
    $query = "
        SELECT c.*, d.department_name
        FROM classes c 
        LEFT JOIN departments d ON c.department_id = d.id
        ORDER BY c.class_name, c.section
    ";
    $result = $conn->query($query);
} else {
    $stmt = $conn->prepare("
        SELECT c.*, d.department_name
        FROM classes c 
        LEFT JOIN departments d ON c.department_id = d.id
        WHERE c.department_id = ?
        ORDER BY c.class_name, c.section
    ");
    $stmt->bind_param("i", $user_department);
    $stmt->execute();
    $result = $stmt->get_result();
}

$classes = [];
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

// Get subjects for selected class
$subjects = [];
if ($class_filter) {
    $stmt = $conn->prepare("
        SELECT s.* FROM subjects s 
        WHERE s.class_id = ? 
        ORDER BY s.subject_name
    ");
    $stmt->bind_param("i", $class_filter);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// Get students for selected class
$students = [];
if ($class_filter) {
    $stmt = $conn->prepare("
        SELECT s.* FROM students s 
        WHERE s.class_id = ? AND s.status = 'Active'
        ORDER BY s.first_name, s.last_name
    ");
    $stmt->bind_param("i", $class_filter);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Get existing attendance for the selected date and class
$existing_attendance = [];
if ($class_filter && $date_filter && $subject_filter) {
    $stmt = $conn->prepare("
        SELECT student_id, status, remarks 
        FROM attendance 
        WHERE class_id = ? AND attendance_date = ? AND subject_id = ?
    ");
    $stmt->bind_param("isi", $class_filter, $date_filter, $subject_filter);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $existing_attendance[$row['student_id']] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Attendance Management</h1>
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
                    <label for="class" class="form-label">Class *</label>
                    <select class="form-select" id="class" name="class" required onchange="this.form.submit()">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php echo $class_filter == $class['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section']); ?>
                            <?php if (has_role('Admin')): ?>
                            - <?php echo htmlspecialchars($class['department_name']); ?>
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="subject" class="form-label">Subject</label>
                    <select class="form-select" id="subject" name="subject" onchange="this.form.submit()">
                        <option value="">All Subjects</option>
                        <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['id']; ?>" <?php echo $subject_filter == $subject['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date" class="form-label">Date *</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>" required onchange="this.form.submit()">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Load Attendance</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($class_filter && !empty($students)): ?>
    <!-- Attendance Form -->
    <div class="card shadow">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-check-circle me-2"></i>Mark Attendance
                <small class="text-muted">
                    - <?php echo htmlspecialchars($classes[array_search($class_filter, array_column($classes, 'id'))]['class_name'] . ' ' . $classes[array_search($class_filter, array_column($classes, 'id'))]['section']); ?>
                    - <?php echo date('M d, Y', strtotime($date_filter)); ?>
                </small>
            </h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="mark_attendance">
                <input type="hidden" name="class_id" value="<?php echo $class_filter; ?>">
                <input type="hidden" name="subject_id" value="<?php echo $subject_filter; ?>">
                <input type="hidden" name="attendance_date" value="<?php echo $date_filter; ?>">
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <?php 
                            $existing = isset($existing_attendance[$student['id']]) ? $existing_attendance[$student['id']] : null;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                <td>
                                    <select class="form-select form-select-sm" name="attendance[<?php echo $student['id']; ?>]" required>
                                        <option value="Present" <?php echo ($existing && $existing['status'] == 'Present') ? 'selected' : ''; ?>>Present</option>
                                        <option value="Absent" <?php echo ($existing && $existing['status'] == 'Absent') ? 'selected' : ''; ?>>Absent</option>
                                        <option value="Late" <?php echo ($existing && $existing['status'] == 'Late') ? 'selected' : ''; ?>>Late</option>
                                        <option value="Excused" <?php echo ($existing && $existing['status'] == 'Excused') ? 'selected' : ''; ?>>Excused</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="remarks[<?php echo $student['id']; ?>]" 
                                           value="<?php echo htmlspecialchars($existing ? $existing['remarks'] : ''); ?>" 
                                           placeholder="Optional remarks...">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Save Attendance
                    </button>
                    <a href="attendance.php" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php elseif ($class_filter && empty($students)): ?>
    <!-- No Students -->
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-users fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No Students Found</h5>
            <p class="text-muted">No active students found in the selected class.</p>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Select Class -->
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-chalkboard-teacher fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Select a Class</h5>
            <p class="text-muted">Please select a class to mark attendance.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Auto-submit form when class or date changes
document.getElementById('class').addEventListener('change', function() {
    if (this.value) {
        this.form.submit();
    }
});

document.getElementById('date').addEventListener('change', function() {
    if (this.value && document.getElementById('class').value) {
        this.form.submit();
    }
});

// Quick attendance marking
function markAllPresent() {
    document.querySelectorAll('select[name^="attendance"]').forEach(select => {
        select.value = 'Present';
    });
}

function markAllAbsent() {
    document.querySelectorAll('select[name^="attendance"]').forEach(select => {
        select.value = 'Absent';
    });
}

// Add quick action buttons if students are loaded
<?php if ($class_filter && !empty($students)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const quickActions = document.createElement('div');
    quickActions.className = 'mb-3';
    quickActions.innerHTML = `
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-success btn-sm" onclick="markAllPresent()">
                <i class="fas fa-check me-1"></i>Mark All Present
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="markAllAbsent()">
                <i class="fas fa-times me-1"></i>Mark All Absent
            </button>
        </div>
    `;
    form.insertBefore(quickActions, form.firstElementChild);
});
<?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>
