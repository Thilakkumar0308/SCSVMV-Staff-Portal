<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

$teacher_id = $_SESSION['user_id'] ?? null;

// Stats container
$stats = [
    'total_students'      => 0,
    'total_classes'       => 0,
    'active_disciplinary' => 0,
];

// 1) Get classes assigned to this teacher (so we can show only those classes and link to their student lists)
$teacher_classes = [];
$stmt = $conn->prepare("
    SELECT c.id, c.class_name, c.section, COUNT(s.id) AS student_count
    FROM classes c
    LEFT JOIN students s ON s.class_id = c.id
    JOIN teacher_classes tc ON c.id = tc.class_id
    WHERE tc.teacher_id = ?
    GROUP BY c.id
    ORDER BY c.class_name, c.section
");
if ($stmt) {
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $teacher_classes[] = $row;
    }
    $stmt->close();
}

// 2) Total students in assigned classes
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT s.id) AS total
    FROM students s
    JOIN classes c ON s.class_id = c.id
    JOIN teacher_classes tc ON c.id = tc.class_id
    WHERE tc.teacher_id = ?
");
if ($stmt) {
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stats['total_students'] = $res->fetch_assoc()['total'] ?? 0;
    $stmt->close();
}

// 3) Total classes assigned
$stats['total_classes'] = count($teacher_classes);

// 4) Active disciplinary actions for this teacher's classes
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total_da
    FROM disciplinary_actions da
    JOIN students s ON da.student_id = s.id
    JOIN classes c ON s.class_id = c.id
    JOIN teacher_classes tc ON c.id = tc.class_id
    WHERE tc.teacher_id = ? AND da.status = 'Active'
");
if ($stmt) {
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stats['active_disciplinary'] = $res->fetch_assoc()['total_da'] ?? 0;
    $stmt->close();
}

// 5) Recent disciplinary actions (limit 5) for teacher's classes
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
if ($stmt) {
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_disciplinary[] = $row;
    }
    $stmt->close();
}

include 'includes/header.php';
?>

