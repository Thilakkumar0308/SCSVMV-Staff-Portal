<?php
$page_title = 'On-Duty Management';
require_once 'includes/header.php';

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- ADD REQUEST ---
    if ($action == 'add') {
        if (!has_any_role(['Teacher','HOD','Admin'])) { redirect('dashboard.php'); }
        $student_id = $_POST['student_id'];
        $event_name = sanitize_input($_POST['event_name']);
        $event_date = $_POST['event_date'];
        $venue = sanitize_input($_POST['venue']);
        $reason = sanitize_input($_POST['reason']);

        $stmt = $conn->prepare("INSERT INTO onduty_requests (student_id,event_name,event_date,venue,reason,status) VALUES (?,?,?,?,?,'Pending')");
        $stmt->bind_param("issss",$student_id,$event_name,$event_date,$venue,$reason);
        if ($stmt->execute()) {
            $message = "On-duty request submitted successfully";
            $message_type = "success";
        } else {
            $message = "Error: ".$conn->error;
            $message_type = "danger";
        }
    }

    // --- APPROVE / REJECT ---
    elseif ($action=='approve' || $action=='reject') {
        if (!has_any_role(['HOD','Admin'])) { redirect('dashboard.php'); }
        $id = $_POST['id'] ?? 0;
        if(!$id) { $message="Invalid Request"; $message_type='danger'; }
        else {
            $status = $action=='approve'?'Approved':'Rejected';
            $stmt = $conn->prepare("UPDATE onduty_requests SET status=?, approved_by=?, approved_at=NOW() WHERE id=?");
            $stmt->bind_param("sii",$status,$_SESSION['user_id'],$id);
            if($stmt->execute()) {
                $message = "Request $status successfully";
                $message_type = 'success';
            } else {
                $message = "Error: ".$conn->error;
                $message_type='danger';
            }
        }
    }

    // --- DELETE ---
    elseif ($action=='delete') {
        if(!has_role('Admin')) { redirect('dashboard.php'); }
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM onduty_requests WHERE id=?");
        $stmt->bind_param("i",$id);
        if($stmt->execute()){
            $message="Request deleted successfully"; $message_type='success';
        } else { $message="Error: ".$conn->error; $message_type='danger'; }
    }
}

// Get requests
$onduty_requests=[];
$query = "SELECT odr.*, s.first_name, s.last_name, s.student_id as reg_no, c.class_name, c.section, u.full_name as approved_by_name
          FROM onduty_requests odr
          JOIN students s ON odr.student_id = s.id
          LEFT JOIN classes c ON s.class_id = c.id
          LEFT JOIN users u ON odr.approved_by = u.id
          ORDER BY odr.created_at DESC";
$result = $conn->query($query);
while($row = $result->fetch_assoc()){ $onduty_requests[]=$row; }

// Get students
$students=[];
$res = $conn->query("SELECT id,first_name,last_name,student_id FROM students WHERE status='Active' ORDER BY first_name,last_name");
while($row=$res->fetch_assoc()){ $students[]=$row; }

// Filters
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
?>

<div class="container-fluid">
    <!-- Header with Back + Add buttons -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-3">
        <h2>On-Duty Management</h2>
        <div>
            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addOndutyModal">
                <i class="fas fa-plus me-1"></i> New Request
            </button>
           <button class="btn btn-secondary" onclick="history.back()">
            <i class="fas fa-arrow-left me-1" >
            </i> Back</button>
        </div>
    </div>

    <?php if($message): ?>
        <div class="alert alert-<?php echo $message_type;?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label>Status</label>
                    <select class="form-select" name="status">
                        <option value="">All</option>
                        <option value="Pending" <?php echo $status_filter=='Pending'?'selected':''; ?>>Pending</option>
                        <option value="Approved" <?php echo $status_filter=='Approved'?'selected':''; ?>>Approved</option>
                        <option value="Rejected" <?php echo $status_filter=='Rejected'?'selected':''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Event Date</label>
                    <input type="date" class="form-control" name="date" value="<?php echo $date_filter;?>">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button class="btn btn-outline-primary me-2">Filter</button>
                    <a href="onduty.php" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="ondutyTable">
                    <thead>
                        <tr>
                            <th>Register No</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Event Name</th>
                            <th>Event Date</th>
                            <th>Venue</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($onduty_requests)): ?>
                        <tr><td colspan="9" class="text-center">No records found</td></tr>
                    <?php else: foreach($onduty_requests as $r):
                        if($status_filter && $r['status']!=$status_filter) continue;
                        if($date_filter && $r['event_date']!=$date_filter) continue;
                    ?>
                        <tr>
                            <td><?php echo $r['reg_no']; ?></td>
                            <td><?php echo $r['first_name'].' '.$r['last_name']; ?></td>
                            <td><?php echo $r['class_name'].' '.$r['section']; ?></td>
                            <td><?php echo $r['event_name']; ?></td>
                            <td><?php echo date('M d, Y',strtotime($r['event_date'])); ?></td>
                            <td><?php echo $r['venue']; ?></td>
                            <td><?php echo $r['reason']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $r['status']=='Approved'?'success':($r['status']=='Rejected'?'danger':'warning'); ?>">
                                    <?php echo $r['status']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if($r['status']=='Pending' && has_any_role(['HOD','Admin'])): ?>
                                    <button class="btn btn-success btn-sm" onclick="approveOnduty(<?php echo $r['id']; ?>)">Approve</button>
                                    <button class="btn btn-danger btn-sm" onclick="rejectOnduty(<?php echo $r['id']; ?>)">Reject</button>
                                <?php endif; ?>
                                <?php if(has_role('Admin')): ?>
                                    <button class="btn btn-outline-danger btn-sm" onclick="deleteOnduty(<?php echo $r['id']; ?>)">Delete</button>
                                <?php endif; ?>
                                <?php if($r['approved_by_name']): ?>
                                    <small class="d-block text-muted">By: <?php echo $r['approved_by_name']; ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Request Modal -->
<div class="modal fade" id="addOndutyModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">New On-Duty Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Student</label>
                            <select class="form-select" id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach($students as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo $s['first_name'].' '.$s['last_name'].' ('.$s['student_id'].')'; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Event Name</label>
                            <input type="text" class="form-control" name="event_name" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Event Date</label>
                            <input type="date" class="form-control" name="event_date" required>
                        </div>
                        <div class="col-md-6">
                            <label>Venue</label>
                            <input type="text" class="form-control" name="venue" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Reason</label>
                        <textarea class="form-control" name="reason" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Approve On-Duty Request</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">Are you sure you want to approve this request?</div>
        <input type="hidden" name="action" value="approve">
        <input type="hidden" name="id" id="approve_id">
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Approve</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Reject On-Duty Request</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">Are you sure you want to reject this request?</div>
        <input type="hidden" name="action" value="reject">
        <input type="hidden" name="id" id="reject_id">
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Reject</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Delete On-Duty Request</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">Are you sure you want to delete this request? This action cannot be undone.</div>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="assets/js/onduty.js"></script>
<?php require_once 'includes/footer.php'; ?>
