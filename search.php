<?php
$page_title = 'Global Search';
require_once 'includes/header.php';

$search_results = [];
$alerts = [];
$search_term = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_term'])) {
    $search_term = sanitize_input($_POST['search_term']);
    
    if (!empty($search_term)) {
        // Search for student by registration number
        $stmt = $conn->prepare("
            SELECT s.*, c.class_name, c.section, d.department_name,
                   u.full_name as created_by_name
            FROM students s 
            LEFT JOIN classes c ON s.class_id = c.id 
            LEFT JOIN departments d ON c.department_id = d.id
            LEFT JOIN users u ON s.id = u.id
            WHERE s.student_id = ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ?
        ");
        $search_pattern = "%$search_term%";
        $stmt->bind_param("ssss", $search_term, $search_pattern, $search_pattern, $search_pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $search_results[] = $row;
        }
        
        // Check for disciplinary actions/remarks for this student
        if (!empty($search_results)) {
            $student_id = $search_results[0]['id'];
            
            // Get disciplinary actions
            $stmt = $conn->prepare("
                SELECT da.*, u.full_name as imposed_by_name
                FROM disciplinary_actions da 
                LEFT JOIN users u ON da.imposed_by = u.id
                WHERE da.student_id = ? AND da.status = 'Active'
                ORDER BY da.created_at DESC
            ");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $alerts[] = [
                    'type' => 'disciplinary',
                    'title' => 'Disciplinary Action',
                    'message' => $row['action_type'] . ' - ' . $row['description'],
                    'date' => $row['action_date'],
                    'imposed_by' => $row['imposed_by_name']
                ];
            }
            
            // Get recent leave requests
            $stmt = $conn->prepare("
                SELECT lr.*, lr.status as request_status
                FROM leave_requests lr 
                WHERE lr.student_id = ? AND lr.status = 'Pending'
                ORDER BY lr.created_at DESC
            ");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $alerts[] = [
                    'type' => 'leave',
                    'title' => 'Pending Leave Request',
                    'message' => $row['leave_type'] . ' leave from ' . $row['start_date'] . ' to ' . $row['end_date'],
                    'date' => $row['created_at'],
                    'reason' => $row['reason']
                ];
            }
            
            // Get recent on-duty requests
            $stmt = $conn->prepare("
                SELECT odr.*, odr.status as request_status
                FROM onduty_requests odr 
                WHERE odr.student_id = ? AND odr.status = 'Pending'
                ORDER BY odr.created_at DESC
            ");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $alerts[] = [
                    'type' => 'onduty',
                    'title' => 'Pending On-Duty Request',
                    'message' => $row['event_name'] . ' on ' . $row['event_date'],
                    'date' => $row['created_at'],
                    'venue' => $row['venue']
                ];
            }
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-search me-2"></i>Global Search
                </h1>
            </div>
        </div>
    </div>

    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-10">
                    <label for="search_term" class="form-label">Search by Registration Number, Name, or Email</label>
                    <input type="text" class="form-control form-control-lg" id="search_term" name="search_term" 
                           value="<?php echo htmlspecialchars($search_term); ?>" 
                           placeholder="Enter student registration number, name, or email..." required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-search me-1"></i>Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($alerts)): ?>
    <!-- Alert Notifications -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-exclamation-triangle me-2"></i>Important Notifications
            </h5>
        </div>
        <div class="card-body">
            <?php foreach ($alerts as $alert): ?>
            <div class="alert alert-<?php echo $alert['type'] == 'disciplinary' ? 'danger' : ($alert['type'] == 'leave' ? 'warning' : 'info'); ?> alert-dismissible fade show" role="alert">
                <strong><?php echo $alert['title']; ?>:</strong> <?php echo htmlspecialchars($alert['message']); ?>
                <br><small class="text-muted">
                    Date: <?php echo date('M d, Y', strtotime($alert['date'])); ?>
                    <?php if (isset($alert['imposed_by'])): ?>
                        | Imposed by: <?php echo htmlspecialchars($alert['imposed_by']); ?>
                    <?php endif; ?>
                </small>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($search_results)): ?>
    <!-- Search Results -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-user-graduate me-2"></i>Student Information
            </h5>
        </div>
        <div class="card-body">
            <?php foreach ($search_results as $student): ?>
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                        <h4><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($student['student_id']); ?></p>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Personal Information</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Date of Birth:</strong></td>
                                    <td><?php echo $student['date_of_birth'] ? date('M d, Y', strtotime($student['date_of_birth'])) : 'Not provided'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Gender:</strong></td>
                                    <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Academic Information</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Class:</strong></td>
                                    <td><?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Department:</strong></td>
                                    <td><?php echo htmlspecialchars($student['department_name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo $student['status'] == 'Active' ? 'success' : ($student['status'] == 'Inactive' ? 'warning' : 'info'); ?>">
                                            <?php echo $student['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Admission Date:</strong></td>
                                    <td><?php echo $student['admission_date'] ? date('M d, Y', strtotime($student['admission_date'])) : 'Not provided'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <?php if ($student['address']): ?>
                    <div class="mt-3">
                        <h6>Address</h6>
                        <p><?php echo nl2br(htmlspecialchars($student['address'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($student['parent_name']): ?>
                    <div class="mt-3">
                        <h6>Parent/Guardian Information</h6>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($student['parent_name']); ?></p>
                        <?php if ($student['parent_phone']): ?>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['parent_phone']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="mt-4">
                <h6>Quick Actions</h6>
                <div class="btn-group" role="group">
                    <a href="students.php?edit=<?php echo $student['id']; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-1"></i>Edit Student
                    </a>
                    <a href="leave.php?student=<?php echo $student['id']; ?>" class="btn btn-outline-warning">
                        <i class="fas fa-calendar-times me-1"></i>Leave Requests
                    </a>
                    <a href="onduty.php?student=<?php echo $student['id']; ?>" class="btn btn-outline-info">
                        <i class="fas fa-calendar-check me-1"></i>On-Duty Requests
                    </a>
                    <a href="disciplinary.php?student=<?php echo $student['id']; ?>" class="btn btn-outline-danger">
                        <i class="fas fa-exclamation-triangle me-1"></i>Disciplinary Actions
                    </a>
                    <a href="marks.php?student=<?php echo $student['id']; ?>" class="btn btn-outline-success">
                        <i class="fas fa-chart-line me-1"></i>Marks
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($search_term)): ?>
    <!-- No Results -->
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No students found</h5>
            <p class="text-muted">No students found matching "<?php echo htmlspecialchars($search_term); ?>"</p>
            <p class="text-muted">Try searching with:</p>
            <ul class="list-unstyled text-muted">
                <li>• Student registration number</li>
                <li>• Student first name</li>
                <li>• Student last name</li>
                <li>• Student email address</li>
            </ul>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Auto-focus on search input
document.getElementById('search_term').focus();

// Auto-submit on Enter key
document.getElementById('search_term').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        this.form.submit();
    }
});

// Clear search on Escape key
document.getElementById('search_term').addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        this.value = '';
        this.focus();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