<div class="container-fluid mt-3">
    <!-- Welcome -->
    <div class="text-center mb-4">
        <h2 class="text-white fw-bold fs-5">
            <i class="fas fa-chalkboard-teacher me-2 text-warning"></i>
            Welcome, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Teacher') ?>!
        </h2>
        <p class="text-white-50 fs-7">Here’s a summary of your class activities. Click a class to view only students in that class.</p>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <?php
        // Students card: link to a filtered students page. We prefer linking to students.php?teacher_id=...,
        // but better UX is to direct to the specific class if the teacher has exactly one class.
        $students_link = 'students.php?teacher_id=' . urlencode($teacher_id);
        if (count($teacher_classes) === 1) {
            // If only one class assigned, link directly to that class's student list
            $students_link = 'students.php?class_id=' . urlencode($teacher_classes[0]['id']);
        }
        $cards = [
            [
                'title' => 'Students',
                'icon'  => 'fas fa-users',
                'value' => $stats['total_students'],
                'color' => 'primary',
                'link'  => $students_link
            ],
            [
                'title' => 'Your Classes',
                'icon'  => 'fas fa-chalkboard-teacher',
                'value' => $stats['total_classes'],
                'color' => 'warning',
                'link'  => 'class_management.php'
            ],
            [
                'title' => 'Active Disciplinary',
                'icon'  => 'fas fa-exclamation-triangle',
                'value' => $stats['active_disciplinary'],
                'color' => 'danger',
                'link'  => 'disciplinary.php'
            ],
        ];

        foreach ($cards as $card): ?>
            <div class="col-6 col-sm-6 col-md-3">
                <a href="<?= htmlspecialchars($card['link']) ?>" class="text-decoration-none">
                    <div class="card stat-card border-0 text-center shadow-sm h-100">
                        <div class="card-body py-4">
                            <div class="icon-circle bg-<?= htmlspecialchars($card['color']) ?> bg-opacity-10 mb-3">
                                <i class="<?= htmlspecialchars($card['icon']) ?> text-<?= htmlspecialchars($card['color']) ?> fa-2x"></i>
                            </div>
                            <h6 class="fw-semibold text-secondary mb-1"><?= htmlspecialchars($card['title']) ?></h6>
                            <h3 class="fw-bold text-dark mb-0"><?= htmlspecialchars($card['value']) ?></h3>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3">
        <!-- Teacher's Classes (explicit list so teacher can click the exact class e.g., "MCA II") -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Your Classes</h6>
                    <a href="class_management.php" class="btn btn-sm btn-light">View All</a>
                </div>
                <div class="card-body p-2">
                    <?php if (empty($teacher_classes)): ?>
                        <p class="text-muted small m-0">No classes assigned to you.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($teacher_classes as $class): ?>
                                <a href="students.php?class_id=<?= urlencode($class['id']) ?>"
                                   class="list-group-item list-group-item-action border-0 mb-2 shadow-sm rounded hover-glow flex-column flex-sm-row">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong class="d-block"><?= htmlspecialchars($class['class_name']) ?></strong>
                                            <span class="text-muted small d-block">Section: <?= htmlspecialchars($class['section']) ?></span>
                                        </div>
                                        <span class="badge bg-info mt-1 mt-sm-0">
                                            <?= htmlspecialchars($class['student_count']) ?> Students
                                        </span>
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
                    <?php if (empty($recent_disciplinary)): ?>
                        <p class="text-muted small m-0">No disciplinary actions.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recent_disciplinary as $disc): ?>
                                <a href="student_da_record.php?student_id=<?= urlencode($disc['student_id']) ?>"
                                   class="list-group-item list-group-item-action border-0 mb-2 shadow-sm rounded hover-glow flex-column flex-sm-row">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong class="d-block"><?= htmlspecialchars($disc['first_name'] . ' ' . $disc['last_name']) ?></strong>
                                            <span class="text-muted small d-block">
                                                <b>Class:</b> <?= htmlspecialchars($disc['class_name'] . ' ' . $disc['section']) ?>
                                            </span>
                                            <span class="text-muted small d-block">
                                                <b>Reason:</b> <?= htmlspecialchars($disc['description'] ?: 'No reason provided') ?>
                                            </span>
                                        </div>
                                        <span class="badge bg-<?= ($disc['status'] === 'Active') ? 'danger' : 'secondary' ?> mt-1 mt-sm-0">
                                            <?= htmlspecialchars($disc['status']) ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Overview / Placeholder -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Overview</h6>
                    <a href="#" class="btn btn-sm btn-light invisible">View</a>
                </div>
                <div class="card-body p-3">
                    <p class="small text-muted m-0">Use this area to show teacher-specific stats (e.g., messages, schedules, or recent submissions).</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Card Glow & Animation */
.stat-card {
    border-radius: 18px;
    transition: all 0.3s ease;
    background-color: #fff;
}
.stat-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

/* Icon Circle */
.icon-circle {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    background-color: rgba(255,255,255,0.3);
    transition: all 0.3s ease;
}
.stat-card:hover .icon-circle {
    transform: scale(1.05);
}

/* Hover list for list-group items */
.hover-glow {
    transition: transform 0.25s, box-shadow 0.25s, background-color 0.25s;
    background-color: #fff;
}
.hover-glow:hover {
    transform: translateY(-4px);
    cursor: pointer;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

/* Responsive tweaks */
@media (max-width: 480px) {
    .card h3 { font-size: 1.1rem !important; }
    .card h6 { font-size: 0.85rem !important; }
    .list-group-item { font-size: 0.85rem; padding: 0.6rem; }
    .badge { font-size: 0.7rem; padding: 0.25em 0.4em; }
}
</style>

<?php include 'includes/footer.php'; ?>