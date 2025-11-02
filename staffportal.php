<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/db.php';
require_once 'includes/functions.php';
require_login();

// Only allow teachers
if (!has_role('Teacher')) {
    redirect('dashboard.php');
}

$teacher_id = $_SESSION['user_id'];

// Stats
$stats = [];

// Total students in assigned classes
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT s.id) AS total
    FROM students s
    JOIN teacher_classes tc ON s.class_id = tc.class_id
    WHERE tc.teacher_id = ?
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$stats['total_students'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Attendance today
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT a.id) AS total
    FROM attendance a
    JOIN teacher_classes tc ON a.class_id = tc.class_id
    WHERE tc.teacher_id = ? AND DATE(a.attendance_date) = CURDATE()
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$stats['today_attendance'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Pending leave requests
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM leave_requests lr
    JOIN students s ON lr.student_id = s.id
    JOIN teacher_classes tc ON s.class_id = tc.class_id
    WHERE tc.teacher_id = ? AND lr.status='Pending'
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$stats['pending_leaves'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Pending on-duty requests
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM onduty_requests odr
    JOIN students s ON odr.student_id = s.id
    JOIN teacher_classes tc ON s.class_id = tc.class_id
    WHERE tc.teacher_id = ? AND odr.status='Pending'
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$stats['pending_onduty'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Recent leave requests
$recent_leaves = [];
$stmt = $conn->prepare("
    SELECT lr.*, s.first_name, s.last_name, s.student_id, c.class_name, c.section
    FROM leave_requests lr
    JOIN students s ON lr.student_id = s.id
    JOIN classes c ON s.class_id = c.id
    JOIN teacher_classes tc ON c.id = tc.class_id
    WHERE tc.teacher_id = ?
    ORDER BY lr.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_leaves[] = $row;
}

// Recent on-duty requests
$recent_onduty = [];
$stmt = $conn->prepare("
    SELECT odr.*, s.first_name, s.last_name, s.student_id, c.class_name, c.section
    FROM onduty_requests odr
    JOIN students s ON odr.student_id = s.id
    JOIN classes c ON s.class_id = c.id
    JOIN teacher_classes tc ON c.id = tc.class_id
    WHERE tc.teacher_id = ?
    ORDER BY odr.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_onduty[] = $row;
}

// Recent disciplinary actions
$recent_disciplinary = [];
$stmt = $conn->prepare("
    SELECT da.*, s.first_name, s.last_name, s.student_id, c.class_name, c.section
    FROM disciplinary_actions da
    JOIN students s ON da.student_id = s.id
    JOIN classes c ON s.class_id = c.id
    JOIN teacher_classes tc ON c.id = tc.class_id
    WHERE tc.teacher_id = ?
    ORDER BY da.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_disciplinary[] = $row;
}

include 'includes/header.php';
?>

<div class="container-fluid mt-3">

    <!-- Welcome -->
    <div class="text-center mb-4">
        <h2 class="text-white fw-bold fs-5">
            <i class="fas fa-chalkboard-teacher me-2"></i>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>!
        </h2>
        <p class="text-white-50 fs-7">Here’s a summary of your class activities.</p>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <?php 
        $cards = [
            ['title'=>'Students', 'icon'=>'fas fa-users', 'value'=>$stats['total_students'], 'color'=>'primary', 'link'=>'students.php'],
            ['title'=>"Today's Attendance", 'icon'=>'fas fa-clipboard-check', 'value'=>$stats['today_attendance'], 'color'=>'info', 'link'=>'attendance.php'],
            ['title'=>'Pending Leaves', 'icon'=>'fas fa-calendar-times', 'value'=>$stats['pending_leaves'], 'color'=>'warning', 'link'=>'leave.php'],
            ['title'=>'Pending On-Duty', 'icon'=>'fas fa-calendar-check', 'value'=>$stats['pending_onduty'], 'color'=>'success', 'link'=>'onduty.php'],
        ];
        foreach($cards as $card): ?>
        <div class="col-6 col-sm-6 col-md-3 col-lg-3 col-xl-3">
            <a href="<?= $card['link'] ?>" class="text-decoration-none">
                <div class="card shadow-sm border-0 text-center h-100 hover-glow">
                    <div class="card-body py-3">
                        <i class="<?= $card['icon'] ?> fa-2x text-<?= $card['color'] ?> mb-2"></i>
                        <h6 class="fw-bold text-secondary mb-1"><?= $card['title'] ?></h6>
                        <h4 class="fw-bold mb-0"><?= $card['value'] ?></h4>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3">
        <!-- Recent Leave Requests -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-calendar-times me-2"></i>Recent Leave Requests</h6>
                    <a href="leave.php" class="btn btn-sm btn-light">View All</a>
                </div>
                <div class="card-body p-2">
                    <?php if(empty($recent_leaves)): ?>
                        <p class="text-muted small m-0">No recent leave requests.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach($recent_leaves as $leave): ?>
                                <a href="student_leave_record.php?student_id=<?= urlencode($leave['student_id']) ?>" 
                                   class="list-group-item list-group-item-action border-0 mb-2 shadow-sm rounded hover-glow flex-column flex-sm-row">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong class="d-block"><?= htmlspecialchars($leave['first_name'].' '.$leave['last_name']) ?></strong>
                                            <span class="text-muted small d-block"><?= htmlspecialchars($leave['class_name'].' '.$leave['section']) ?></span>
                                            <span class="text-muted small d-block"><?= htmlspecialchars($leave['leave_type']) ?></span>
                                        </div>
                                        <span class="badge bg-<?= $leave['status']=='Approved'?'success':($leave['status']=='Rejected'?'danger':'warning') ?> mt-1 mt-sm-0"><?= $leave['status'] ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent On-Duty Requests -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Recent On-Duty Requests</h6>
                    <a href="onduty.php" class="btn btn-sm btn-light">View All</a>
                </div>
                <div class="card-body p-2">
                    <?php if(empty($recent_onduty)): ?>
                        <p class="text-muted small m-0">No recent on-duty requests.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach($recent_onduty as $od): ?>
                                <a href="student_onduty_record.php?student_id=<?= urlencode($od['student_id']) ?>" 
                                   class="list-group-item list-group-item-action border-0 mb-2 shadow-sm rounded hover-glow flex-column flex-sm-row">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong class="d-block"><?= htmlspecialchars($od['first_name'].' '.$od['last_name']) ?></strong>
                                            <span class="text-muted small d-block"><?= htmlspecialchars($od['class_name'].' '.$od['section']) ?></span>
                                            <span class="text-muted small d-block"><?= htmlspecialchars($od['event_name']) ?></span>
                                        </div>
                                        <span class="badge bg-<?= $od['status']=='Approved'?'success':($od['status']=='Rejected'?'danger':'warning') ?> mt-1 mt-sm-0"><?= $od['status'] ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Disciplinary Actions -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Recent Disciplinary Actions</h6>
                    <a href="disciplinary.php" class="btn btn-sm btn-light">View All</a>
                </div>
                <div class="card-body p-2">
                    <?php if(empty($recent_disciplinary)): ?>
                        <p class="text-muted small m-0">No disciplinary actions.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach($recent_disciplinary as $disc): ?>
                                <a href="student_da_record.php?student_id=<?= urlencode($disc['student_id']) ?>" 
                                   class="list-group-item list-group-item-action border-0 mb-2 shadow-sm rounded hover-glow flex-column flex-sm-row">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong class="d-block"><?= htmlspecialchars($disc['first_name'].' '.$disc['last_name']) ?></strong>
                                            <span class="text-muted small d-block"><?= htmlspecialchars($disc['class_name'].' '.$disc['section']) ?></span>
                                            <span class="text-muted small d-block"><?= htmlspecialchars($disc['description'] ?: 'No reason provided') ?></span>
                                        </div>
                                        <span class="badge bg-<?= $disc['status']=='Active'?'danger':'secondary' ?> mt-1 mt-sm-0"><?= htmlspecialchars($disc['status']) ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.hover-glow {
    transition: transform 0.3s, box-shadow 0.3s, background-color 0.3s;
    position: relative;
    background-color: #fff; 
}
.hover-glow:hover {
    transform: translateY(-5px);
    cursor: pointer;
    background-color: rgba(255, 166, 0, 1);
    box-shadow: 0 0 20px 8px rgba(255,165,0,0.6);
}
@media (max-width: 480px) {
    .card h4 { font-size: 1rem !important; }
    .card h6 { font-size: 0.8rem !important; }
    .list-group-item { padding: 0.5rem !important; font-size: 0.7rem; }
    .badge { font-size: 0.6rem; padding: 0.25em 0.4em; }
}
</style>

<?php include 'includes/footer.php'; ?>
