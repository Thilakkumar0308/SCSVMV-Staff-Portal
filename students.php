<?php
$page_title = 'Student Management';
require_once 'includes/header.php';

// Check permissions
if (!has_role('Admin') && !has_role('DeptAdmin') && !has_role('HOD')) {
    redirect('dashboard.php');
}

$message = '';
$message_type = '';

// Upload folder
$upload_dir = __DIR__ . '/uploads/profile_pictures/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

    // ---------------- ADD STUDENT ----------------
    if ($_POST['action'] === 'add') {
        $student_id = sanitize_input($_POST['student_id']);
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $email = sanitize_input($_POST['email']);
        $phone = sanitize_input($_POST['phone']);
        $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
        $gender = $_POST['gender'] ?? null;
        $address = sanitize_input($_POST['address'] ?? '');
        $class_id = (int)($_POST['class_id'] ?? 0);
        $parent_name = sanitize_input($_POST['parent_name'] ?? '');
        $parent_phone = sanitize_input($_POST['parent_phone'] ?? '');
        $admission_date = !empty($_POST['admission_date']) ? $_POST['admission_date'] : null;
        $status = 'Active';

        // Profile picture
        $profile_picture = null;
        if (!empty($_FILES['profile_picture']['name']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $filename = time() . "_" . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['profile_picture']['name']));
            $target_file = $upload_dir . $filename;
            $web_path = 'uploads/profile_pictures/' . $filename;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                $profile_picture = $web_path;
            }
        }

        $stmt = $conn->prepare("INSERT INTO students 
            (student_id, first_name, last_name, email, phone, date_of_birth, gender, address, class_id, parent_name, parent_phone, admission_date, status, profile_picture) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt) die("Prepare failed: " . $conn->error);

        $stmt->bind_param(
            "ssssssssisssss", 
            $student_id, $first_name, $last_name, $email, $phone, 
            $date_of_birth, $gender, $address, $class_id, 
            $parent_name, $parent_phone, $admission_date, $status, $profile_picture
        );

        if ($stmt->execute()) {
            $message = "Student added successfully";
            $message_type = "success";
        } else {
            $message = "DB Error: " . $stmt->error;
            $message_type = "danger";
        }
    }

    // ---------------- EDIT STUDENT ----------------
    elseif ($_POST['action'] === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $student_id = sanitize_input($_POST['student_id'] ?? '');
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
        $gender = $_POST['gender'] ?? null;
        $address = sanitize_input($_POST['address'] ?? '');
        $class_id = (int)($_POST['class_id'] ?? 0);
        $parent_name = sanitize_input($_POST['parent_name'] ?? '');
        $parent_phone = sanitize_input($_POST['parent_phone'] ?? '');
        $admission_date = !empty($_POST['admission_date']) ? $_POST['admission_date'] : null;
        $status = $_POST['status'] ?? 'Active';

        // Get current profile picture
        $res = $conn->prepare("SELECT profile_picture FROM students WHERE id=?");
        $res->bind_param("i", $id);
        $res->execute();
        $r = $res->get_result()->fetch_assoc();
        $current_picture = $r['profile_picture'] ?? null;

        // Handle new picture
        $profile_picture = $current_picture;
        if (!empty($_FILES['profile_picture']['name']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $filename = time() . "_" . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['profile_picture']['name']));
            $target_file = $upload_dir . $filename;
            $web_path = 'uploads/profile_pictures/' . $filename;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                if ($current_picture && file_exists(__DIR__ . '/' . $current_picture)) unlink(__DIR__ . '/' . $current_picture);
                $profile_picture = $web_path;
            }
        }

        $stmt = $conn->prepare("UPDATE students SET 
            student_id=?, first_name=?, last_name=?, email=?, phone=?, date_of_birth=?, gender=?, address=?, class_id=?, parent_name=?, parent_phone=?, admission_date=?, status=?, profile_picture=? 
            WHERE id=?");

        if (!$stmt) die("Prepare failed: " . $conn->error);

        $stmt->bind_param(
            "ssssssssisssssi",
            $student_id, $first_name, $last_name, $email, $phone, $date_of_birth, $gender, $address, $class_id,
            $parent_name, $parent_phone, $admission_date, $status, $profile_picture, $id
        );

        if ($stmt->execute()) {
            $message = "Student updated successfully";
            $message_type = "success";
        } else {
            $message = "DB Error: " . $stmt->error;
            $message_type = "danger";
        }
    }

    // ---------------- DELETE STUDENT ----------------
    elseif ($_POST['action'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        $res = $conn->prepare("SELECT profile_picture FROM students WHERE id=?");
        $res->bind_param("i", $id);
        $res->execute();
        $row = $res->get_result()->fetch_assoc();
        $profile_picture = $row['profile_picture'] ?? null;

        $stmt = $conn->prepare("DELETE FROM students WHERE id=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($profile_picture && file_exists(__DIR__ . '/' . $profile_picture)) unlink(__DIR__ . '/' . $profile_picture);
            $message = "Student deleted successfully";
            $message_type = "success";
        } else {
            $message = "DB Error: " . $stmt->error;
            $message_type = "danger";
        }
    }
}

