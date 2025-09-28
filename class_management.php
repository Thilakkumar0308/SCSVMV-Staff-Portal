<?php
$page_title = 'Class Management';
require_once 'includes/header.php';

// Check permission
if (!has_role('Admin') && !has_role('DeptAdmin') && !has_role('HOD')) {
    redirect('dashboard.php');
}

$message = '';
$message_type = '';

// Get HOD's department
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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $class_name    = sanitize_input($_POST['class_name']);
        $section       = sanitize_input($_POST['section']);
        $academic_year = sanitize_input($_POST['academic_year']);
        $department_id = has_role('Admin') ? (int)$_POST['department_id'] : $user_department;

        $stmt = $conn->prepare("INSERT INTO classes (class_name, section, academic_year, department_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $class_name, $section, $academic_year, $department_id);

        if ($stmt->execute()) {
            $message = 'Class added successfully';
            $message_type = 'success';
        } else {
            $message = 'Error adding class: ' . $conn->error;
            $message_type = 'danger';
        }
    }

    elseif ($action === 'edit') {
        $id            = (int)$_POST['id'];
        $class_name    = sanitize_input($_POST['class_name']);
        $section       = sanitize_input($_POST['section']);
        $academic_year = sanitize_input($_POST['academic_year']);
        $department_id = has_role('Admin') ? (int)$_POST['department_id'] : $user_department;

        $stmt = $conn->prepare("UPDATE classes SET class_name=?, section=?, academic_year=?, department_id=? WHERE id=?");
        $stmt->bind_param("sssii", $class_name, $section, $academic_year, $department_id, $id);

        if ($stmt->execute()) {
            $message = 'Class updated successfully';
            $message_type = 'success';
        } else {
            $message = 'Error updating class: ' . $conn->error;
            $message_type = 'danger';
        }
    }

    elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM classes WHERE id=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = 'Class deleted successfully';
            $message_type = 'success';
        } else {
            $message = 'Error deleting class: ' . $conn->error;
            $message_type = 'danger';
        }
    }
}

// Fetch classes
if (has_role('Admin')) {
    $query = "
        SELECT c.*, d.department_name, COUNT(s.id) as student_count
        FROM classes c
        LEFT JOIN departments d ON c.department_id = d.id
        LEFT JOIN students s ON c.id = s.class_id AND s.status = 'Active'
        GROUP BY c.id
        ORDER BY c.class_name, c.section
    ";
    $result = $conn->query($query);
} else {
    $stmt = $conn->prepare("
        SELECT c.*, d.department_name, COUNT(s.id) as student_count
        FROM classes c
        LEFT JOIN departments d ON c.department_id = d.id
        LEFT JOIN students s ON c.id = s.class_id AND s.status = 'Active'
        WHERE c.department_id = ?
        GROUP BY c.id
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

// Get departments (for Admin)
$departments = [];
if (has_role('Admin')) {
    $res = $conn->query("SELECT * FROM departments ORDER BY department_name");
    while ($row = $res->fetch_assoc()) {
        $departments[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Class Management</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                    <i class="fas fa-plus me-1"></i>Add Class
                </button>
            </div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Classes Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="classesTable">
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Section</th>
                            <th>Academic Year</th>
                            <?php if (has_role('Admin')): ?><th>Department</th><?php endif; ?>
                            <th>Students</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $class): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($class['class_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($class['section']); ?></td>
                            <td><?php echo htmlspecialchars($class['academic_year']); ?></td>
                            <?php if (has_role('Admin')): ?>
                            <td><?php echo htmlspecialchars($class['department_name']); ?></td>
                            <?php endif; ?>
                            <td><span class="badge bg-primary"><?php echo $class['student_count']; ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($class['created_at'])); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="editClass(<?php echo htmlspecialchars(json_encode($class)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="students.php?class=<?php echo $class['id']; ?>" class="btn btn-outline-info" title="View Students">
                                        <i class="fas fa-users"></i>
                                    </a>
                                    <a href="attendance.php?class=<?php echo $class['id']; ?>" class="btn btn-outline-success" title="Mark Attendance">
                                        <i class="fas fa-check-circle"></i>
                                    </a>
                                    <a href="timetable.php?class=<?php echo $class['id']; ?>" class="btn btn-outline-warning" title="View Timetable">
                                        <i class="fas fa-calendar-alt"></i>
                                    </a>
                                    <button class="btn btn-outline-danger" onclick="deleteClass(<?php echo $class['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modals for Add/Edit/Delete (same as your version, no major change) -->
<?php require_once 'includes/class-modals.php'; ?>


<?php require_once 'includes/footer.php'; ?>
