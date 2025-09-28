<?php
$page_title = 'Marks & Leaderboard';
require_once 'includes/header.php';

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add') {
            $student_id = $_POST['student_id'];
            $subject_id = $_POST['subject_id'];
            $exam_type = $_POST['exam_type'];
            $marks_obtained = $_POST['marks_obtained'];
            $total_marks = $_POST['total_marks'];
            $exam_date = $_POST['exam_date'];
            $remarks = sanitize_input($_POST['remarks']);
            
            // Validate marks
            if ($marks_obtained > $total_marks) {
                $message = 'Marks obtained cannot be greater than total marks';
                $message_type = 'danger';
            } else {
                $stmt = $conn->prepare("INSERT INTO marks (student_id, subject_id, exam_type, marks_obtained, total_marks, exam_date, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisddssi", $student_id, $subject_id, $exam_type, $marks_obtained, $total_marks, $exam_date, $remarks, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $message = 'Marks added successfully';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding marks: ' . $conn->error;
                    $message_type = 'danger';
                }
            }
        }
        
        elseif ($action == 'edit') {
            $id = $_POST['id'];
            $subject_id = $_POST['subject_id'];
            $exam_type = $_POST['exam_type'];
            $marks_obtained = $_POST['marks_obtained'];
            $total_marks = $_POST['total_marks'];
            $exam_date = $_POST['exam_date'];
            $remarks = sanitize_input($_POST['remarks']);
            
            // Validate marks
            if ($marks_obtained > $total_marks) {
                $message = 'Marks obtained cannot be greater than total marks';
                $message_type = 'danger';
            } else {
                $stmt = $conn->prepare("UPDATE marks SET subject_id=?, exam_type=?, marks_obtained=?, total_marks=?, exam_date=?, remarks=? WHERE id=?");
                $stmt->bind_param("isddssi", $subject_id, $exam_type, $marks_obtained, $total_marks, $exam_date, $remarks, $id);
                
                if ($stmt->execute()) {
                    $message = 'Marks updated successfully';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating marks: ' . $conn->error;
                    $message_type = 'danger';
                }
            }
        }
        
        elseif ($action == 'delete') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM marks WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = 'Marks deleted successfully';
                $message_type = 'success';
            } else {
                $message = 'Error deleting marks: ' . $conn->error;
                $message_type = 'danger';
            }
        }
    }
}

// Get marks with student and subject information
$marks = [];
$query = "
    SELECT m.*, s.first_name, s.last_name, s.student_id, s.class_id, c.class_name, c.section,
           sub.subject_name, sub.subject_code, u.full_name as created_by_name
    FROM marks m 
    JOIN students s ON m.student_id = s.id 
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN subjects sub ON m.subject_id = sub.id
    LEFT JOIN users u ON m.created_by = u.id
    ORDER BY m.created_at DESC
";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $marks[] = $row;
}

// Get students for dropdown
$students = [];
$result = $conn->query("SELECT id, first_name, last_name, student_id FROM students WHERE status = 'Active' ORDER BY first_name, last_name");
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

// Get subjects for dropdown
$subjects = [];
$result = $conn->query("SELECT id, subject_name, subject_code FROM subjects ORDER BY subject_name");
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

