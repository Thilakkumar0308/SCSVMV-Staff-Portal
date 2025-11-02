<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Check if user is logged in and has permission
if (!is_logged_in()) {
    redirect('index.php');
}

// Only Admin and DeptAdmin can bulk enroll students
if (!has_any_role(['Admin', 'DeptAdmin'])) {
    redirect('dashboard.php');
}

$page_title = 'Bulk Student Enrollment';
include 'includes/header.php';

$message = '';
$message_type = '';
$uploaded_data = [];
$validation_errors = [];
$preview_mode = false;

// Get classes for dropdown - Admin and DeptAdmin can see all classes
$classes_query = "SELECT c.id, c.class_name, c.section, c.academic_year, d.department_name 
                  FROM classes c 
                  LEFT JOIN departments d ON c.department_id = d.id
                  ORDER BY c.class_name, c.section";
$result = $conn->query($classes_query);

$classes = [];
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

// Handle CSV upload and processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload_csv') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['csv_file'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($file_extension !== 'csv') {
                $message = 'Please upload a CSV file only.';
                $message_type = 'danger';
            } else {
                // Process CSV file
                $uploaded_data = process_csv_file($file['tmp_name']);
                $validation_errors = validate_student_data($uploaded_data, $classes);
                
                if (empty($validation_errors)) {
                    $preview_mode = true;
                    $message = 'CSV file processed successfully. Please review the data below before importing.';
                    $message_type = 'success';
                } else {
                    $message = 'Please fix the validation errors below before proceeding.';
                    $message_type = 'warning';
                }
            }
        } else {
            $message = 'Please select a CSV file to upload.';
            $message_type = 'danger';
        }
    }
    
    elseif ($action === 'import_students') {
        $student_data = json_decode($_POST['student_data'], true);
        $import_results = import_students_to_database($student_data);
        
        $success_count = $import_results['success'];
        $error_count = $import_results['errors'];
        $duplicate_count = $import_results['duplicates'];
        
        if ($error_count == 0) {
            $message = "Successfully imported $success_count students.";
            if ($duplicate_count > 0) {
                $message .= " $duplicate_count duplicate entries were skipped.";
            }
            $message_type = 'success';
        } else {
            $message = "Imported $success_count students with $error_count errors.";
            $message_type = 'warning';
        }
        
        // Reset preview mode
        $preview_mode = false;
        $uploaded_data = [];
        $validation_errors = [];
    }
}

// Function to process CSV file
function process_csv_file($file_path) {
    $data = [];
    $handle = fopen($file_path, 'r');
    
    if ($handle !== false) {
        $header = fgetcsv($handle); // Skip header row
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 10) { // Minimum required columns
                $data[] = [
                    'student_id' => trim($row[0]),
                    'first_name' => trim($row[1]),
                    'last_name' => trim($row[2]),
                    'email' => trim($row[3]),
                    'phone' => trim($row[4]),
                    'date_of_birth' => trim($row[5]),
                    'gender' => trim($row[6]),
                    'address' => trim($row[7]),
                    'class_name' => trim($row[8]),
                    'parent_name' => trim($row[9]),
                    'parent_phone' => trim($row[10] ?? ''),
                    'admission_date' => trim($row[11] ?? '')
                ];
            }
        }
        fclose($handle);
    }
    
    return $data;
}

