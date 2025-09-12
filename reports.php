<?php
$page_title = 'Reports';
require_once 'includes/header.php';

// Get filter parameters
$report_type = isset($_GET['type']) ? $_GET['type'] : 'students';
$class_filter = isset($_GET['class']) ? $_GET['class'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Get classes for filter
$classes = [];
$result = $conn->query("SELECT * FROM classes ORDER BY class_name, section");
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

// Generate reports based on type
$report_data = [];
$report_title = '';

switch ($report_type) {
    case 'students':
        $report_title = 'Student Report';
        $query = "SELECT s.*, c.class_name, c.section FROM students s LEFT JOIN classes c ON s.class_id = c.id";
        $params = [];
        $types = '';
        
        if ($class_filter) {
            $query .= " WHERE s.class_id = ?";
            $params[] = $class_filter;
            $types .= 'i';
        }
        
        $query .= " ORDER BY s.first_name, s.last_name";
        
        if (!empty($params)) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($query);
        }
        
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
        break;
        
    case 'attendance':
        $report_title = 'Attendance Report';
        $query = "
            SELECT s.first_name, s.last_name, s.student_id, c.class_name, c.section,
                   COUNT(lr.id) as total_leaves,
                   SUM(CASE WHEN lr.status = 'Approved' THEN 1 ELSE 0 END) as approved_leaves,
                   SUM(CASE WHEN lr.status = 'Pending' THEN 1 ELSE 0 END) as pending_leaves
            FROM students s 
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN leave_requests lr ON s.id = lr.student_id
        ";
        
        $params = [];
        $types = '';
        $where_conditions = [];
        
        if ($class_filter) {
            $where_conditions[] = "s.class_id = ?";
            $params[] = $class_filter;
            $types .= 'i';
        }
        
        if ($date_from) {
            $where_conditions[] = "lr.start_date >= ?";
            $params[] = $date_from;
            $types .= 's';
        }
        
        if ($date_to) {
            $where_conditions[] = "lr.end_date <= ?";
            $params[] = $date_to;
            $types .= 's';
        }
        
        if (!empty($where_conditions)) {
            $query .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        $query .= " GROUP BY s.id ORDER BY s.first_name, s.last_name";
        
        if (!empty($params)) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($query);
        }
        
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
        break;
        
    case 'marks':
        $report_title = 'Marks Report';
        $query = "
            SELECT s.first_name, s.last_name, s.student_id, c.class_name, c.section,
                   sub.subject_name, m.exam_type, m.marks_obtained, m.total_marks,
                   ROUND((m.marks_obtained / m.total_marks) * 100, 2) as percentage,
                   m.exam_date
            FROM marks m
            JOIN students s ON m.student_id = s.id
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN subjects sub ON m.subject_id = sub.id
        ";
        
        $params = [];
        $types = '';
        $where_conditions = [];
        
        if ($class_filter) {
            $where_conditions[] = "s.class_id = ?";
            $params[] = $class_filter;
            $types .= 'i';
        }
        
        if ($date_from) {
            $where_conditions[] = "m.exam_date >= ?";
            $params[] = $date_from;
            $types .= 's';
        }
        
        if ($date_to) {
            $where_conditions[] = "m.exam_date <= ?";
            $params[] = $date_to;
            $types .= 's';
        }
        
        if (!empty($where_conditions)) {
            $query .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        $query .= " ORDER BY s.first_name, s.last_name, m.exam_date DESC";
        
        if (!empty($params)) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($query);
        }
        
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
        break;
        
    case 'disciplinary':
        $report_title = 'Disciplinary Actions Report';
        $query = "
            SELECT s.first_name, s.last_name, s.student_id, c.class_name, c.section,
                   da.action_type, da.description, da.action_date, da.status,
                   u.full_name as imposed_by
            FROM disciplinary_actions da
            JOIN students s ON da.student_id = s.id
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN users u ON da.imposed_by = u.id
        ";
        
        $params = [];
        $types = '';
        $where_conditions = [];
        
        if ($class_filter) {
            $where_conditions[] = "s.class_id = ?";
            $params[] = $class_filter;
            $types .= 'i';
        }
        
        if ($date_from) {
            $where_conditions[] = "da.action_date >= ?";
            $params[] = $date_from;
            $types .= 's';
        }
        
        if ($date_to) {
            $where_conditions[] = "da.action_date <= ?";
            $params[] = $date_to;
            $types .= 's';
        }
        
        if (!empty($where_conditions)) {
            $query .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        $query .= " ORDER BY da.action_date DESC";
        
        if (!empty($params)) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($query);
        }
        
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
        break;
        
    case 'summary':
        $report_title = 'Summary Report';
        // Get summary statistics
        $summary_stats = [];
        
        // Total students
        $result = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'Active'");
        $summary_stats['total_students'] = $result->fetch_assoc()['total'];
        
        // Total classes
        $result = $conn->query("SELECT COUNT(*) as total FROM classes");
        $summary_stats['total_classes'] = $result->fetch_assoc()['total'];
        
        // Pending leave requests
        $result = $conn->query("SELECT COUNT(*) as total FROM leave_requests WHERE status = 'Pending'");
        $summary_stats['pending_leaves'] = $result->fetch_assoc()['total'];
        
        // Pending on-duty requests
        $result = $conn->query("SELECT COUNT(*) as total FROM onduty_requests WHERE status = 'Pending'");
        $summary_stats['pending_onduty'] = $result->fetch_assoc()['total'];
        
        // Active disciplinary actions
        $result = $conn->query("SELECT COUNT(*) as total FROM disciplinary_actions WHERE status = 'Active'");
        $summary_stats['active_disciplinary'] = $result->fetch_assoc()['total'];
        
        // Average marks
        $result = $conn->query("SELECT AVG((marks_obtained / total_marks) * 100) as avg_marks FROM marks");
        $summary_stats['avg_marks'] = $result->fetch_assoc()['avg_marks'];
        
        $report_data = $summary_stats;
        break;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Reports</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-success" onclick="exportReport()">
                        <i class="fas fa-download me-1"></i>Export
                    </button>
                    <button type="button" class="btn btn-primary" onclick="printReport()">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="type" class="form-label">Report Type</label>
                    <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                        <option value="students" <?php echo $report_type == 'students' ? 'selected' : ''; ?>>Student Report</option>
                        <option value="attendance" <?php echo $report_type == 'attendance' ? 'selected' : ''; ?>>Attendance Report</option>
                        <option value="marks" <?php echo $report_type == 'marks' ? 'selected' : ''; ?>>Marks Report</option>
                        <option value="disciplinary" <?php echo $report_type == 'disciplinary' ? 'selected' : ''; ?>>Disciplinary Actions</option>
                        <option value="summary" <?php echo $report_type == 'summary' ? 'selected' : ''; ?>>Summary Report</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="class" class="form-label">Class</label>
                    <select class="form-select" id="class" name="class">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php echo $class_filter == $class['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary">Generate</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Content -->
    <div class="card shadow" id="reportContent">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-chart-bar me-2"></i><?php echo $report_title; ?>
                <small class="text-muted ms-2">
                    Generated on <?php echo date('F d, Y \a\t g:i A'); ?>
                </small>
            </h5>
        </div>
        <div class="card-body">
            <?php if ($report_type == 'summary'): ?>
                <!-- Summary Report -->
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-primary">
                            <div class="card-body text-center">
                                <h3 class="text-primary"><?php echo $summary_stats['total_students']; ?></h3>
                                <p class="mb-0">Total Students</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-success">
                            <div class="card-body text-center">
                                <h3 class="text-success"><?php echo $summary_stats['total_classes']; ?></h3>
                                <p class="mb-0">Total Classes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-warning">
                            <div class="card-body text-center">
                                <h3 class="text-warning"><?php echo $summary_stats['pending_leaves']; ?></h3>
                                <p class="mb-0">Pending Leaves</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-info">
                            <div class="card-body text-center">
                                <h3 class="text-info"><?php echo number_format($summary_stats['avg_marks'], 1); ?>%</h3>
                                <p class="mb-0">Average Marks</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h6>Pending On-Duty Requests</h6>
                        <div class="progress mb-3">
                            <div class="progress-bar bg-warning" style="width: 100%">
                                <?php echo $summary_stats['pending_onduty']; ?> requests
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Active Disciplinary Actions</h6>
                        <div class="progress mb-3">
                            <div class="progress-bar bg-danger" style="width: 100%">
                                <?php echo $summary_stats['active_disciplinary']; ?> actions
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Data Reports -->
                <?php if (empty($report_data)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No data available for the selected criteria</h5>
                        <p class="text-muted">Try adjusting your filters or select a different report type.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped" id="reportTable">
                            <thead>
                                <tr>
                                    <?php if ($report_type == 'students'): ?>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Class</th>
                                        <th>Status</th>
                                        <th>Admission Date</th>
                                    <?php elseif ($report_type == 'attendance'): ?>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Class</th>
                                        <th>Total Leaves</th>
                                        <th>Approved Leaves</th>
                                        <th>Pending Leaves</th>
                                    <?php elseif ($report_type == 'marks'): ?>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Class</th>
                                        <th>Subject</th>
                                        <th>Exam Type</th>
                                        <th>Marks</th>
                                        <th>Percentage</th>
                                        <th>Exam Date</th>
                                    <?php elseif ($report_type == 'disciplinary'): ?>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Class</th>
                                        <th>Action Type</th>
                                        <th>Description</th>
                                        <th>Action Date</th>
                                        <th>Status</th>
                                        <th>Imposed By</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <?php if ($report_type == 'students'): ?>
                                        <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($row['class_name'] . ' ' . $row['section']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['status'] == 'Active' ? 'success' : ($row['status'] == 'Inactive' ? 'warning' : 'info'); ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($row['admission_date'])); ?></td>
                                        
                                    <?php elseif ($report_type == 'attendance'): ?>
                                        <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['class_name'] . ' ' . $row['section']); ?></td>
                                        <td><span class="badge bg-primary"><?php echo $row['total_leaves']; ?></span></td>
                                        <td><span class="badge bg-success"><?php echo $row['approved_leaves']; ?></span></td>
                                        <td><span class="badge bg-warning"><?php echo $row['pending_leaves']; ?></span></td>
                                        
                                    <?php elseif ($report_type == 'marks'): ?>
                                        <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['class_name'] . ' ' . $row['section']); ?></td>
                                        <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $row['exam_type'] == 'Final' ? 'danger' : 
                                                    ($row['exam_type'] == 'Midterm' ? 'warning' : 
                                                    ($row['exam_type'] == 'Quiz' ? 'info' : 'secondary')); 
                                            ?>">
                                                <?php echo $row['exam_type']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $row['marks_obtained']; ?> / <?php echo $row['total_marks']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['percentage'] >= 90 ? 'success' : ($row['percentage'] >= 80 ? 'info' : ($row['percentage'] >= 70 ? 'warning' : 'danger')); ?>">
                                                <?php echo number_format($row['percentage'], 1); ?>%
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($row['exam_date'])); ?></td>
                                        
                                    <?php elseif ($report_type == 'disciplinary'): ?>
                                        <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['class_name'] . ' ' . $row['section']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $row['action_type'] == 'Warning' ? 'warning' : 
                                                    ($row['action_type'] == 'Suspension' ? 'danger' : 
                                                    ($row['action_type'] == 'Expulsion' ? 'dark' : 'info')); 
                                            ?>">
                                                <?php echo $row['action_type']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['action_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['status'] == 'Active' ? 'danger' : ($row['status'] == 'Resolved' ? 'success' : 'secondary'); ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['imposed_by']); ?></td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function exportReport() {
    // Create CSV content
    var table = document.getElementById('reportTable');
    if (!table) {
        alert('No data to export');
        return;
    }
    
    var csv = [];
    var rows = table.querySelectorAll('tr');
    
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (var j = 0; j < cols.length; j++) {
            row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        }
        
        csv.push(row.join(','));
    }
    
    // Download CSV
    var csvContent = csv.join('\n');
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    var url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', '<?php echo strtolower(str_replace(' ', '_', $report_title)); ?>_<?php echo date('Y-m-d'); ?>.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function printReport() {
    var printContent = document.getElementById('reportContent').innerHTML;
    var originalContent = document.body.innerHTML;
    
    document.body.innerHTML = `
        <html>
            <head>
                <title><?php echo $report_title; ?></title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    @media print {
                        .btn { display: none !important; }
                        .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
                    }
                </style>
            </head>
            <body>
                <div class="container-fluid">
                    <h2 class="mb-3"><?php echo $report_title; ?></h2>
                    <p class="text-muted mb-4">Generated on <?php echo date('F d, Y \a\t g:i A'); ?></p>
                    ${printContent}
                </div>
            </body>
        </html>
    `;
    
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}

// Initialize DataTable for non-summary reports
$(document).ready(function() {
    if (document.getElementById('reportTable')) {
        $('#reportTable').DataTable({
            "pageLength": 25,
            "order": [[ 0, "asc" ]],
            "dom": 'Bfrtip',
            "buttons": [
                {
                    extend: 'csv',
                    text: 'Export CSV',
                    className: 'btn btn-sm btn-outline-primary'
                },
                {
                    extend: 'print',
                    text: 'Print',
                    className: 'btn btn-sm btn-outline-secondary'
                }
            ]
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>

