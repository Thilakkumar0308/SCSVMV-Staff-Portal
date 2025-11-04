<?php
$page_title = 'Student Disciplinary Records';
require_once 'includes/header.php';

// Simple helper to create references for bind_param via call_user_func_array
function bindParamsStmt($stmt, $types, array &$params) {
    if (empty($types) || count($params) === 0) return;
    $bind_names = [];
    $bind_names[] = $types;
    // bind_param requires references
    foreach ($params as $key => $value) {
        $bind_names[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

// Check student_id in URL (external student id)
if (!isset($_GET['student_id']) || trim($_GET['student_id']) === '') {
    echo "<div class='alert alert-danger m-3'>No student selected.</div>";
    require_once 'includes/footer.php';
    exit;
}

$student_id_external = trim($_GET['student_id']);

// Fetch student info (basic details for header)
$stmt = $conn->prepare("
    SELECT s.id, s.student_id, s.first_name, s.last_name, c.class_name, c.section 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE s.student_id = ? LIMIT 1
");
if (!$stmt) {
    echo "<div class='alert alert-danger m-3'>Database error. Please try again later.</div>";
    error_log('student_disciplinary.php: failed preparing student query: ' . $conn->error);
    require_once 'includes/footer.php';
    exit;
}
$stmt->bind_param("s", $student_id_external);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    echo "<div class='alert alert-warning m-3'>Student not found.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Filters (optional)
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_filter   = isset($_GET['date']) ? trim($_GET['date']) : '';

// Build query to fetch disciplinary records for this student
// Note: disciplinary_actions uses columns da_reason and resolved_reason in your schema.
// We'll alias da_reason to description for display compatibility.
$query = "
    SELECT id, action_date, action_type, da_reason AS description, resolved_reason, status
    FROM disciplinary_actions
    WHERE student_id = ?
";
$params = [];
$types  = "";

// internal student id (integer)
$params[] = (int)$student['id'];
$types .= "i";

if ($status_filter !== '') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($date_filter !== '') {
    // Expecting YYYY-MM-DD
    $query .= " AND action_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

$query .= " ORDER BY action_date DESC, created_at DESC";

$disc_stmt = $conn->prepare($query);
if (!$disc_stmt) {
    echo "<div class='alert alert-danger m-3'>Failed to fetch disciplinary records.</div>";
    error_log('student_disciplinary.php: failed preparing disciplinary query: ' . $conn->error);
    require_once 'includes/footer.php';
    exit;
}

// Bind params using helper (bind_param requires references)
if (!empty($types) && count($params) > 0) {
    bindParamsStmt($disc_stmt, $types, $params);
}

$disc_stmt->execute();
$disc_result = $disc_stmt->get_result();
?>

<div class="container mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex flex-column align-items-start">
            <!-- Page Title -->
            <h5 class="mb-1"><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></h5>
            <!-- Student Name & Class -->
            <div class="d-flex justify-content-between w-100 align-items-center">
                <div>
                    <h4 class="mb-1"><?= htmlspecialchars($student['first_name'].' '.$student['last_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($student['student_id'], ENT_QUOTES, 'UTF-8') ?>)</h4>
                    <small class="text-light"><?= htmlspecialchars($student['class_name'] ?? '', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($student['section'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                </div>
                <a href="student_info.php?student_id=<?= urlencode($student['student_id']) ?>" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Optional Filters -->
            <form class="row g-3 align-items-end mb-4" method="GET">
                <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id_external, ENT_QUOTES, 'UTF-8') ?>">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All</option>
                        <option value="Active" <?= $status_filter === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Resolved" <?= $status_filter === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                        <option value="Expired" <?= $status_filter === 'Expired' ? 'selected' : '' ?>>Expired</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="date" value="<?= htmlspecialchars($date_filter, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-3 d-flex">
                    <button type="submit" class="btn btn-primary me-2 w-50"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="student_disciplinary.php?student_id=<?= urlencode($student_id_external) ?>" class="btn btn-outline-secondary w-50"><i class="fas fa-times me-1"></i> Clear</a>
                </div>
            </form>

            <!-- Disciplinary Records Table -->
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Action Type</th>
                            <th class="text-start">Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($disc_result && $disc_result->num_rows > 0): ?>
                            <?php while ($record = $disc_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= !empty($record['action_date']) ? date('M d, Y', strtotime($record['action_date'])) : '' ?></td>
                                <td><?= htmlspecialchars($record['action_type'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-start"><?= nl2br(htmlspecialchars($record['description'] ?? '', ENT_QUOTES, 'UTF-8')) ?></td>
                                <td>
                                    <?php
                                        $status = $record['status'] ?? '';
                                        $badge = ($status === 'Active') ? 'danger' : (($status === 'Resolved') ? 'success' : 'secondary');
                                    ?>
                                    <span class="badge bg-<?= $badge ?>">
                                        <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-muted">No disciplinary records found for this student.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<?php
if (isset($disc_stmt) && $disc_stmt) {
    $disc_stmt->close();
}
require_once 'includes/footer.php';
?>