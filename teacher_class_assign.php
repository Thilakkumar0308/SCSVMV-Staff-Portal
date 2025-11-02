<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Check if logged in
if (!is_logged_in()) redirect('index.php');
if (!has_any_role(['Admin','DeptAdmin','HOD'])) redirect('dashboard.php');

$message = '';
$message_type = '';
$user_department = null;

// If HOD, get their department
if (has_role('HOD')) {
    $stmt = $conn->prepare("SELECT department_id FROM users WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) $user_department = $row['department_id'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'assign') {
        $teacher_id = intval($_POST['teacher_id'] ?? 0);
        $class_id = intval($_POST['class_id'] ?? 0);
        $subject_id = intval($_POST['subject_id'] ?? 0);
        
        if ($teacher_id && $class_id && $subject_id) {
            // Check duplicate
            $stmt = $conn->prepare("SELECT COUNT(*) FROM teacher_classes WHERE teacher_id=? AND class_id=? AND subject_id=?");
            $stmt->bind_param("iii", $teacher_id, $class_id, $subject_id);
            $stmt->execute();
            $count = $stmt->get_result()->fetch_row()[0];
            
            if ($count > 0) {
                $message = "This assignment already exists.";
                $message_type = "warning";
            } else {
                $stmt = $conn->prepare("INSERT INTO teacher_classes (teacher_id, class_id, subject_id, assigned_by, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("iiii", $teacher_id, $class_id, $subject_id, $_SESSION['user_id']);
                if ($stmt->execute()) {
                    $message = "Teacher assigned successfully.";
                    $message_type = "success";
                } else {
                    $message = "Error assigning teacher: ".$conn->error;
                    $message_type = "danger";
                }
            }
        } else {
            $message = "Please select teacher, class, and subject.";
            $message_type = "danger";
        }
    } elseif ($action === 'remove') {
        $assignment_id = intval($_POST['assignment_id'] ?? 0);
        if ($assignment_id) {
            $stmt = $conn->prepare("DELETE FROM teacher_classes WHERE id=?");
            $stmt->bind_param("i", $assignment_id);
            if ($stmt->execute()) {
                $message = "Assignment removed successfully.";
                $message_type = "success";
            } else {
                $message = "Error removing assignment: ".$conn->error;
                $message_type = "danger";
            }
        }
    }
}

// Fetch teachers
$teachers_query = "SELECT id, full_name, department_id FROM users WHERE role='Teacher'";
if (has_role('HOD') && $user_department) $teachers_query .= " AND department_id=$user_department";
$teachers = $conn->query($teachers_query)->fetch_all(MYSQLI_ASSOC);

// Fetch classes
$classes_query = "SELECT c.id, c.class_name, c.section, d.department_name 
                  FROM classes c 
                  LEFT JOIN departments d ON c.department_id=d.id";
if (has_role('HOD') && $user_department) $classes_query .= " WHERE c.department_id=$user_department";
$classes = $conn->query($classes_query)->fetch_all(MYSQLI_ASSOC);

// Fetch assignments
$assignments_query = "SELECT tc.id, u.full_name AS teacher_name, c.class_name, c.section,
                             s.subject_name, s.subject_code, ab.full_name AS assigned_by_name, tc.created_at
                      FROM teacher_classes tc
                      JOIN users u ON tc.teacher_id=u.id
                      JOIN classes c ON tc.class_id=c.id
                      JOIN subjects s ON tc.subject_id=s.id
                      LEFT JOIN users ab ON tc.assigned_by=ab.id";
$assignments = $conn->query($assignments_query)->fetch_all(MYSQLI_ASSOC);

$page_title = 'Teacher Class Management';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h2">Teacher Class Assignment</h1>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal">
                    <i class="fas fa-plus me-1"></i> Assign Teacher
                </button>
                <button class="btn btn-secondary" onclick="history.back()">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </button>
            </div>
        </div>
    </div>

    <?php if($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Assignments Table -->
    <div class="card shadow-sm mx-auto" style="max-width:1000px;">
        <div class="card-header bg-primary text-white">
            Current Teacher Assignments
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="assignmentsTable">
                    <thead class="table-dark">
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
                        <?php foreach($assignments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['teacher_name']) ?></td>
                            <td><?= htmlspecialchars($a['class_name'].' '.$a['section']) ?></td>
                            <td><?= htmlspecialchars($a['subject_name']) ?></td>
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
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Teacher to Class & Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Teacher *</label>
                            <select name="teacher_id" class="form-select" required>
                                <option value="">-- Select Teacher --</option>
                                <?php foreach($teachers as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Class *</label>
                            <select name="class_id" id="class_id" class="form-select" required>
                                <option value="">-- Select Class --</option>
                                <?php foreach($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name'].' '.$c['section']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Subject *</label>
                        <select name="subject_id" id="subject_id" class="form-select" required>
                            <option value="">-- Select Subject --</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    $('#assignmentsTable').DataTable({ "pageLength":25, "order":[[4,"desc"]], "columnDefs":[{"orderable":false,"targets":5}] });

    $('#class_id').on('change', function(){
        const classId = $(this).val();
        const subjectSelect = $('#subject_id');
        subjectSelect.html('<option>Loading...</option>');
        if(classId){
            $.ajax({
                url:'ajax/ajax_get_subjects.php',
                type:'GET',
                data:{class_id: classId},
                dataType:'json',
                success:function(data){
                    subjectSelect.empty().append('<option value="">-- Select Subject --</option>');
                    if(data.length>0){
                        data.forEach(s=> subjectSelect.append(`<option value="${s.id}">${s.subject_name}</option>`));
                    } else {
                        subjectSelect.append('<option value="">No subjects found</option>');
                    }
                },
                error:function(){ subjectSelect.html('<option value="">Error loading subjects</option>'); }
            });
        } else subjectSelect.html('<option value="">-- Select Subject --</option>');
    });
});
</script>

<?php include 'includes/footer.php'; ?>
