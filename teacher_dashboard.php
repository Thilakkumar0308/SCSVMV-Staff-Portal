<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Check if user is logged in and is a teacher
if (!is_logged_in()) {
    redirect('index.php');
}

if (!has_role('Teacher')) {
    redirect('dashboard.php');
}

$page_title = 'Teacher Dashboard';
include 'includes/header.php';

// Get teacher's assigned classes and subjects
$teacher_id = $_SESSION['user_id'];

// Check if subject_id column exists in teacher_classes table
$check_column = $conn->query("SHOW COLUMNS FROM teacher_classes LIKE 'subject_id'");

if ($check_column->num_rows > 0) {
    // New structure with subject assignments
    $assignments_query = "
        SELECT tc.id, c.id as class_id, c.class_name, c.section, c.academic_year,
               s.id as subject_id, s.subject_name, s.subject_code,
               d.department_name, tc.created_at
        FROM teacher_classes tc
        JOIN classes c ON tc.class_id = c.id
        JOIN subjects s ON tc.subject_id = s.id
        LEFT JOIN departments d ON c.department_id = d.id
        WHERE tc.teacher_id = ?
        ORDER BY c.class_name, c.section, s.subject_name
    ";
    $stmt = $conn->prepare($assignments_query);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Fallback for old structure - show all classes but no specific subjects
    $assignments_query = "
        SELECT tc.id, c.id as class_id, c.class_name, c.section, c.academic_year,
               NULL as subject_id, 'All Subjects' as subject_name, '' as subject_code,
               d.department_name, tc.assigned_at as created_at
        FROM teacher_classes tc
        JOIN classes c ON tc.class_id = c.id
        LEFT JOIN departments d ON c.department_id = d.id
        WHERE tc.teacher_id = ?
        ORDER BY c.class_name, c.section
    ";
    $stmt = $conn->prepare($assignments_query);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

$assignments = [];
while ($row = $result->fetch_assoc()) {
    $assignments[] = $row;
}

// Get quick stats for assigned classes
$stats = [];
foreach ($assignments as $assignment) {
    $class_id = $assignment['class_id'];
    $subject_id = $assignment['subject_id'];
    
    // Count students in this class
    $stmt = $conn->prepare("SELECT COUNT(*) as student_count FROM students WHERE class_id = ? AND status = 'Active'");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $student_count = $stmt->get_result()->fetch_assoc()['student_count'];
    
    // Count marks entered for this class/subject
    if ($subject_id) {
        $stmt = $conn->prepare("SELECT COUNT(*) as marks_count FROM marks WHERE subject_id = ? AND created_by = ?");
        $stmt->bind_param("ii", $subject_id, $teacher_id);
        $stmt->execute();
        $marks_count = $stmt->get_result()->fetch_assoc()['marks_count'];
    } else {
        $marks_count = 0; // Can't track marks without subject_id
    }
    
    // Count attendance marked for this class/subject (last 30 days)
    if ($subject_id) {
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT attendance_date) as attendance_count 
            FROM attendance 
            WHERE class_id = ? AND subject_id = ? AND marked_by = ? 
            AND attendance_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->bind_param("iii", $class_id, $subject_id, $teacher_id);
        $stmt->execute();
        $attendance_count = $stmt->get_result()->fetch_assoc()['attendance_count'];
    } else {
        $attendance_count = 0; // Can't track attendance without subject_id
    }
    
    $stats[$assignment['id']] = [
        'student_count' => $student_count,
        'marks_count' => $marks_count,
        'attendance_count' => $attendance_count
    ];
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Assigned Classes</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <span class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($assignments)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            You don't have any class assignments yet. Please contact your administrator.
        </div>
    <?php else: ?>
        <!-- Stats Overview -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title"><?php echo count($assignments); ?></h4>
                                <p class="card-text">Assigned Classes</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-chalkboard-teacher fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title"><?php echo array_sum(array_column($stats, 'student_count')); ?></h4>
                                <p class="card-text">Total Students</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title"><?php echo array_sum(array_column($stats, 'marks_count')); ?></h4>
                                <p class="card-text">Marks Entered</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title"><?php echo array_sum(array_column($stats, 'attendance_count')); ?></h4>
                                <p class="card-text">Attendance Days</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-calendar-check fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assigned Classes -->
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0">My Class Assignments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Department</th>
                                <th>Students</th>
                                <th>Marks</th>
                                <th>Attendance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($assignment['class_name'] . ' ' . $assignment['section']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($assignment['academic_year']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($assignment['subject_name']); ?></strong>
                                        <?php if (!empty($assignment['subject_code'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($assignment['subject_code']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($assignment['department_name']); ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $stats[$assignment['id']]['student_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning"><?php echo $stats[$assignment['id']]['marks_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $stats[$assignment['id']]['attendance_count']; ?> days</span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($assignment['subject_id']): ?>
                                                <a href="marks.php?class=<?php echo $assignment['class_id']; ?>&subject=<?php echo $assignment['subject_id']; ?>" 
                                                   class="btn btn-outline-primary" title="Enter Marks">
                                                    <i class="fas fa-chart-line"></i>
                                                </a>
                                                <a href="attendance.php?class=<?php echo $assignment['class_id']; ?>&subject=<?php echo $assignment['subject_id']; ?>" 
                                                   class="btn btn-outline-success" title="Mark Attendance">
                                                    <i class="fas fa-calendar-check"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="marks.php?class=<?php echo $assignment['class_id']; ?>" 
                                                   class="btn btn-outline-primary" title="Enter Marks">
                                                    <i class="fas fa-chart-line"></i>
                                                </a>
                                                <a href="attendance.php?class=<?php echo $assignment['class_id']; ?>" 
                                                   class="btn btn-outline-success" title="Mark Attendance">
                                                    <i class="fas fa-calendar-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="students.php?class=<?php echo $assignment['class_id']; ?>" 
                                               class="btn btn-outline-info" title="View Students">
                                                <i class="fas fa-users"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
