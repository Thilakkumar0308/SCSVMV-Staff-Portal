<?php
$page_title = 'Timetable Management';
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add') {
            $class_id = $_POST['class_id'];
            $subject_id = $_POST['subject_id'];
            $teacher_id = $_POST['teacher_id'];
            $day_of_week = $_POST['day_of_week'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $room = sanitize_input($_POST['room']);
            $academic_year = sanitize_input($_POST['academic_year']);
            
            $stmt = $conn->prepare("INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, start_time, end_time, room, academic_year, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisssssi", $class_id, $subject_id, $teacher_id, $day_of_week, $start_time, $end_time, $room, $academic_year, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $message = 'Timetable entry added successfully';
                $message_type = 'success';
            } else {
                $message = 'Error adding timetable entry: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'edit') {
            $id = $_POST['id'];
            $class_id = $_POST['class_id'];
            $subject_id = $_POST['subject_id'];
            $teacher_id = $_POST['teacher_id'];
            $day_of_week = $_POST['day_of_week'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $room = sanitize_input($_POST['room']);
            $academic_year = sanitize_input($_POST['academic_year']);
            
            $stmt = $conn->prepare("UPDATE timetable SET class_id=?, subject_id=?, teacher_id=?, day_of_week=?, start_time=?, end_time=?, room=?, academic_year=? WHERE id=?");
            $stmt->bind_param("iiisssssi", $class_id, $subject_id, $teacher_id, $day_of_week, $start_time, $end_time, $room, $academic_year, $id);
            
            if ($stmt->execute()) {
                $message = 'Timetable entry updated successfully';
                $message_type = 'success';
            } else {
                $message = 'Error updating timetable entry: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        elseif ($action == 'delete') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM timetable WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = 'Timetable entry deleted successfully';
                $message_type = 'success';
            } else {
                $message = 'Error deleting timetable entry: ' . $conn->error;
                $message_type = 'danger';
            }
        }
    }
}

// Get filter parameters
$class_filter = isset($_GET['class']) ? $_GET['class'] : '';
$day_filter = isset($_GET['day']) ? $_GET['day'] : '';

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

// Get timetable entries
$timetable_entries = [];
$where_conditions = [];
$params = [];
$types = '';

if ($class_filter) {
    $where_conditions[] = "t.class_id = ?";
    $params[] = $class_filter;
    $types .= 'i';
}

if ($day_filter) {
    $where_conditions[] = "t.day_of_week = ?";
    $params[] = $day_filter;
    $types .= 's';
}

$query = "
    SELECT t.*, c.class_name, c.section, d.department_name, s.subject_name, u.full_name as teacher_name
    FROM timetable t
    LEFT JOIN classes c ON t.class_id = c.id
    LEFT JOIN departments d ON c.department_id = d.id
    LEFT JOIN subjects s ON t.subject_id = s.id
    LEFT JOIN users u ON t.teacher_id = u.id
";

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY t.day_of_week, t.start_time";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

