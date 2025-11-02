<?php
$page_title = 'Subject Management';
require_once 'includes/header.php';

// Only Admin, DeptAdmin, HOD can access
if (!has_any_role(['Admin','DeptAdmin','HOD'])) {
    redirect('dashboard.php');
}

$message = '';
$message_type = '';

// Get user's department if HOD
$user_department = null;
if (has_role('HOD')) {
    $stmt = $conn->prepare("SELECT department_id FROM users WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()) $user_department = $row['department_id'];
}

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $subject_name = sanitize_input($_POST['subject_name']);
        $subject_code = sanitize_input($_POST['subject_code']);
        $class_id = (int)($_POST['class_id'] ?? 0);

        // Check for duplicate code
        $stmt = $conn->prepare("SELECT id FROM subjects WHERE subject_code=?");
        $stmt->bind_param("s", $subject_code);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $message = "Subject code already exists!";
            $message_type = 'danger';
        } else {
            $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, class_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $subject_name, $subject_code, $class_id);
            $stmt->execute();
            $message = "Subject added successfully";
            $message_type = 'success';
        }
    } elseif ($action === 'edit') {
        $subject_id = (int)$_POST['subject_id'];
        $subject_name = sanitize_input($_POST['subject_name']);
        $subject_code = sanitize_input($_POST['subject_code']);
        $class_id = (int)($_POST['class_id'] ?? 0);

        $stmt = $conn->prepare("UPDATE subjects SET subject_name=?, subject_code=?, class_id=? WHERE id=?");
        $stmt->bind_param("ssii", $subject_name, $subject_code, $class_id, $subject_id);
        $stmt->execute();
        $message = "Subject updated successfully";
        $message_type = 'success';
    } elseif ($action === 'delete') {
        $subject_id = (int)$_POST['subject_id'];
        $stmt = $conn->prepare("DELETE FROM subjects WHERE id=?");
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $message = "Subject deleted successfully";
        $message_type = 'success';
    }
}

// Fetch classes for dropdown
if (has_role('HOD') && $user_department) {
    $stmt = $conn->prepare("SELECT * FROM classes WHERE department_id=? ORDER BY class_name, section");
    $stmt->bind_param("i", $user_department);
    $stmt->execute();
    $classes_result = $stmt->get_result();
} else {
    $classes_result = $conn->query("SELECT * FROM classes ORDER BY class_name, section");
}
$classes = [];
while ($row = $classes_result->fetch_assoc()) $classes[] = $row;

// Fetch subjects
if (has_role('HOD') && $user_department) {
    $stmt = $conn->prepare("
        SELECT s.*, c.class_name, c.section, c.academic_year 
        FROM subjects s
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE c.department_id=?
        ORDER BY c.class_name, c.section, s.subject_name
    ");
    $stmt->bind_param("i", $user_department);
    $stmt->execute();
    $subjects_result = $stmt->get_result();
} else {
    $subjects_result = $conn->query("
        SELECT s.*, c.class_name, c.section, c.academic_year 
        FROM subjects s
        LEFT JOIN classes c ON s.class_id = c.id
        ORDER BY c.class_name, c.section, s.subject_name
    ");
}
$subjects = [];
while ($row = $subjects_result->fetch_assoc()) $subjects[] = $row;
?>

<div class="container-fluid">
    <h1 class="h2 mt-3">Subject Management</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addSubjectModal">Add Subject</button>

    <table class="table table-striped table-hover" id="subjectsTable">
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Class</th>
                <th>Year</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($subjects as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['subject_code']) ?></td>
                <td><?= htmlspecialchars($s['subject_name']) ?></td>
                <td><?= $s['class_name'] ? htmlspecialchars($s['class_name'] . ' ' . $s['section']) : 'Not assigned' ?></td>
                <td><?= htmlspecialchars($s['academic_year'] ?? '-') ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick='editSubject(<?= json_encode($s) ?>)'><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger" onclick='deleteSubject(<?= $s["id"] ?>, "<?= htmlspecialchars($s["subject_name"]) ?>")'><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modals -->
<?php include 'includes/class-modals.php'; ?>
<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSubjectModalLabel">Add Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="subject_name" class="form-label">Subject Name</label>
                        <input type="text" name="subject_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="subject_code" class="form-label">Subject Code</label>
                        <input type="text" name="subject_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="class_id" class="form-label">Class</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach($classes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name'].' '.$c['section']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="subject_id">
                    <div class="mb-3">
                        <label class="form-label">Subject Name</label>
                        <input type="text" name="subject_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject Code</label>
                        <input type="text" name="subject_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Class</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach($classes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name'].' '.$c['section']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Subject Modal -->
<div class="modal fade" id="deleteSubjectModal" tabindex="-1" aria-labelledby="deleteSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSubjectModalLabel">Delete Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this subject?</p>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="subject_id">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editSubject(subject) {
    $('#editSubjectModal input[name="subject_id"]').val(subject.id);
    $('#editSubjectModal input[name="subject_name"]').val(subject.subject_name);
    $('#editSubjectModal input[name="subject_code"]').val(subject.subject_code);
    $('#editSubjectModal select[name="class_id"]').val(subject.class_id);
    $('#editSubjectModal').modal('show');
}

function deleteSubject(id, name) {
    if(confirm('Delete subject "' + name + '"?')) {
        $('#deleteSubjectModal input[name="subject_id"]').val(id);
        $('#deleteSubjectModal').modal('show');
        $('#deleteSubjectModal form').submit();
    }
}

$(document).ready(function(){
    $('#subjectsTable').DataTable({ "pageLength":25 });
});
</script>

<?php include 'includes/footer.php'; ?>
