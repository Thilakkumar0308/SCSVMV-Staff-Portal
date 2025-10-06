<?php
session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';

// Restrict access to Admin & HOD
if (!has_role('Admin') && !has_role('HOD')) {
    redirect('dashboard.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = intval($_POST['teacher_id']);
    $class_id   = intval($_POST['class_id']);
    $subject_id = intval($_POST['subject_id']);

    if ($teacher_id && $class_id && $subject_id) {
        $stmt = $pdo->prepare("INSERT INTO teacher_classes (teacher_id, class_id, subject_id, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$teacher_id, $class_id, $subject_id]);
        $success = "Teacher assigned successfully!";
    } else {
        $error = "All fields are required.";
    }
}

// Fetch teachers (only Teachers role)
$teachers = $pdo->query("SELECT id, name FROM users WHERE role='Teacher' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch classes
$classes = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch subjects
$subjects = $pdo->query("SELECT id, subject_name FROM subjects ORDER BY subject_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch current assignments
$sql = "SELECT tc.id, u.name AS teacher, c.class_name, s.subject_name
        FROM teacher_classes tc
        JOIN users u ON tc.teacher_id = u.id
        JOIN classes c ON tc.class_id = c.id
        JOIN subjects s ON tc.subject_id = s.id
        ORDER BY c.class_name, s.subject_name";
$assignments = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <h2 class="mt-4">Teacher-Class Assignment</h2>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <!-- Assignment Form -->
            <div class="card mb-4">
                <div class="card-header">Assign Teacher to Class & Subject</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Teacher</label>
                                <select name="teacher_id" class="form-select" required>
                                    <option value="">-- Select Teacher --</option>
                                    <?php foreach ($teachers as $t): ?>
                                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Class</label>
                                <select name="class_id" class="form-select" required>
                                    <option value="">-- Select Class --</option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Subject</label>
                                <select name="subject_id" class="form-select" required>
                                    <option value="">-- Select Subject --</option>
                                    <?php foreach ($subjects as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Assign</button>
                    </form>
                </div>
            </div>

            <!-- Assignment List -->
            <div class="card">
                <div class="card-header">Current Assignments</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Teacher</th>
                                <th>Class</th>
                                <th>Subject</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $a): ?>
                                <tr>
                                    <td><?= $a['id'] ?></td>
                                    <td><?= htmlspecialchars($a['teacher']) ?></td>
                                    <td><?= htmlspecialchars($a['class_name']) ?></td>
                                    <td><?= htmlspecialchars($a['subject_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($assignments)): ?>
                                <tr><td colspan="4" class="text-center">No assignments yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
