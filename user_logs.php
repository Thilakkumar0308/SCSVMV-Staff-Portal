<?php
date_default_timezone_set('Asia/Kolkata'); // Set PHP default timezone
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';
require_login(); // ensure user is logged in

// Only Admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    redirect('dashboard.php');
}

$page_title = 'User Logs';
require_once 'includes/header.php';

// Fetch filter inputs
$user_filter  = isset($_GET['user']) ? (int)$_GET['user'] : 0;
$date_from    = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to      = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Prepare users for dropdown
$users = [];
$resUsers = $conn->query("SELECT id, full_name, username FROM users ORDER BY full_name");
while ($r = $resUsers->fetch_assoc()) $users[] = $r;

// Fetch user logs for sessions
$logs_sql = "SELECT ul.*, u.full_name, u.username 
             FROM user_logs ul 
             JOIN users u ON ul.user_id = u.id";

$conditions = [];
$params = [];
$types = '';

if ($user_filter) {
    $conditions[] = "ul.user_id = ?";
    $params[] = $user_filter;
    $types .= 'i';
}
if ($date_from) {
    $conditions[] = "DATE(ul.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}
if ($date_to) {
    $conditions[] = "DATE(ul.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

if (!empty($conditions)) {
    $logs_sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$logs_sql .= " ORDER BY ul.user_id, ul.created_at ASC";

$stmt = $conn->prepare($logs_sql);
if ($stmt === false) {
    echo '<div class="alert alert-danger">Query error: ' . htmlspecialchars($conn->error) . '</div>';
    require_once 'includes/footer.php';
    exit;
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Aggregate sessions per user
$sessions = [];
$userSessions = []; // Keep track of open sessions per user

while ($row = $result->fetch_assoc()) {
    $uid = $row['user_id'];

    if ($row['action'] === 'Login') {
        // Start a new session
        $userSessions[$uid][] = [
            'user_id' => $uid,
            'full_name' => $row['full_name'],
            'username' => $row['username'],
            'login_at' => $row['created_at'],
            'logout_at' => null,
            'ips' => [$row['ip_address']],
            'user_agent' => $row['user_agent']
        ];
    } elseif ($row['action'] === 'Logout' && !empty($userSessions[$uid])) {
        // Find the latest open session
        for ($i = count($userSessions[$uid])-1; $i >= 0; $i--) {
            if ($userSessions[$uid][$i]['logout_at'] === null) {
                $userSessions[$uid][$i]['logout_at'] = $row['created_at'];
                break;
            }
        }
    } else {
        // For other actions, just collect IPs for all open sessions of this user
        if (!empty($userSessions[$uid])) {
            foreach ($userSessions[$uid] as &$sess) {
                if (!in_array($row['ip_address'], $sess['ips'])) {
                    $sess['ips'][] = $row['ip_address'];
                }
            }
            unset($sess);
        }
    }
}

// Flatten all sessions
foreach ($userSessions as $userSessArr) {
    foreach ($userSessArr as $sess) {
        $sessions[] = $sess;
    }
}

// Sort by login time
usort($sessions, function($a,$b){
    return strtotime($a['login_at']) - strtotime($b['login_at']);
});

// Fetch failed login attempts
$failed_sql = "SELECT ul.*, u.full_name, u.username
               FROM user_logs ul
               LEFT JOIN users u ON ul.user_id = u.id
               WHERE ul.action = 'Failed Login'";

$failedWhere = [];
$failedParams = [];
$failedTypes = '';

if ($user_filter) {
    $failedWhere[] = "ul.user_id = ?";
    $failedParams[] = $user_filter;
    $failedTypes .= 'i';
}
if ($date_from) {
    $failedWhere[] = "DATE(ul.created_at) >= ?";
    $failedParams[] = $date_from;
    $failedTypes .= 's';
}
if ($date_to) {
    $failedWhere[] = "DATE(ul.created_at) <= ?";
    $failedParams[] = $date_to;
    $failedTypes .= 's';
}
if (!empty($failedWhere)) {
    $failed_sql .= ' AND ' . implode(' AND ', $failedWhere);
}

$failed_sql .= " ORDER BY ul.created_at DESC";

$failed_stmt = $conn->prepare($failed_sql);
if ($failed_stmt === false) {
    echo '<div class="alert alert-danger">Query error: ' . htmlspecialchars($conn->error) . '</div>';
    require_once 'includes/footer.php';
    exit;
}
if (!empty($failedParams)) {
    $failed_stmt->bind_param($failedTypes, ...$failedParams);
}
$failed_stmt->execute();
$faileds = $failed_stmt->get_result();
?>

<link rel="stylesheet" href="assets/css/logs.css">

<div class="container-fluid py-4">

    <div class="page-title">
        <h3><i class="fas fa-user-clock me-2"></i>User Activity Logs</h3>
        <small>Sessions & failed login attempts (admin only)</small>
    </div>

    <div class="d-flex justify-content-end mb-3 gap-2">
        <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
        <button id="exportAll" class="btn btn-success"><i class="fas fa-file-csv me-1"></i>Export Sessions</button>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-sm-12 col-md-4">
                    <label class="form-label">User</label>
                    <select name="user" class="form-select">
                        <option value="">All Users</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= $user_filter === (int)$u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name'] . ' (' . $u['username'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="col-sm-12 col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SESSIONS -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-door-open me-2"></i>Sessions</h5>
            <small class="text-muted">Aggregated per login session</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="sessionsTable" class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Username</th>
                            <th>Login Time</th>
                            <th>Logout Time</th>
                            <th>Duration</th>
                            <th>IP(s)</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i=1; foreach ($sessions as $row): 
                            // Convert login/logout times from UTC to IST
                            $login_dt = new DateTime($row['login_at'], new DateTimeZone('UTC'));
                            $login_dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
                            $login_time = $login_dt->format('d M Y, h:i A');

                            if ($row['logout_at']) {
                                $logout_dt = new DateTime($row['logout_at'], new DateTimeZone('UTC'));
                                $logout_dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
                                $logout_time = $logout_dt->format('d M Y, h:i A');

                                $secs = $logout_dt->getTimestamp() - $login_dt->getTimestamp();
                                $h = floor($secs / 3600);
                                $m = floor(($secs % 3600) / 60);
                                $s = $secs % 60;
                                $duration = sprintf('%02dh %02dm %02ds', $h, $m, $s);
                            } else {
                                $logout_time = '-';
                                $duration = '<span class="text-muted">Open</span>';
                            }

                            $ua_short = htmlspecialchars(substr($row['user_agent'] ?? '', 0, 80));
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= $login_time ?></td>
                            <td><?= $logout_time ?></td>
                            <td><?= $duration ?></td>
                            <td><?= htmlspecialchars(implode(', ', $row['ips'])) ?></td>
                            <td title="<?= htmlspecialchars($row['user_agent']) ?>"><?= $ua_short ?><?= strlen($row['user_agent'] ?? '') > 80 ? '…' : '' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- FAILED ATTEMPTS -->
    <div class="card mb-5">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Failed Login Attempts</h5>
            <small class="text-muted">Individual failed login attempts</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="failedTable" class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>User (if known)</th>
                            <th>Username</th>
                            <th>Timestamp</th>
                            <th>IP</th>
                            <th>User Agent</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $j=1; while($f=$faileds->fetch_assoc()): 
                            $f_dt = new DateTime($f['created_at'], new DateTimeZone('UTC'));
                            $f_dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
                        ?>
                        <tr>
                            <td><?= $j++ ?></td>
                            <td><?= htmlspecialchars($f['full_name'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($f['username'] ?: '-') ?></td>
                            <td><?= $f_dt->format('d M Y, h:i A') ?></td>
                            <td><?= htmlspecialchars($f['ip_address'] ?: '-') ?></td>
                            <td title="<?= htmlspecialchars($f['user_agent']) ?>"><?= htmlspecialchars(substr($f['user_agent'] ?? '',0,80)) ?><?= strlen($f['user_agent'] ?? '')>80?'…':'' ?></td>
                            <td><?= htmlspecialchars($f['remarks'] ?? '-') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- DataTables -->
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#sessionsTable').DataTable({
        pageLength: 25,
        order: [[3, 'desc']],
        columnDefs: [{ orderable: false, targets: [6,7] }]
    });
    $('#failedTable').DataTable({
        pageLength: 10,
        order: [[3,'desc']],
        columnDefs: [{ orderable: false, targets: [5] }]
    });

    // Export sessions CSV
    $('#exportAll').on('click', function(e){
        e.preventDefault();
        let table = document.getElementById('sessionsTable');
        let csv = [];
        for (let i=0; i<table.rows.length; i++) {
            let cols = table.rows[i].querySelectorAll('td,th');
            let row = [];
            for (let j=0;j<cols.length;j++){
                row.push('"' + cols[j].innerText.replace(/"/g,'""') + '"');
            }
            csv.push(row.join(','));
        }
        let blob = new Blob([csv.join('\n')], {type:'text/csv;charset=utf-8;'});
        let url = URL.createObjectURL(blob);
        let a = document.createElement('a');
        a.href = url;
        a.download = 'user_sessions_<?= date("Y-m-d") ?>.csv';
        document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
    });
});
</script>

<?php
$stmt->close();
$failed_stmt->close();
require_once 'includes/footer.php';
?>