while ($row = $result->fetch_assoc()) {
    $timetable_entries[] = $row;
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

// Get teachers
$teachers = [];
$result = $conn->query("SELECT id, full_name FROM users WHERE role = 'Teacher' ORDER BY full_name");
while ($row = $result->fetch_assoc()) {
    $teachers[] = $row;
}

// Days of week
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Timetable Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTimetableModal">
                        <i class="fas fa-plus me-1"></i>Add Timetable Entry
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
                <div class="col-md-4">
                    <label for="class" class="form-label">Class</label>
                    <select class="form-select" id="class" name="class" onchange="this.form.submit()">
                        <option value="">All Classes</option>
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
                <div class="col-md-4">
                    <label for="day" class="form-label">Day of Week</label>
                    <select class="form-select" id="day" name="day" onchange="this.form.submit()">
                        <option value="">All Days</option>
                        <?php foreach ($days as $day): ?>
                        <option value="<?php echo $day; ?>" <?php echo $day_filter == $day ? 'selected' : ''; ?>>
                            <?php echo $day; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <a href="timetable.php" class="btn btn-outline-secondary">Clear Filters</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Timetable Display -->
    <div class="card shadow">
        <div class="card-body">
            <?php if (empty($timetable_entries)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Timetable Entries</h5>
                    <p class="text-muted">No timetable entries found for the selected criteria.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped" id="timetableTable">
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Room</th>
                                <th>Academic Year</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($timetable_entries as $entry): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($entry['class_name'] . ' ' . $entry['section']); ?></strong>
                                    <?php if (has_role('Admin')): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($entry['department_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($entry['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($entry['teacher_name'] ?: 'Not Assigned'); ?></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $entry['day_of_week']; ?></span>
                                </td>
                                <td>
                                    <?php echo date('g:i A', strtotime($entry['start_time'])); ?> - 
                                    <?php echo date('g:i A', strtotime($entry['end_time'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($entry['room'] ?: 'Not Specified'); ?></td>
                                <td><?php echo htmlspecialchars($entry['academic_year']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="editTimetable(<?php echo htmlspecialchars(json_encode($entry)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deleteTimetable(<?php echo $entry['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
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

<!-- Add Timetable Modal -->
<div class="modal fade" id="addTimetableModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Timetable Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="class_id" class="form-label">Class *</label>
                            <select class="form-select" id="class_id" name="class_id" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section']); ?>
                                    <?php if (has_role('Admin')): ?>
                                    - <?php echo htmlspecialchars($class['department_name']); ?>
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="subject_id" class="form-label">Subject *</label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>">
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="teacher_id" class="form-label">Teacher</label>
                            <select class="form-select" id="teacher_id" name="teacher_id">
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['full_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="day_of_week" class="form-label">Day of Week *</label>
                            <select class="form-select" id="day_of_week" name="day_of_week" required>
                                <option value="">Select Day</option>
                                <?php foreach ($days as $day): ?>
                                <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="start_time" class="form-label">Start Time *</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="end_time" class="form-label">End Time *</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="room" class="form-label">Room</label>
                            <input type="text" class="form-control" id="room" name="room" placeholder="e.g., Room 101">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="academic_year" class="form-label">Academic Year *</label>
                        <input type="text" class="form-control" id="academic_year" name="academic_year" 
                               value="<?php echo get_setting('academic_year', '2024-2025'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Timetable Modal -->
<div class="modal fade" id="editTimetableModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Timetable Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_class_id" class="form-label">Class *</label>
                            <select class="form-select" id="edit_class_id" name="class_id" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section']); ?>
                                    <?php if (has_role('Admin')): ?>
                                    - <?php echo htmlspecialchars($class['department_name']); ?>
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_subject_id" class="form-label">Subject *</label>
                            <select class="form-select" id="edit_subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>">
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_teacher_id" class="form-label">Teacher</label>
                            <select class="form-select" id="edit_teacher_id" name="teacher_id">
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['full_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_day_of_week" class="form-label">Day of Week *</label>
                            <select class="form-select" id="edit_day_of_week" name="day_of_week" required>
                                <option value="">Select Day</option>
                                <?php foreach ($days as $day): ?>
                                <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_start_time" class="form-label">Start Time *</label>
                            <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_end_time" class="form-label">End Time *</label>
                            <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_room" class="form-label">Room</label>
                            <input type="text" class="form-control" id="edit_room" name="room" placeholder="e.g., Room 101">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_academic_year" class="form-label">Academic Year *</label>
                        <input type="text" class="form-control" id="edit_academic_year" name="academic_year" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Entry</button>
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
                Are you sure you want to delete this timetable entry? This action cannot be undone.
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
function editTimetable(entry) {
    document.getElementById('edit_id').value = entry.id;
    document.getElementById('edit_class_id').value = entry.class_id;
    document.getElementById('edit_subject_id').value = entry.subject_id;
    document.getElementById('edit_teacher_id').value = entry.teacher_id || '';
    document.getElementById('edit_day_of_week').value = entry.day_of_week;
    document.getElementById('edit_start_time').value = entry.start_time;
    document.getElementById('edit_end_time').value = entry.end_time;
    document.getElementById('edit_room').value = entry.room || '';
    document.getElementById('edit_academic_year').value = entry.academic_year;
    
    new bootstrap.Modal(document.getElementById('editTimetableModal')).show();
}

function deleteTimetable(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Update subjects when class changes
document.getElementById('class_id').addEventListener('change', function() {
    const classId = this.value;
    const subjectSelect = document.getElementById('subject_id');
    
    // Clear existing options
    subjectSelect.innerHTML = '<option value="">Select Subject</option>';
    
    if (classId) {
        // Fetch subjects for selected class
        fetch(`ajax/get_subjects.php?class_id=${classId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(subject => {
                    const option = document.createElement('option');
                    option.value = subject.id;
                    option.textContent = subject.subject_name;
                    subjectSelect.appendChild(option);
                });
            });
    }
});

// Time validation
document.getElementById('start_time').addEventListener('change', function() {
    const startTime = this.value;
    const endTimeInput = document.getElementById('end_time');
    
    if (endTimeInput.value && endTimeInput.value <= startTime) {
        endTimeInput.value = '';
    }
});

document.getElementById('end_time').addEventListener('change', function() {
    const endTime = this.value;
    const startTimeInput = document.getElementById('start_time');
    const startTime = startTimeInput.value;
    
    if (startTime && endTime <= startTime) {
        alert('End time must be after start time');
        this.value = '';
    }
});

// Initialize DataTable
$(document).ready(function() {
    $('#timetableTable').DataTable({
        "pageLength": 25,
        "order": [[ 3, "asc" ], [ 4, "asc" ]],
        "columnDefs": [
            { "orderable": false, "targets": 7 }
        ]
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
