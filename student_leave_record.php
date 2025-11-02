<?php
$page_title = 'Student Leave Records';
require_once 'includes/header.php';

if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    echo "<div class='alert alert-danger m-3'>No student selected.</div>";
    require_once 'includes/footer.php';
    exit;
}

$student_id = $_GET['student_id'];

// Fetch student info
$stmt = $conn->prepare("SELECT s.id, s.student_id, s.first_name, s.last_name, c.class_name, c.section 
                        FROM students s 
                        LEFT JOIN classes c ON s.class_id = c.id
                        WHERE s.student_id = ? LIMIT 1");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    echo "<div class='alert alert-warning m-3'>Student not found.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Filters
$status_filter = $_GET['status'] ?? '';
$leave_type_filter = $_GET['leave_type'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Fetch leave records by student_id
$query = "SELECT * FROM leave_requests WHERE student_id = ?";
$params = [$student['id']];
$types = "i";

if ($status_filter) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($leave_type_filter) {
    $query .= " AND leave_type = ?";
    $params[] = $leave_type_filter;
    $types .= "s";
}

if ($date_filter) {
    $query .= " AND start_date <= ? AND end_date >= ?";
    $params[] = $date_filter;
    $params[] = $date_filter;
    $types .= "ss";
}

$query .= " ORDER BY start_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$leave_result = $stmt->get_result();
?>

<div class="container mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex flex-column align-items-start">
            <!-- Page Title -->
            <h5 class="mb-1"><?= htmlspecialchars($page_title) ?></h5>
            <!-- Student Name & Class -->
            <div class="d-flex justify-content-between w-100 align-items-center">
                <div>
                    <h4 class="mb-1"><?= htmlspecialchars($student['first_name'].' '.$student['last_name']) ?> (<?= htmlspecialchars($student['student_id']) ?>)</h4>
                    <small class="text-light"><?= $student['class_name'] ?? '' ?> <?= $student['section'] ?? '' ?></small>
                </div>
                <a href="leave.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Filters -->
            <form class="row g-3 align-items-end mb-4" method="GET">
                <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All</option>
                        <option value="Pending" <?= $status_filter=='Pending'?'selected':''; ?>>Pending</option>
                        <option value="Approved" <?= $status_filter=='Approved'?'selected':''; ?>>Approved</option>
                        <option value="Rejected" <?= $status_filter=='Rejected'?'selected':''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Leave Type</label>
                    <select class="form-select" name="leave_type">
                        <option value="">All</option>
                        <option value="Medical" <?= $leave_type_filter=='Medical'?'selected':''; ?>>Medical</option>
                        <option value="Personal" <?= $leave_type_filter=='Personal'?'selected':''; ?>>Personal</option>
                        <option value="Emergency" <?= $leave_type_filter=='Emergency'?'selected':''; ?>>Emergency</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="date" value="<?= $date_filter ?>">
                </div>
                <div class="col-md-3 d-flex">
                    <button type="submit" class="btn btn-primary me-2 w-50"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="student_leave.php?student_id=<?= htmlspecialchars($student_id) ?>" class="btn btn-outline-secondary w-50"><i class="fas fa-times me-1"></i> Clear</a>
                </div>
            </form>

            <!-- Leave Records Table -->
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>From</th>
                            <th>To</th>
                            <th>Type</th>
                            <th class="text-start">Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($leave_result->num_rows > 0): ?>
                            <?php while ($row = $leave_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($row['start_date'])) ?></td>
                                <td><?= date('M d, Y', strtotime($row['end_date'])) ?></td>
                                <td><?= htmlspecialchars($row['leave_type']) ?></td>
                                <td class="text-start"><?= htmlspecialchars($row['reason']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $row['status']=='Approved'?'success':($row['status']=='Rejected'?'danger':'warning') ?>">
                                        <?php if($row['status']=='Approved'): ?><i class="fas fa-check-circle me-1"></i><?php elseif($row['status']=='Rejected'): ?><i class="fas fa-times-circle me-1"></i><?php else: ?><i class="fas fa-clock me-1"></i><?php endif; ?>
                                        <?= $row['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-muted">No leave records found for this student.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
