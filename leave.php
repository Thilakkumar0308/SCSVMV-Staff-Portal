<?php
$page_title = 'Leave Management';
require_once 'includes/header.php';

// Handle actions (approve/reject/add)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'], $_POST['request_id'])) {
        $request_id = (int) $_POST['request_id'];
        $action = $_POST['action'];

        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE leave_requests SET status = 'Approved' WHERE id = ?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE leave_requests SET status = 'Rejected' WHERE id = ?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
        }
    }

    if (isset($_POST['add_leave'])) {
        $student_name = $_POST['student_name'];
        $leave_type = $_POST['leave_type'];
        $from_date = $_POST['from_date'];
        $to_date = $_POST['to_date'];
        $reason = $_POST['reason'];

        $stmt = $conn->prepare("INSERT INTO leave_requests (student_name, leave_type, from_date, to_date, reason, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("sssss", $student_name, $leave_type, $from_date, $to_date, $reason);
        $stmt->execute();
    }
}

// --- Filtering ---
$where = "WHERE 1=1";
$params = [];
$types = "";

if (!empty($_GET['status'])) {
    $where .= " AND status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

if (!empty($_GET['leave_type'])) {
    $where .= " AND leave_type = ?";
    $params[] = $_GET['leave_type'];
    $types .= "s";
}

if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
    // ✅ Better overlap logic
    $where .= " AND (from_date <= ? AND to_date >= ?)";
    $params[] = $_GET['to_date'];
    $params[] = $_GET['from_date'];
    $types .= "ss";
}

$sql = "SELECT * FROM leave_requests $where ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-3">
        <h2>Leave Management</h2>
        <div>
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addLeaveModal">
                <i class="fas fa-plus me-1"></i> Add Leave Request
            </button>
            <button class="btn btn-secondary" onclick="history.back()">
            <i class="fas fa-arrow-left me-1" >
            </i> Back</button>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" class="row g-3 mb-3">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">-- All Status --</option>
                <option value="Pending" <?= ($_GET['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Approved" <?= ($_GET['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
                <option value="Rejected" <?= ($_GET['status'] ?? '') === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="leave_type" class="form-select">
                <option value="">-- All Types --</option>
                <option value="Medical" <?= ($_GET['leave_type'] ?? '') === 'Medical' ? 'selected' : '' ?>>Medical</option>
                <option value="Personal" <?= ($_GET['leave_type'] ?? '') === 'Personal' ? 'selected' : '' ?>>Personal</option>
                <option value="Emergency" <?= ($_GET['leave_type'] ?? '') === 'Emergency' ? 'selected' : '' ?>>Emergency</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <div class="card shadow">
        <div class="card-body">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                        <td><?= htmlspecialchars($row['leave_type']) ?></td>
                        <td><?= $row['from_date'] ?></td>
                        <td><?= $row['to_date'] ?></td>
                        <td><?= htmlspecialchars($row['reason']) ?></td>
                        <td>
                            <?php if ($row['status'] === 'Pending'): ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php elseif ($row['status'] === 'Approved'): ?>
                                <span class="badge bg-success">Approved</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Rejected</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['status'] === 'Pending'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            <?php else: ?>
                                <em>No action</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Leave Modal -->
<div class="modal fade" id="addLeaveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Leave Request</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Student Name</label>
            <input type="text" name="student_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Leave Type</label>
            <select name="leave_type" class="form-select" required>
              <option value="Medical">Medical</option>
              <option value="Personal">Personal</option>
              <option value="Emergency">Emergency</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">From Date</label>
            <input type="date" name="from_date" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">To Date</label>
            <input type="date" name="to_date" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Reason</label>
            <textarea name="reason" class="form-control" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add_leave" class="btn btn-primary">Submit</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
