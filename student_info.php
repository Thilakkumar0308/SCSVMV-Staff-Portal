<?php
$page_title = 'Student Information';
require_once 'includes/header.php';

// Check student_id in URL
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    echo "<div class='alert alert-danger m-3'>No student selected.</div>";
    require_once 'includes/footer.php';
    exit;
}

$student_id = $_GET['student_id'];

// Fetch student info
$stmt = $conn->prepare("
    SELECT s.*, c.class_name, c.section, d.department_name
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    LEFT JOIN departments d ON c.department_id = d.id
    WHERE s.student_id = ?
    LIMIT 1
");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    echo "<div class='alert alert-warning m-3'>Student not found.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Profile picture function
function getProfilePictureUrl($filename) {
    if (!$filename || $filename == "null" || $filename == "undefined") {
        return "uploads/profile_pictures/default.svg";
    }
    return $filename;
}

// Fetch disciplinary records
$disciplinary_records = [];
$da_stmt = $conn->prepare("SELECT * FROM disciplinary_actions WHERE student_id=? ORDER BY action_date DESC");
$da_stmt->bind_param("i", $student['id']);
$da_stmt->execute();
$disc_result = $da_stmt->get_result();
while ($row = $disc_result->fetch_assoc()) {
    $disciplinary_records[] = $row;
}

// Latest DA for popup
$latest_da = count($disciplinary_records) > 0 ? $disciplinary_records[0] : null;

// Check if user can edit
$can_edit_student = has_any_role(['HOD','Admin']);
?>

<div class="container mt-4">
    <div class="card shadow-lg">
        <div class="card-body">
            <div class="row g-4 align-items-center">
                <!-- Profile Picture -->
                <div class="col-12 col-md-3 text-center">
                    <img src="<?php echo getProfilePictureUrl($student['profile_picture']); ?>" 
                         class="img-fluid rounded-circle mb-3 shadow-sm" 
                         style="max-width:180px; height:180px; object-fit:cover;" 
                         alt="Profile Picture">

                    <h4 class="mb-1"><?php echo htmlspecialchars($student['first_name'] . " " . $student['last_name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($student['student_id']); ?></p>
                    <span class="badge bg-<?php echo $student['status'] == 'Active' ? 'success' : ($student['status'] == 'Inactive' ? 'warning' : 'info'); ?>">
                        <?php echo $student['status']; ?>
                    </span>
                </div>

                <!-- Student Info -->
                <div class="col-12 col-md-9">
                    <h3 class="mb-3">Student Information</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>Full Name</th>
                                    <td><?php echo htmlspecialchars($student['first_name'] . " " . $student['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                </tr>
                                <tr>
                                    <th>Phone</th>
                                    <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                </tr>
                                <tr>
                                    <th>Date of Birth</th>
                                    <td><?php echo $student['date_of_birth'] ? date('M d, Y', strtotime($student['date_of_birth'])) : 'Not provided'; ?></td>
                                </tr>
                                <tr>
                                    <th>Gender</th>
                                    <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                </tr>
                                <tr>
                                    <th>Address</th>
                                    <td><?php echo nl2br(htmlspecialchars($student['address'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Class</th>
                                    <td><?php echo htmlspecialchars($student['class_name'] . " " . $student['section']); ?></td>
                                </tr>
                                <tr>
                                    <th>Department</th>
                                    <td><?php echo htmlspecialchars($student['department_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Admission Date</th>
                                    <td><?php echo $student['admission_date'] ? date('M d, Y', strtotime($student['admission_date'])) : 'Not provided'; ?></td>
                                </tr>
                                <tr>
                                    <th>Parent Name</th>
                                    <td><?php echo htmlspecialchars($student['parent_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Parent Phone</th>
                                    <td><?php echo htmlspecialchars($student['parent_phone']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Disciplinary Records Table -->
                    <div class="mt-4">
                        <h5>Disciplinary Records</h5>
                        <?php if(count($disciplinary_records) > 0): ?>
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
                                    <?php foreach($disciplinary_records as $d): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($d['action_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($d['action_type']); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($d['description'])); ?></td>
                                        <td><?php echo htmlspecialchars($d['status']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <p class="text-muted">No disciplinary records found.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-4">
                        <h5>Quick Actions</h5>
                        <div class="btn-group flex-wrap" role="group">
                            <?php if($can_edit_student): ?>
                            <button class="btn btn-outline-primary m-1" data-bs-toggle="modal" data-bs-target="#editStudentModal">
                                <i class="fas fa-edit me-1"></i> Edit Student
                            </button>
                            <?php endif; ?>

                            <a href="marks.php?student=<?php echo $student['id']; ?>" class="btn btn-outline-success m-1">
                                <i class="fas fa-chart-line me-1"></i> Marks
                            </a>

                            <a href="leave.php?student=<?php echo $student['id']; ?>" class="btn btn-outline-warning m-1">
                                <i class="fas fa-calendar-times me-1"></i> Leave Requests
                            </a>

                            <a href="onduty.php?student=<?php echo $student['id']; ?>" class="btn btn-outline-info m-1">
                                <i class="fas fa-calendar-check me-1"></i> On-Duty
                            </a>

                            <a href="student_da_record.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-outline-danger m-1">
                                <i class="fas fa-exclamation-triangle me-1"></i> Disciplinary
                            </a>

                            <a href="students.php" class="btn btn-secondary m-1">
                                <i class="fas fa-arrow-left me-1"></i> Back to Students
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Disciplinary Popup -->
<?php if($latest_da): ?>
<div class="modal fade" id="daPopupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-danger shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Disciplinary Action Alert</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <h4><?php echo htmlspecialchars($latest_da['action_type']); ?></h4>
                <p><?php echo nl2br(htmlspecialchars($latest_da['description'])); ?></p>
                <span class="badge bg-<?php echo ($latest_da['status']=='Pending')?'warning':(($latest_da['status']=='Resolved')?'success':'danger'); ?>">
                    <?php echo htmlspecialchars($latest_da['status']); ?>
                </span>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="student_da_record.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-danger">
                    View Record
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var daModal = new bootstrap.Modal(document.getElementById('daPopupModal'));
    daModal.show();
});
</script>
<?php endif; ?>

<!-- Edit Student Modal -->
<?php if($can_edit_student): ?>
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="update_student.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($student['email']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($student['address']); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