// Get leaderboard data
$leaderboard = [];
$result = $conn->query("
    SELECT s.id, s.first_name, s.last_name, s.student_id, s.class_id, c.class_name, c.section,
           AVG((m.marks_obtained / m.total_marks) * 100) as avg_percentage,
           COUNT(m.id) as total_exams
    FROM students s 
    LEFT JOIN marks m ON s.id = m.student_id 
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE s.status = 'Active'
    GROUP BY s.id 
    HAVING total_exams > 0
    ORDER BY avg_percentage DESC
");
while ($row = $result->fetch_assoc()) {
    $leaderboard[] = $row;
}

// Filter options
$class_filter = isset($_GET['class']) ? $_GET['class'] : '';
$subject_filter = isset($_GET['subject']) ? $_GET['subject'] : '';
$exam_filter = isset($_GET['exam']) ? $_GET['exam'] : '';

// Get classes for filter
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
                <h1 class="h2">Marks & Leaderboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMarksModal">
                        <i class="fas fa-plus me-1"></i>Add Marks
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
                    <label for="class" class="form-label">Class</label>
                    <select class="form-select" id="class" name="class">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php echo $class_filter == $class['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="subject" class="form-label">Subject</label>
                    <select class="form-select" id="subject" name="subject">
                        <option value="">All Subjects</option>
                        <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['id']; ?>" <?php echo $subject_filter == $subject['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="exam" class="form-label">Exam Type</label>
                    <select class="form-select" id="exam" name="exam">
                        <option value="">All Types</option>
                        <option value="Quiz" <?php echo $exam_filter == 'Quiz' ? 'selected' : ''; ?>>Quiz</option>
                        <option value="Midterm" <?php echo $exam_filter == 'Midterm' ? 'selected' : ''; ?>>Midterm</option>
                        <option value="Final" <?php echo $exam_filter == 'Final' ? 'selected' : ''; ?>>Final</option>
                        <option value="Assignment" <?php echo $exam_filter == 'Assignment' ? 'selected' : ''; ?>>Assignment</option>
                        <option value="Project" <?php echo $exam_filter == 'Project' ? 'selected' : ''; ?>>Project</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Leaderboard -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-trophy me-2"></i>Academic Leaderboard
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($leaderboard)): ?>
                        <p class="text-muted text-center">No marks data available for leaderboard</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Student</th>
                                        <th>Student ID</th>
                                        <th>Class</th>
                                        <th>Average %</th>
                                        <th>Total Exams</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($leaderboard as $index => $student): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?php echo $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : ($index == 2 ? 'info' : 'light')); ?> fs-6">
                                                #<?php echo $index + 1; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $student['avg_percentage'] >= 90 ? 'success' : ($student['avg_percentage'] >= 80 ? 'info' : ($student['avg_percentage'] >= 70 ? 'warning' : 'danger')); ?>">
                                                <?php echo number_format($student['avg_percentage'], 1); ?>%
                                            </span>
                                        </td>
                                        <td><?php echo $student['total_exams']; ?></td>
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

    <!-- Marks Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="marksTable">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Exam Type</th>
                            <th>Marks</th>
                            <th>Percentage</th>
                            <th>Exam Date</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($marks as $mark): ?>
                        <?php 
                        // Apply filters
                        if ($class_filter && $mark['class_id'] != $class_filter) continue;
                        if ($subject_filter && $mark['subject_id'] != $subject_filter) continue;
                        if ($exam_filter && $mark['exam_type'] != $exam_filter) continue;
                        
                        $percentage = ($mark['marks_obtained'] / $mark['total_marks']) * 100;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($mark['first_name'] . ' ' . $mark['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($mark['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($mark['class_name'] . ' ' . $mark['section']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($mark['subject_name']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($mark['subject_code']); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $mark['exam_type'] == 'Final' ? 'danger' : 
                                        ($mark['exam_type'] == 'Midterm' ? 'warning' : 
                                        ($mark['exam_type'] == 'Quiz' ? 'info' : 'secondary')); 
                                ?>">
                                    <?php echo $mark['exam_type']; ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo $mark['marks_obtained']; ?></strong> / <?php echo $mark['total_marks']; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $percentage >= 90 ? 'success' : ($percentage >= 80 ? 'info' : ($percentage >= 70 ? 'warning' : 'danger')); ?>">
                                    <?php echo number_format($percentage, 1); ?>%
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($mark['exam_date'])); ?></td>
                            <td>
                                <span class="text-truncate d-inline-block" style="max-width: 150px;" title="<?php echo htmlspecialchars($mark['remarks']); ?>">
                                    <?php echo htmlspecialchars($mark['remarks']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="editMarks(<?php echo htmlspecialchars(json_encode($mark)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteMarks(<?php echo $mark['id']; ?>)">
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

<!-- Add Marks Modal -->
<div class="modal fade" id="addMarksModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Marks</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="add-form add-marks-form">
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
                            <label for="subject_id" class="form-label">Subject *</label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>">
                                    <?php echo htmlspecialchars($subject['subject_name'] . ' (' . $subject['subject_code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="exam_type" class="form-label">Exam Type *</label>
                            <select class="form-select" id="exam_type" name="exam_type" required>
                                <option value="">Select Type</option>
                                <option value="Quiz">Quiz</option>
                                <option value="Midterm">Midterm</option>
                                <option value="Final">Final</option>
                                <option value="Assignment">Assignment</option>
                                <option value="Project">Project</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="exam_date" class="form-label">Exam Date *</label>
                            <input type="date" class="form-control" id="exam_date" name="exam_date" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="marks_obtained" class="form-label">Marks Obtained *</label>
                            <input type="number" class="form-control" id="marks_obtained" name="marks_obtained" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="total_marks" class="form-label">Total Marks *</label>
                            <input type="number" class="form-control" id="total_marks" name="total_marks" step="0.01" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Any additional remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Marks</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Marks Modal -->
<div class="modal fade" id="editMarksModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Marks</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="add-form edit-marks-form">
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
                            <label for="edit_subject_id" class="form-label">Subject *</label>
                            <select class="form-select" id="edit_subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>">
                                    <?php echo htmlspecialchars($subject['subject_name'] . ' (' . $subject['subject_code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_exam_type" class="form-label">Exam Type *</label>
                            <select class="form-select" id="edit_exam_type" name="exam_type" required>
                                <option value="">Select Type</option>
                                <option value="Quiz">Quiz</option>
                                <option value="Midterm">Midterm</option>
                                <option value="Final">Final</option>
                                <option value="Assignment">Assignment</option>
                                <option value="Project">Project</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_exam_date" class="form-label">Exam Date *</label>
                            <input type="date" class="form-control" id="edit_exam_date" name="exam_date" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_marks_obtained" class="form-label">Marks Obtained *</label>
                            <input type="number" class="form-control" id="edit_marks_obtained" name="marks_obtained" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_total_marks" class="form-label">Total Marks *</label>
                            <input type="number" class="form-control" id="edit_total_marks" name="total_marks" step="0.01" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="edit_remarks" name="remarks" rows="3" placeholder="Any additional remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Marks</button>
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
                Are you sure you want to delete this marks record? This action cannot be undone.
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
function editMarks(mark) {
    document.getElementById('edit_id').value = mark.id;
    document.getElementById('edit_student_id').value = mark.student_id;
    document.getElementById('edit_subject_id').value = mark.subject_id;
    document.getElementById('edit_exam_type').value = mark.exam_type;
    document.getElementById('edit_exam_date').value = mark.exam_date;
    document.getElementById('edit_marks_obtained').value = mark.marks_obtained;
    document.getElementById('edit_total_marks').value = mark.total_marks;
    document.getElementById('edit_remarks').value = mark.remarks;
    
    new bootstrap.Modal(document.getElementById('editMarksModal')).show();
}

function deleteMarks(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Marks validation
document.getElementById('marks_obtained').addEventListener('input', function() {
    var marksObtained = parseFloat(this.value);
    var totalMarks = parseFloat(document.getElementById('total_marks').value);
    
    if (totalMarks && marksObtained > totalMarks) {
        this.setCustomValidity('Marks obtained cannot be greater than total marks');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('total_marks').addEventListener('input', function() {
    var totalMarks = parseFloat(this.value);
    var marksObtained = parseFloat(document.getElementById('marks_obtained').value);
    
    if (marksObtained && marksObtained > totalMarks) {
        document.getElementById('marks_obtained').setCustomValidity('Marks obtained cannot be greater than total marks');
    } else {
        document.getElementById('marks_obtained').setCustomValidity('');
    }
});

// Edit form validation
document.getElementById('edit_marks_obtained').addEventListener('input', function() {
    var marksObtained = parseFloat(this.value);
    var totalMarks = parseFloat(document.getElementById('edit_total_marks').value);
    
    if (totalMarks && marksObtained > totalMarks) {
        this.setCustomValidity('Marks obtained cannot be greater than total marks');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('edit_total_marks').addEventListener('input', function() {
    var totalMarks = parseFloat(this.value);
    var marksObtained = parseFloat(document.getElementById('edit_marks_obtained').value);
    
    if (marksObtained && marksObtained > totalMarks) {
        document.getElementById('edit_marks_obtained').setCustomValidity('Marks obtained cannot be greater than total marks');
    } else {
        document.getElementById('edit_marks_obtained').setCustomValidity('');
    }
});

// Initialize DataTable
$(document).ready(function() {
    $('#marksTable').DataTable({
        "pageLength": 25,
        "order": [[ 0, "desc" ]],
        "columnDefs": [
            { "orderable": false, "targets": 9 }
        ]
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>