// Function to validate student data
function validate_student_data($data, $classes) {
    global $conn;
    $errors = [];
    
    // Create class lookup
    $class_lookup = [];
    foreach ($classes as $class) {
        $class_lookup[$class['class_name'] . ' ' . $class['section']] = $class['id'];
    }
    
    foreach ($data as $index => $student) {
        $row_errors = [];
        $line_number = $index + 2; // +2 because we skip header and arrays start at 0
        
        // Validate required fields
        if (empty($student['student_id'])) {
            $row_errors[] = 'Student ID is required';
        }
        
        if (empty($student['first_name'])) {
            $row_errors[] = 'First name is required';
        }
        
        if (empty($student['last_name'])) {
            $row_errors[] = 'Last name is required';
        }
        
        if (empty($student['email'])) {
            $row_errors[] = 'Email is required';
        } elseif (!filter_var($student['email'], FILTER_VALIDATE_EMAIL)) {
            $row_errors[] = 'Invalid email format';
        }
        
        if (empty($student['class_name'])) {
            $row_errors[] = 'Class is required';
        }
        
        // Validate class exists
        if (!empty($student['class_name']) && !isset($class_lookup[$student['class_name']])) {
            $row_errors[] = 'Class "' . $student['class_name'] . '" does not exist';
        }
        
        // Validate gender
        if (!empty($student['gender']) && !in_array($student['gender'], ['Male', 'Female', 'Other'])) {
            $row_errors[] = 'Gender must be Male, Female, or Other';
        }
        
        // Validate dates
        if (!empty($student['date_of_birth']) && !validate_date($student['date_of_birth'])) {
            $row_errors[] = 'Invalid date of birth format (use YYYY-MM-DD)';
        }
        
        if (!empty($student['admission_date']) && !validate_date($student['admission_date'])) {
            $row_errors[] = 'Invalid admission date format (use YYYY-MM-DD)';
        }
        
        // Check for duplicate student_id
        if (!empty($student['student_id'])) {
            $stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
            $stmt->bind_param("s", $student['student_id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $row_errors[] = 'Student ID already exists';
            }
        }
        
        // Check for duplicate email
        if (!empty($student['email'])) {
            $stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
            $stmt->bind_param("s", $student['email']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $row_errors[] = 'Email already exists';
            }
        }
        
        if (!empty($row_errors)) {
            $errors[$line_number] = $row_errors;
        }
    }
    
    return $errors;
}

// Function to validate date format
function validate_date($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Function to import students to database
function import_students_to_database($student_data) {
    global $conn;
    
    $success_count = 0;
    $error_count = 0;
    $duplicate_count = 0;
    
    // Get classes for lookup
    $result = $conn->query("SELECT id, class_name, section FROM classes");
    $class_lookup = [];
    while ($row = $result->fetch_assoc()) {
        $class_lookup[$row['class_name'] . ' ' . $row['section']] = $row['id'];
    }
    
    foreach ($student_data as $student) {
        // Check for duplicates again before insertion
        $stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ? OR email = ?");
        $stmt->bind_param("ss", $student['student_id'], $student['email']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $duplicate_count++;
            continue;
        }
        
        // Get class_id
        $class_id = $class_lookup[$student['class_name']] ?? null;
        
        // Prepare data
        $student_id = $student['student_id'];
        $first_name = $student['first_name'];
        $last_name = $student['last_name'];
        $email = $student['email'];
        $phone = $student['phone'] ?: null;
        $date_of_birth = !empty($student['date_of_birth']) ? $student['date_of_birth'] : null;
        $gender = $student['gender'] ?: null;
        $address = $student['address'] ?: null;
        $parent_name = $student['parent_name'] ?: null;
        $parent_phone = $student['parent_phone'] ?: null;
        $admission_date = !empty($student['admission_date']) ? $student['admission_date'] : date('Y-m-d');
        $status = 'Active';
        
        // Insert student
        $stmt = $conn->prepare("INSERT INTO students 
            (student_id, first_name, last_name, email, phone, date_of_birth, gender, address, class_id, parent_name, parent_phone, admission_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("ssssssssissss", 
                $student_id, $first_name, $last_name, $email, $phone, 
                $date_of_birth, $gender, $address, $class_id, 
                $parent_name, $parent_phone, $admission_date, $status);
            
            if ($stmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
        } else {
            $error_count++;
        }
    }
    
    return [
        'success' => $success_count,
        'errors' => $error_count,
        'duplicates' => $duplicate_count
    ];
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Bulk Student Enrollment</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-secondary" onclick="history.back()">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (!$preview_mode): ?>
        <!-- Upload Section -->
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Upload CSV File</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_csv">
                            
                            <div class="mb-3">
                                <label for="csv_file" class="form-label">Select CSV File <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                                <div class="form-text">Please upload a CSV file with student data.</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-1"></i> Upload and Validate
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">CSV Format Guide</h5>
                    </div>
                    <div class="card-body">
                        <h6>Required Columns (in order):</h6>
                        <ol class="small">
                            <li><strong>Student ID</strong> - Unique identifier</li>
                            <li><strong>First Name</strong> - Student's first name</li>
                            <li><strong>Last Name</strong> - Student's last name</li>
                            <li><strong>Email</strong> - Valid email address</li>
                            <li><strong>Phone</strong> - Contact number</li>
                            <li><strong>Date of Birth</strong> - YYYY-MM-DD format</li>
                            <li><strong>Gender</strong> - Male/Female/Other</li>
                            <li><strong>Address</strong> - Full address</li>
                            <li><strong>Class</strong> - Class name (e.g., "MCA I")</li>
                            <li><strong>Parent Name</strong> - Guardian's name</li>
                            <li><strong>Parent Phone</strong> - Guardian's contact</li>
                            <li><strong>Admission Date</strong> - YYYY-MM-DD format</li>
                        </ol>
                        
                        <div class="mt-3">
                            <a href="download_sample_csv.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-download me-1"></i> Download Sample CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Classes -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Available Classes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Class Name</th>
                                        <th>Section</th>
                                        <th>Academic Year</th>
                                        <th>Department</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                        <td><?php echo htmlspecialchars($class['section']); ?></td>
                                        <td><?php echo htmlspecialchars($class['academic_year']); ?></td>
                                        <td><?php echo htmlspecialchars($class['department_name']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Preview Section -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Preview Student Data</h5>
                        <div>
                            <span class="badge bg-success"><?php echo count($uploaded_data); ?> students ready for import</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($validation_errors)): ?>
                            <div class="alert alert-danger">
                                <h6>Validation Errors Found:</h6>
                                <?php foreach ($validation_errors as $line => $errors): ?>
                                    <div><strong>Line <?php echo $line; ?>:</strong></div>
                                    <ul class="mb-2">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center">
                                <a href="bulk_student_enrollment.php" class="btn btn-warning">
                                    <i class="fas fa-arrow-left me-1"></i> Go Back and Fix Errors
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Class</th>
                                            <th>Parent</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($uploaded_data as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                            <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['parent_name']); ?></td>
                                            <td><span class="badge bg-success">Ready</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="text-center mt-4">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="import_students">
                                    <input type="hidden" name="student_data" value="<?php echo htmlspecialchars(json_encode($uploaded_data)); ?>">
                                    <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('Are you sure you want to import <?php echo count($uploaded_data); ?> students? This action cannot be undone.')">
                                        <i class="fas fa-check me-1"></i> Import <?php echo count($uploaded_data); ?> Students
                                    </button>
                                </form>
                                
                                <a href="bulk_student_enrollment.php" class="btn btn-secondary btn-lg ms-2">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Auto-hide alerts after 5 seconds
$(document).ready(function() {
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
});
</script>

<?php include 'includes/footer.php'; ?>