// Fetch students (ordered by student_id / roll number)
$students = [];
$result = $conn->query("
    SELECT s.*, c.class_name, c.section 
    FROM students s 
    LEFT JOIN classes c ON s.class_id=c.id 
    ORDER BY CAST(s.student_id AS UNSIGNED) ASC
");
while ($row = $result->fetch_assoc()) $students[] = $row;

// Fetch classes
$classes = [];
$result = $conn->query("SELECT * FROM classes ORDER BY class_name, section");
while ($row = $result->fetch_assoc()) $classes[] = $row;
?>

<div class="container mt-4">
    <h2>Student Management</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

<!-- Filters -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <div class="row g-3">
      
      <!-- Class Filter -->
      <div class="col-12 col-md-3">
        <label for="classFilter" class="form-label fw-semibold">Class</label>
        <select id="classFilter" class="form-select form-select-sm">
          <option value="">All Classes</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?php echo $c['id']; ?>">
              <?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Name Filter -->
      <div class="col-12 col-md-3">
        <label for="nameFilter" class="form-label fw-semibold">Name</label>
        <input type="text" id="nameFilter" class="form-control form-control-sm" placeholder="Search by name">
      </div>

      <!-- ID Filter -->
      <div class="col-12 col-md-3">
        <label for="idFilter" class="form-label fw-semibold">Register Number</label>
        <input type="text" id="idFilter" class="form-control form-control-sm" placeholder="Register Number">
      </div>

      <!-- Add Student Button -->
      <div class="col-12 col-md-3 d-flex align-items-end justify-content-md-end">
        <button type="button" class="btn btn-primary w-100 w-md-auto" 
                data-bs-toggle="modal" data-bs-target="#addModal">
          <i class="fas fa-plus"></i> Add Student
        </button>
      </div>

    </div>
  </div>
</div>

    <!-- PC Table -->
    <div class="d-none d-md-block card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" id="studentsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Photo</th>
                            <th>Roll No</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Class</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s):
                            $student_json_html = htmlspecialchars(json_encode($s, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr class="student-row" data-student-id="<?php echo $s['student_id']; ?>" data-class-id="<?php echo $s['class_id']; ?>">
                            <td><img src="<?php echo $s['profile_picture'] ?: 'uploads/profile_pictures/default.svg'; ?>" class="img-fluid rounded-circle" style="width:50px;height:50px;object-fit:cover;"></td>
                            <td><?php echo htmlspecialchars($s['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($s['first_name'].' '.$s['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($s['email']); ?></td>
                            <td><?php echo htmlspecialchars($s['phone']); ?></td>
                            <td><?php echo htmlspecialchars($s['class_name'].' '.$s['section']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo ($s['status']=='Active') ? 'success' : (($s['status']=='Inactive') ? 'warning' : 'secondary'); ?>">
                                    <?php echo htmlspecialchars($s['status'] ?? 'Active'); ?>
                                </span>
                            </td>
                            <td class="text-nowrap">
                                <a href="student_info.php?student_id=<?php echo urlencode($s['student_id']); ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a>
                                <button class="btn btn-sm btn-outline-primary btn-edit" data-student="<?php echo $student_json_html; ?>"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-danger btn-delete" data-id="<?php echo (int)$s['id']; ?>"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Mobile Cards -->
    <div class="d-md-none">
        <?php foreach($students as $s): 
            $student_json_html = htmlspecialchars(json_encode($s, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
        ?>
            <div class="card mb-3 shadow-sm student-card" data-student-id="<?php echo $s['student_id']; ?>" data-class-id="<?php echo $s['class_id']; ?>">
                <div class="row g-0 align-items-center">
                    <div class="col-3 text-center p-2">
                        <img src="<?php echo $s['profile_picture'] ?: 'uploads/profile_pictures/default.svg'; ?>" class="img-fluid rounded-circle" style="width:60px;height:60px;object-fit:cover;">
                    </div>
                    <div class="col-9 p-2">
                        <h6 class="mb-1"><?php echo htmlspecialchars($s['first_name'].' '.$s['last_name']); ?></h6>
                        <p class="mb-0 small">Roll No: <?php echo htmlspecialchars($s['student_id']); ?></p>
                        <p class="mb-0 small">Email: <?php echo htmlspecialchars($s['email']); ?></p>
                        <p class="mb-0 small">Phone: <?php echo htmlspecialchars($s['phone']); ?></p>
                        <p class="mb-0 small">Class: <?php echo htmlspecialchars($s['class_name'].' '.$s['section']); ?></p>
                        <span class="badge bg-<?php echo ($s['status']=='Active') ? 'success' : (($s['status']=='Inactive') ? 'warning' : 'secondary'); ?>">
                            <?php echo htmlspecialchars($s['status'] ?? 'Active'); ?>
                        </span>
                        <div class="mt-1 text-end">
                            <a href="student_info.php?student_id=<?php echo urlencode($s['student_id']); ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a>
                            <button class="btn btn-sm btn-outline-primary btn-edit" data-student='<?php echo $student_json_html; ?>'><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger btn-delete" data-id="<?php echo (int)$s['id']; ?>"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'modals/student_modals.php'; ?>
<script src="assets/js/students.js"></script>

<script>
// Combined Filters
const classFilter = document.getElementById('classFilter');
const nameFilter = document.getElementById('nameFilter');
const idFilter = document.getElementById('idFilter');

function applyFilters() {
    const selectedClass = classFilter.value.toLowerCase();
    const nameValue = nameFilter.value.toLowerCase();
    const idValue = idFilter.value.toLowerCase();

    // PC Table
    document.querySelectorAll('.student-row').forEach(row => {
        const rowClass = row.getAttribute('data-class-id').toLowerCase();
        const studentName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
        const studentId = row.querySelector('td:nth-child(2)').textContent.toLowerCase();

        const show = 
            (selectedClass === "" || rowClass === selectedClass) &&
            (nameValue === "" || studentName.includes(nameValue)) &&
            (idValue === "" || studentId.includes(idValue));

        row.style.display = show ? '' : 'none';
    });

    // Mobile Cards
    document.querySelectorAll('.student-card').forEach(card => {
        const cardClass = card.getAttribute('data-class-id').toLowerCase();
        const studentName = card.querySelector('h6').textContent.toLowerCase();
        const studentId = card.querySelector('p.small').textContent.toLowerCase();

        const show = 
            (selectedClass === "" || cardClass === selectedClass) &&
            (nameValue === "" || studentName.includes(nameValue)) &&
            (idValue === "" || studentId.includes(idValue));

        card.style.display = show ? '' : 'none';
    });
}

[classFilter, nameFilter, idFilter].forEach(el => {
    el.addEventListener('input', applyFilters);
});
</script>

<?php require_once 'includes/footer.php'; ?>
