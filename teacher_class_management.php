<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// --- Enable error display (for debugging; remove in production) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Helper functions (if not in functions.php) ---
if (!function_exists('get_user_department')) {
    function get_user_department() {
        global $conn;
        if (!isset($_SESSION['user_id'])) return null;
        $stmt = $conn->prepare("SELECT department_id FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['department_id'];
        }
        return null;
    }
}

if (!function_exists('check_department_access')) {
    function check_department_access($department_id) {
        $user_department = get_user_department();
        if (!has_role('Admin') && $user_department !== $department_id) {
            die('Access Denied: You cannot access this department’s data.');
        }
    }
}

// --- Check login ---
if (!is_logged_in()) {
    redirect('index.php');
}

// --- Check permissions ---
if (!has_any_role(['Admin', 'DeptAdmin', 'HOD'])) {
    redirect('dashboard.php');
}

// --- Initialize variables ---
$message = '';
$message_type = '';
$user_department = get_user_department();

// --- Verify department for HOD/DeptAdmin if missing ---
if (!$user_department && (has_role('HOD') || has_role('DeptAdmin'))) {
    $stmt = $conn->prepare("SELECT department_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_department = $row['department_id'];
    }
}

// --- Check if subject_id column exists ---
$check_column = $conn->query("SHOW COLUMNS FROM teacher_classes LIKE 'subject_id'");
$has_subject_id = $check_column && $check_column->num_rows > 0;

// --- Handle form submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'assign') {
        $teacher_id = intval($_POST['teacher_id'] ?? 0);
        $class_id = intval($_POST['class_id'] ?? 0);
        $subject_id = intval($_POST['subject_id'] ?? 0);
        
        if ($teacher_id && $class_id && $subject_id) {
            // Permission checks for non-admin
            if (!has_role('Admin')) {
                // Check teacher department
                $stmt = $conn->prepare("SELECT department_id FROM users WHERE id = ?");
                $stmt->bind_param("i", $teacher_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    check_department_access($row['department_id']);
                } else {
                    $message = 'Teacher not found.';
                    $message_type = 'danger';
                    goto skip_assign;
                }

                // Check class department
                $stmt = $conn->prepare("SELECT department_id FROM classes WHERE id = ?");
                $stmt->bind_param("i", $class_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    check_department_access($row['department_id']);
                } else {
                    $message = 'Class not found.';
                    $message_type = 'danger';
                    goto skip_assign;
                }

                // Check subject department
                $stmt = $conn->prepare("
                    SELECT c.department_id 
                    FROM subjects s 
                    LEFT JOIN classes c ON s.class_id = c.id 
                    WHERE s.id = ?
                ");
                $stmt->bind_param("i", $subject_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    check_department_access($row['department_id']);
                } else {
                    $message = 'Subject not found.';
                    $message_type = 'danger';
                    goto skip_assign;
                }
            }

            // Check for duplicates
            if ($has_subject_id) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM teacher_classes WHERE teacher_id = ? AND class_id = ? AND subject_id = ?");
                $stmt->bind_param("iii", $teacher_id, $class_id, $subject_id);
            } else {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM teacher_classes WHERE teacher_id = ? AND class_id = ?");
                $stmt->bind_param("ii", $teacher_id, $class_id);
            }
            $stmt->execute();
            $count = $stmt->get_result()->fetch_row()[0] ?? 0;

            if ($count > 0) {
                $message = "This assignment already exists.";
                $message_type = "warning";
            } else {
                // Insert assignment
                if ($has_subject_id) {
                    $stmt = $conn->prepare("INSERT INTO teacher_classes (teacher_id, class_id, subject_id, assigned_by, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iiii", $teacher_id, $class_id, $subject_id, $_SESSION['user_id']);
                } else {
                    $stmt = $conn->prepare("INSERT INTO teacher_classes (teacher_id, class_id, assigned_at) VALUES (?, ?, NOW())");
                    $stmt->bind_param("ii", $teacher_id, $class_id);
                }

                if ($stmt->execute()) {
                    $message = "Teacher assigned successfully.";
                    $message_type = "success";
                } else {
                    $message = "Error assigning teacher: " . $conn->error;
                    $message_type = "danger";
                }
            }

            skip_assign:
        } else {
            $message = "Please select teacher, class, and subject.";
            $message_type = "danger";
        }
    } elseif ($action === 'remove') {
        $assignment_id = intval($_POST['assignment_id'] ?? 0);

        if ($assignment_id) {
            // Verify department access
            if (!has_role('Admin')) {
                $stmt = $conn->prepare("
                    SELECT c.department_id 
                    FROM teacher_classes tc
                    JOIN classes c ON tc.class_id = c.id
                    WHERE tc.id = ?
                ");
                $stmt->bind_param("i", $assignment_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    check_department_access($row['department_id']);
                } else {
                    $message = 'Assignment not found.';
                    $message_type = 'danger';
                    goto skip_remove;
                }
            }

            $stmt = $conn->prepare("DELETE FROM teacher_classes WHERE id = ?");
            $stmt->bind_param("i", $assignment_id);

            if ($stmt->execute()) {
                $message = "Assignment removed successfully.";
                $message_type = "success";
            } else {
                $message = "Error removing assignment: " . $conn->error;
                $message_type = "danger";
            }

            skip_remove:
        }
    }
}

