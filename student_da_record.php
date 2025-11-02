<?php
$page_title = 'Student Disciplinary Records';
require_once 'includes/header.php';

// Check student_id in URL
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    echo "<div class='alert alert-danger m-3'>No student selected.</div>";
    require_once 'includes/footer.php';
    exit;
}

$student_id = $_GET['student_id'];

// Fetch student info (basic details for header)
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

// Filters (optional: you can filter by Status or Date if needed)
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Fetch disciplinary records for this student
$query = "SELECT action_date, action_type, description, status 
          FROM disciplinary_actions 
          WHERE student_id = ?";
$params = [$student['id']];
$types = "i";

if ($status_filter) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($date_filter) {
    $query .= " AND action_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

$query .= " ORDER BY action_date DESC";

$disc_stmt = $conn->prepare($query);
$disc_stmt->bind_param($types, ...$params);
$disc_stmt->execute();
$disc_result = $disc_stmt->get_result();
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
                <a href="student_info.php?student_id=<?= htmlspecialchars($student['student_id']) ?>" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Optional Filters -->
            <form class="row g-3 align-items-end mb-4" method="GET">
                <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All</option>
                        <option value="Pending" <?= $status_filter=='Pending'?'selected':''; ?>>Pending</option>
                        <option value="Resolved" <?= $status_filter=='Resolved'?'selected':''; ?>>Resolved</option>
                        <option value="Warning" <?= $status_filter=='Warning'?'selected':''; ?>>Warning</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="date" value="<?= $date_filter ?>">
                </div>
                <div class="col-md-3 d-flex">
                    <button type="submit" class="btn btn-primary me-2 w-50"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="student_disciplinary.php?student_id=<?= htmlspecialchars($student_id) ?>" class="btn btn-outline-secondary w-50"><i class="fas fa-times me-1"></i> Clear</a>
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
                        <?php if ($disc_result->num_rows > 0): ?>
                            <?php while ($record = $disc_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($record['action_date'])) ?></td>
                                <td><?= htmlspecialchars($record['action_type']) ?></td>
                                <td class="text-start"><?= nl2br(htmlspecialchars($record['description'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= $record['status']=='Resolved'?'success':($record['status']=='Warning'?'warning':'secondary') ?>">
                                        <?= htmlspecialchars($record['status']) ?>
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

<?php require_once 'includes/footer.php'; ?>
