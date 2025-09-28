<?php
session_start();
require_once 'config/db.php';
require_login();

$page_title = "Classes";
require_once 'includes/header.php';

// Fetch classes
$res = $conn->query("SELECT * FROM classes ORDER BY id ASC"); // changed ORDER BY to id
$classes = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $classes[] = $row;
    }
}
?>

<div class="page-wrapper d-flex flex-column min-vh-100">

    <div class="container-fluid py-4 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-light">🏫 Classes</h2>
            <a href="dashboard.php" class="btn btn-gradient btn-sm"><i class="fas fa-arrow-left me-2"></i> Back</a>
        </div>

        <div class="card glass-card">
            <div class="card-header">
                <span>All Classes</span>
            </div>
            <div class="card-body">
                <?php if (empty($classes)): ?>
                    <p class="text-muted">No classes found.</p>
                <?php else: ?>
                    <table class="table table-dark table-sm align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Class Name</th>
                                <th>Section</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($classes as $c): ?>
                            <tr>
                                <td><?php echo $c['id']; ?></td>
                                <td><?php echo isset($c['class_name']) ? $c['class_name'] : 'N/A'; ?></td>
                                <td><?php echo isset($c['section']) ? $c['section'] : 'N/A'; ?></td>
                                <td><?php echo isset($c['created_at']) ? date('M d, Y', strtotime($c['created_at'])) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>



<?php require_once 'includes/footer.php'; ?>
