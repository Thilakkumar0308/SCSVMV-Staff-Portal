<?php
session_start();
require_once 'config/db.php';
require_login();

// Redirect admin
if (has_role('Admin')) {
    redirect('admin_dashboard.php');
}

$page_title = 'Dashboard';

// ---------- Stats ----------
$stats = [
    'total_students' => 0,
    'total_classes' => 0,
    'pending_leaves' => 0,
    'pending_onduty' => 0
];

$result = $conn->query("SELECT COUNT(*) as total FROM students WHERE status='Active'");
if ($result) $stats['total_students'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM classes");
if ($result) $stats['total_classes'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM leave_requests WHERE status='Pending'");
if ($result) $stats['pending_leaves'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM onduty_requests WHERE status='Pending'");
if ($result) $stats['pending_onduty'] = $result->fetch_assoc()['total'];

// ---------- Recent ----------
$recent_leaves = [];
$res = $conn->query("SELECT lr.*, s.first_name, s.last_name 
    FROM leave_requests lr 
    JOIN students s ON lr.student_id=s.id 
    ORDER BY lr.created_at DESC LIMIT 5");
if ($res) while($row=$res->fetch_assoc()) $recent_leaves[]=$row;

$recent_disciplinary = [];
$res = $conn->query("SELECT da.*, s.first_name, s.last_name, u.full_name as imposed_by_name 
    FROM disciplinary_actions da 
    JOIN students s ON da.student_id=s.id 
    JOIN users u ON da.imposed_by=u.id 
    ORDER BY da.created_at DESC LIMIT 5");
if ($res) while($row=$res->fetch_assoc()) $recent_disciplinary[]=$row;

$top_students = [];
$res = $conn->query("SELECT s.first_name,s.last_name,s.student_id,
    AVG((m.marks_obtained/m.total_marks)*100) as avg_percentage
    FROM students s 
    JOIN marks m ON s.id=m.student_id 
    WHERE s.status='Active'
    GROUP BY s.id ORDER BY avg_percentage DESC LIMIT 5");
if ($res) while($row=$res->fetch_assoc()) $top_students[]=$row;

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-light">📊 Dashboard</h2>
        <a href="#" class="btn btn-gradient btn-sm"><i class="fas fa-download me-2"></i> Export</a>
    </div>

    <!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <a href="students.php" class="text-decoration-none">
            <div class="card dashboard-card gradient-blue">
                <div class="card-body">
                    <h6>Total Students</h6>
                    <h3><?php echo $stats['total_students']; ?></h3>
                    <i class="fas fa-users card-icon"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="classes.php" class="text-decoration-none">
            <div class="card dashboard-card gradient-green">
                <div class="card-body">
                    <h6>Total Classes</h6>
                    <h3><?php echo $stats['total_classes']; ?></h3>
                    <i class="fas fa-chalkboard-teacher card-icon"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="leave.php" class="text-decoration-none">
            <div class="card dashboard-card gradient-yellow">
                <div class="card-body">
                    <h6>Pending Leaves</h6>
                    <h3><?php echo $stats['pending_leaves']; ?></h3>
                    <i class="fas fa-calendar-times card-icon"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="onduty.php" class="text-decoration-none">
            <div class="card dashboard-card gradient-purple">
                <div class="card-body">
                    <h6>Pending On-Duty</h6>
                    <h3><?php echo $stats['pending_onduty']; ?></h3>
                    <i class="fas fa-calendar-check card-icon"></i>
                </div>
            </div>
        </a>
    </div>
</div>


    <!-- Recent Leaves & Disciplinary -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card glass-card">
                <div class="card-header d-flex justify-content-between">
                    <span>Recent Leave Requests</span>
                    <a href="leave.php" class="btn btn-sm btn-gradient">View All</a>
                </div>
                <div class="card-body">
                    <?php if(empty($recent_leaves)): ?>
                        <p class="text-muted">No recent leave requests</p>
                    <?php else: ?>
                        <table class="table table-dark table-sm align-middle">
                            <thead><tr><th>Student</th><th>Type</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach($recent_leaves as $leave): ?>
                                <tr>
                                    <td><?php echo $leave['first_name'].' '.$leave['last_name']; ?></td>
                                    <td><?php echo $leave['leave_type']; ?></td>
                                    <td><?php echo date('M d',strtotime($leave['start_date'])); ?></td>
                                    <td><span class="badge bg-<?php echo $leave['status']=='Approved'?'success':($leave['status']=='Rejected'?'danger':'warning'); ?>">
                                        <?php echo $leave['status']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card glass-card">
                <div class="card-header d-flex justify-content-between">
                    <span>Recent Disciplinary Actions</span>
                    <a href="disciplinary.php" class="btn btn-sm btn-gradient">View All</a>
                </div>
                <div class="card-body">
                    <?php if(empty($recent_disciplinary)): ?>
                        <p class="text-muted">No recent disciplinary actions</p>
                    <?php else: ?>
                        <table class="table table-dark table-sm align-middle">
                            <thead><tr><th>Student</th><th>Action</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach($recent_disciplinary as $d): ?>
                                <tr>
                                    <td><?php echo $d['first_name'].' '.$d['last_name']; ?></td>
                                    <td><?php echo $d['action_type']; ?></td>
                                    <td><?php echo date('M d',strtotime($d['action_date'])); ?></td>
                                    <td><span class="badge bg-<?php echo $d['status']=='Active'?'danger':($d['status']=='Resolved'?'success':'secondary'); ?>">
                                        <?php echo $d['status']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Students -->
    <?php if(!empty($top_students)): ?>
    <div class="card glass-card mt-4">
        <div class="card-header d-flex justify-content-between">
            <span>Top Performing Students</span>
            <a href="marks.php" class="btn btn-sm btn-gradient">View All</a>
        </div>
        <div class="card-body">
            <table class="table table-dark table-sm align-middle">
                <thead><tr><th>Rank</th><th>Student</th><th>ID</th><th>Avg %</th></tr></thead>
                <tbody>
                <?php foreach($top_students as $i=>$s): ?>
                    <tr>
                        <td><span class="badge bg-<?php echo $i==0?'warning':($i==1?'secondary':($i==2?'info':'light')); ?>">#<?php echo $i+1; ?></span></td>
                        <td><?php echo $s['first_name'].' '.$s['last_name']; ?></td>
                        <td><?php echo $s['student_id']; ?></td>
                        <td><?php echo number_format($s['avg_percentage'],1); ?>%</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>


<?php require_once 'includes/footer.php'; ?>