// --- Fetch teachers ---
$teachers_query = "SELECT u.id, u.full_name, d.department_name 
                   FROM users u 
                   LEFT JOIN departments d ON u.department_id = d.id 
                   WHERE u.role = 'Teacher'";
if (!has_role('Admin') && $user_department) {
    $teachers_query .= " AND u.department_id = $user_department";
}
$teachers = $conn->query($teachers_query)->fetch_all(MYSQLI_ASSOC);

// --- Fetch classes ---
$classes_query = "SELECT c.id, c.class_name, c.section, d.department_name 
                  FROM classes c 
                  LEFT JOIN departments d ON c.department_id = d.id";
if (!has_role('Admin') && $user_department) {
    $classes_query .= " WHERE c.department_id = $user_department";
}
$classes = $conn->query($classes_query)->fetch_all(MYSQLI_ASSOC);

// --- Fetch subjects ---
$subjects_query = "SELECT s.id, s.subject_name, s.subject_code, s.class_id, c.class_name, c.department_id
                   FROM subjects s 
                   LEFT JOIN classes c ON s.class_id = c.id";
if (!has_role('Admin') && $user_department) {
    $subjects_query .= " WHERE c.department_id = $user_department";
}
$subjects = $conn->query($subjects_query)->fetch_all(MYSQLI_ASSOC);

// --- Fetch assignments ---
if ($has_subject_id) {
    $assignments_query = "SELECT tc.id, u.full_name AS teacher_name, c.class_name, c.section,
                                 s.subject_name, s.subject_code, tc.created_at,
                                 assigned_by.full_name AS assigned_by_name
                          FROM teacher_classes tc
                          JOIN users u ON tc.teacher_id = u.id
                          JOIN classes c ON tc.class_id = c.id
                          JOIN subjects s ON tc.subject_id = s.id
                          LEFT JOIN users assigned_by ON tc.assigned_by = assigned_by.id";
} else {
    $assignments_query = "SELECT tc.id, u.full_name AS teacher_name, c.class_name, c.section,
                                 'General' AS subject_name, '' AS subject_code, tc.assigned_at AS created_at,
                                 'System' AS assigned_by_name
                          FROM teacher_classes tc
                          JOIN users u ON tc.teacher_id = u.id
                          JOIN classes c ON tc.class_id = c.id";
}
if (!has_role('Admin') && $user_department) {
    $assignments_query .= " WHERE c.department_id = $user_department";
}
$assignments = $conn->query($assignments_query)->fetch_all(MYSQLI_ASSOC);

$page_title = 'Teacher Class Management';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Teacher Class Assignment</h1>
                <div>
                    <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#assignModal">
                        <i class="fas fa-plus me-1"></i> Assign Teacher
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="history.back()">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-header"><h5>Current Teacher Assignments</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="assignmentsTable">
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Assigned By</th>
                            <th>Assigned Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $a): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($a['teacher_name']) ?></strong></td>
                            <td><?= htmlspecialchars($a['class_name'].' '.$a['section']) ?></td>
                            <td>
                                <?= htmlspecialchars($a['subject_name']) ?>
                                <?php if ($a['subject_code']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($a['subject_code']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($a['assigned_by_name']) ?></td>
                            <td><?= date('M d, Y', strtotime($a['created_at'])) ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Remove this assignment?')">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignModalLabel">Assign Teacher to Class & Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teacher *</label>
                            <select name="teacher_id" class="form-select" required>
                                <option value="">-- Select Teacher --</option>
                                <?php foreach ($teachers as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?> <?= $t['department_name'] ? "({$t['department_name']})" : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Class *</label>
                            <select name="class_id" id="class_id" class="form-select" required>
                                <option value="">-- Select Class --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name'].' '.$c['section']) ?> <?= $c['department_name'] ? "({$c['department_name']})" : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject *</label>
                        <select name="subject_id" id="subject_id" class="form-select" required>
                            <option value="">-- Select Subject --</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= $s['id'] ?>" data-class-id="<?= $s['class_id'] ?>"><?= htmlspecialchars($s['subject_name']) ?> <?= $s['subject_code'] ? "({$s['subject_code']})" : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Assign Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#assignmentsTable').DataTable({
        "order": [[4, "desc"]],
        "pageLength": 25,
        "responsive": true,
        "columnDefs": [{ "orderable": false, "targets": 5 }]
    });

    // Filter subjects by selected class
    $('#class_id').on('change', function() {
        const classId = $(this).val();
        $('#subject_id option').each(function() {
            const show = !classId || $(this).data('class-id') == classId || $(this).val() === "";
            $(this).toggle(show);
        });
        $('#subject_id').val('');
    });
});
</script>

<?php include 'includes/footer.php'; ?>
