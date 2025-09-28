<?php
$page_title = 'Timetable Management';
require_once 'includes/header.php';

// Check permissions
if (!has_role('Admin') && !has_role('DeptAdmin') && !has_role('HOD')) {
    redirect('dashboard.php');
}

$message = '';
$message_type = '';

// Handle Upload, Edit, Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'upload') {
        $class_id = $_POST['class_id'];
        $academic_year = sanitize_input($_POST['academic_year']);

        if (!empty($_FILES['timetable_image']['name'])) {
            $upload_dir = "uploads/timetables/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $filename = time() . "_" . basename($_FILES['timetable_image']['name']);
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['timetable_image']['tmp_name'], $target_file)) {
                $stmt = $conn->prepare("INSERT INTO timetable (class_id, academic_year, image_path, created_by) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $class_id, $academic_year, $target_file, $_SESSION['user_id']);
                if ($stmt->execute()) {
                    $message = "Timetable uploaded successfully";
                    $message_type = "success";
                } else {
                    $message = "DB Error: " . $conn->error;
                    $message_type = "danger";
                }
            } else {
                $message = "Failed to upload file.";
                $message_type = "danger";
            }
        } else {
            $message = "Please select a file.";
            $message_type = "warning";
        }
    }

    elseif ($_POST['action'] == 'edit') {
        $id = $_POST['id'];
        $class_id = $_POST['class_id'];
        $academic_year = sanitize_input($_POST['academic_year']);

        // Handle new image upload if any
        $image_path = null;
        if (!empty($_FILES['timetable_image']['name'])) {
            $upload_dir = "uploads/timetables/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $filename = time() . "_" . basename($_FILES['timetable_image']['name']);
            $target_file = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['timetable_image']['tmp_name'], $target_file)) {
                $image_path = $target_file;
            }
        }

        if ($image_path) {
            $stmt = $conn->prepare("UPDATE timetable SET class_id=?, academic_year=?, image_path=? WHERE id=?");
            $stmt->bind_param("issi", $class_id, $academic_year, $image_path, $id);
        } else {
            $stmt = $conn->prepare("UPDATE timetable SET class_id=?, academic_year=? WHERE id=?");
            $stmt->bind_param("isi", $class_id, $academic_year, $id);
        }

        if ($stmt->execute()) {
            $message = "Timetable updated successfully";
            $message_type = "success";
        } else {
            $message = "DB Error: " . $conn->error;
            $message_type = "danger";
        }
    }

    elseif ($_POST['action'] == 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM timetable WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Timetable deleted successfully";
            $message_type = "success";
        } else {
            $message = "DB Error: " . $conn->error;
            $message_type = "danger";
        }
    }
}

// Fetch classes
$classes = [];
$result = $conn->query("SELECT * FROM classes ORDER BY class_name, section");
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

// Fetch timetables
$timetables = [];
$result = $conn->query("
    SELECT t.*, c.class_name, c.section 
    FROM timetable t
    LEFT JOIN classes c ON t.class_id = c.id
    ORDER BY t.created_at DESC
");
while ($row = $result->fetch_assoc()) {
    $timetables[] = $row;
}
?>

<div class="container mt-4">
    <h2>Timetable Management</h2>

    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Upload form -->
    <div class="card mb-4">
        <div class="card-header">Upload Timetable</div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="class_id" class="form-label">Class *</label>
                        <select name="class_id" id="class_id" class="form-select" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="academic_year" class="form-label">Academic Year *</label>
                        <input type="text" name="academic_year" id="academic_year" class="form-control"
                               value="<?php echo get_setting('academic_year', '2024-2025'); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="timetable_image" class="form-label">Timetable Image *</label>
                        <input type="file" name="timetable_image" id="timetable_image" class="form-control" accept="image/*" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Upload</button>
            </form>
        </div>
    </div>

    <!-- Display Timetables -->
    <div class="row">
        <?php foreach ($timetables as $tb): ?>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5><?php echo htmlspecialchars($tb['class_name'] . ' ' . $tb['section']); ?></h5>
                    <p class="text-muted">Academic Year: <?php echo htmlspecialchars($tb['academic_year']); ?></p>

                    <!-- Clickable timetable image -->
                    <a href="#" data-bs-toggle="modal" data-bs-target="#timetableModal<?php echo $tb['id']; ?>">
                        <img src="<?php echo $tb['image_path']; ?>" class="img-fluid rounded mb-2" alt="Timetable" style="cursor:pointer; max-height:200px;">
                    </a>

                    <!-- Buttons below image -->
                    <div class="d-flex justify-content-center mt-2">
                        <button class="btn btn-sm btn-outline-primary me-2" 
                                onclick="editTimetable(<?php echo $tb['id']; ?>,'<?php echo $tb['class_id']; ?>','<?php echo htmlspecialchars($tb['academic_year']); ?>')">
                            Edit
                        </button>
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="deleteTimetable(<?php echo $tb['id']; ?>)">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for full image -->
        <div class="modal fade" id="timetableModal<?php echo $tb['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Timetable - <?php echo htmlspecialchars($tb['class_name'] . ' ' . $tb['section']); ?> 
                            (<?php echo htmlspecialchars($tb['academic_year']); ?>)
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="<?php echo $tb['image_path']; ?>" class="img-fluid" alt="Timetable">
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Timetable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_class_id" class="form-label">Class *</label>
                        <select name="class_id" id="edit_class_id" class="form-select" required>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_academic_year" class="form-label">Academic Year *</label>
                        <input type="text" name="academic_year" id="edit_academic_year" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_timetable_image" class="form-label">Replace Image</label>
                        <input type="file" name="timetable_image" id="edit_timetable_image" class="form-control" accept="image/*">
                        <small class="text-muted">Leave empty to keep current image</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Update</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editTimetable(id, class_id, academic_year) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_class_id').value = class_id;
    document.getElementById('edit_academic_year').value = academic_year;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function deleteTimetable(id) {
    if(confirm('Are you sure you want to delete this timetable?')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="'+id+'">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
