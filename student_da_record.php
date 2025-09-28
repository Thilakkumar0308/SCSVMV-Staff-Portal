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
$stmt = $conn->prepare("SELECT id, student_id, first_name, last_name FROM students WHERE student_id = ? LIMIT 1");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    echo "<div class='alert alert-warning m-3'>Student not found.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Fetch disciplinary records for this student
$disc_stmt = $conn->prepare("SELECT action_date, action_type, description, status FROM disciplinary_actions WHERE student_id = ? ORDER BY action_date DESC");
$disc_stmt->bind_param("i", $student['id']);
$disc_stmt->execute();
$disc_result = $disc_stmt->get_result();
?>

<div class="container mt-4">
    <div class="card shadow-lg">
        <div class="card-body">
            <h3 class="mb-4">
                Disciplinary Records for <?php echo htmlspecialchars($student['first_name'] . " " . $student['last_name']); ?> (<?php echo htmlspecialchars($student['student_id']); ?>)
            </h3>

            <?php if ($disc_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Action Type</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($record = $disc_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($record['action_date'])); ?></td>
                            <td><?php echo htmlspecialchars($record['action_type']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($record['description'])); ?></td>
                            <td><?php echo htmlspecialchars($record['status']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p class="text-muted">No disciplinary records found for this student.</p>
            <?php endif; ?>

            <a href="student_info.php?student_id=<?php echo urlencode($student['student_id']); ?>" class="btn btn-secondary mt-3">
                <i class="fas fa-arrow-left me-1"></i> Back to Student Info
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
