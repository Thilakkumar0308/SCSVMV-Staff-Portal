<?php
// Script to update database with HOD functionality
require_once 'config/db.php';

echo "<h2>Updating Database for HOD Functionality</h2>";
echo "<style>
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
</style>";

// Check if departments table exists
$result = $conn->query("SHOW TABLES LIKE 'departments'");
if ($result->num_rows == 0) {
    echo "<p class='info'>Creating departments table...</p>";
    
    $sql = "CREATE TABLE departments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        department_name VARCHAR(100) NOT NULL,
        description TEXT,
        hod_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (hod_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if ($conn->query($sql)) {
        echo "<p class='success'>✓ Departments table created successfully</p>";
    } else {
        echo "<p class='error'>✗ Error creating departments table: " . $conn->error . "</p>";
    }
} else {
    echo "<p class='success'>✓ Departments table already exists</p>";
}

// Update users table to include HOD role and department_id
echo "<p class='info'>Updating users table...</p>";

// Add department_id column if it doesn't exist
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'department_id'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN department_id INT AFTER full_name";
    if ($conn->query($sql)) {
        echo "<p class='success'>✓ Added department_id column to users table</p>";
    } else {
        echo "<p class='error'>✗ Error adding department_id column: " . $conn->error . "</p>";
    }
} else {
    echo "<p class='success'>✓ department_id column already exists</p>";
}

// Update role enum to include HOD and DeptAdmin
$sql = "ALTER TABLE users MODIFY COLUMN role ENUM('Admin', 'DeptAdmin', 'HOD', 'Teacher', 'Student') NOT NULL";
if ($conn->query($sql)) {
    echo "<p class='success'>✓ Updated role enum to include HOD and DeptAdmin</p>";
} else {
    echo "<p class='error'>✗ Error updating role enum: " . $conn->error . "</p>";
}

// Update classes table to include department_id
echo "<p class='info'>Updating classes table...</p>";

$result = $conn->query("SHOW COLUMNS FROM classes LIKE 'department_id'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE classes ADD COLUMN department_id INT AFTER academic_year";
    if ($conn->query($sql)) {
        echo "<p class='success'>✓ Added department_id column to classes table</p>";
    } else {
        echo "<p class='error'>✗ Error adding department_id column to classes: " . $conn->error . "</p>";
    }
} else {
    echo "<p class='success'>✓ department_id column already exists in classes table</p>";
}

// Insert sample departments
echo "<p class='info'>Inserting sample departments...</p>";

$departments = [
    ['Computer Science', 'Computer Science and Information Technology Department'],
    ['Mathematics', 'Mathematics and Statistics Department'],
    ['Science', 'Physics, Chemistry, and Biology Department'],
    ['English', 'English Language and Literature Department'],
    ['Commerce', 'Commerce and Business Studies Department']
];

foreach ($departments as $dept) {
    $stmt = $conn->prepare("INSERT IGNORE INTO departments (department_name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $dept[0], $dept[1]);
    if ($stmt->execute()) {
        echo "<p class='success'>✓ Added department: " . $dept[0] . "</p>";
    } else {
        echo "<p class='error'>✗ Error adding department " . $dept[0] . ": " . $conn->error . "</p>";
    }
}

// Create Department Admin user
echo "<p class='info'>Creating Department Admin user...</p>";

$deptadmin_username = 'deptadmin';
$deptadmin_password = password_hash('deptadmin123', PASSWORD_DEFAULT);
$deptadmin_email = 'deptadmin@school.com';
$deptadmin_role = 'DeptAdmin';
$deptadmin_full_name = 'Department Administrator';

$stmt = $conn->prepare("INSERT IGNORE INTO users (username, password, email, role, full_name) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $deptadmin_username, $deptadmin_password, $deptadmin_email, $deptadmin_role, $deptadmin_full_name);

if ($stmt->execute()) {
    echo "<p class='success'>✓ Department Admin user created: $deptadmin_username</p>";
} else {
    echo "<p class='error'>✗ Error creating Department Admin user: " . $conn->error . "</p>";
}

// Create HOD users for each department
echo "<p class='info'>Creating HOD users for each department...</p>";

$hod_users = [
    ['hod_cse', 'hod123', 'hod.cse@school.com', 'HOD Computer Science', 1],
    ['hod_math', 'hod123', 'hod.math@school.com', 'HOD Mathematics', 2],
    ['hod_science', 'hod123', 'hod.science@school.com', 'HOD Science', 3],
    ['hod_english', 'hod123', 'hod.english@school.com', 'HOD English', 4],
    ['hod_commerce', 'hod123', 'hod.commerce@school.com', 'HOD Commerce', 5]
];

foreach ($hod_users as $hod) {
    $username = $hod[0];
    $password = password_hash($hod[1], PASSWORD_DEFAULT);
    $email = $hod[2];
    $role = 'HOD';
    $full_name = $hod[3];
    $department_id = $hod[4];

    $stmt = $conn->prepare("INSERT IGNORE INTO users (username, password, email, role, full_name, department_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $username, $password, $email, $role, $full_name, $department_id);

    if ($stmt->execute()) {
        echo "<p class='success'>✓ HOD user created: $username ($full_name)</p>";
    } else {
        echo "<p class='error'>✗ Error creating HOD user $username: " . $conn->error . "</p>";
    }
}

echo "<p>Login credentials:</p>";
echo "<ul>";
echo "<li><strong>Admin:</strong> Username: <strong>admin</strong> | Password: <strong>admin123</strong></li>";
echo "<li><strong>Department Admin:</strong> Username: <strong>deptadmin</strong> | Password: <strong>deptadmin123</strong></li>";
echo "<li><strong>HOD Users (all use password: <strong>hod123</strong>):</strong></li>";
echo "<li>• Username: <strong>hod_cse</strong> - Computer Science HOD</li>";
echo "<li>• Username: <strong>hod_math</strong> - Mathematics HOD</li>";
echo "<li>• Username: <strong>hod_science</strong> - Science HOD</li>";
echo "<li>• Username: <strong>hod_english</strong> - English HOD</li>";
echo "<li>• Username: <strong>hod_commerce</strong> - Commerce HOD</li>";
echo "</ul>";

// Create timetable table
echo "<p class='info'>Creating timetable table...</p>";

$result = $conn->query("SHOW TABLES LIKE 'timetable'");
if ($result->num_rows == 0) {
    $sql = "CREATE TABLE timetable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_id INT NOT NULL,
        subject_id INT NOT NULL,
        teacher_id INT,
        day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        room VARCHAR(50),
        academic_year VARCHAR(20) NOT NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "<p class='success'>✓ Timetable table created successfully</p>";
    } else {
        echo "<p class='error'>✗ Error creating timetable table: " . $conn->error . "</p>";
    }
} else {
    echo "<p class='success'>✓ Timetable table already exists</p>";
}

// Create attendance table
echo "<p class='info'>Creating attendance table...</p>";

$result = $conn->query("SHOW TABLES LIKE 'attendance'");
if ($result->num_rows == 0) {
    $sql = "CREATE TABLE attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        class_id INT NOT NULL,
        subject_id INT,
        attendance_date DATE NOT NULL,
        status ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL,
        remarks TEXT,
        marked_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
        FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_attendance (student_id, class_id, attendance_date, subject_id)
    )";
    
    if ($conn->query($sql)) {
        echo "<p class='success'>✓ Attendance table created successfully</p>";
    } else {
        echo "<p class='error'>✗ Error creating attendance table: " . $conn->error . "</p>";
    }
} else {
    echo "<p class='success'>✓ Attendance table already exists</p>";
}

// Update existing classes with department assignments
echo "<p class='info'>Updating existing classes with department assignments...</p>";

$class_updates = [
    [1, 1], // Class 1A -> Computer Science
    [2, 1], // Class 1B -> Computer Science
    [3, 2], // Class 2A -> Mathematics
    [4, 2], // Class 2B -> Mathematics
    [5, 3], // Class 3A -> Science
    [6, 3]  // Class 3B -> Science
];

foreach ($class_updates as $update) {
    $stmt = $conn->prepare("UPDATE classes SET department_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $update[1], $update[0]);
    if ($stmt->execute()) {
        echo "<p class='success'>✓ Updated class ID " . $update[0] . " with department ID " . $update[1] . "</p>";
    } else {
        echo "<p class='error'>✗ Error updating class ID " . $update[0] . ": " . $conn->error . "</p>";
    }
}

echo "<hr>";
echo "<h3 class='success'>Enhanced Student Management System Update Complete!</h3>";
echo "<p>You can now:</p>";
echo "<ul>";
echo "<li><strong>Admin Features:</strong></li>";
echo "<li>• Login as Admin with username: <strong>admin</strong> and password: <strong>admin123</strong></li>";
echo "<li>• Access Admin Dashboard for department-wise management</li>";
echo "<li>• Manage classes, attendance, and timetables</li>";
echo "<li>• Use global search with alert notifications</li>";
echo "<li><strong>Department Admin Features:</strong></li>";
echo "<li>• Login as DeptAdmin with username: <strong>deptadmin</strong> and password: <strong>deptadmin123</strong></li>";
echo "<li>• Manage all departments (add, edit, delete)</li>";
echo "<li>• Assign HODs to departments</li>";
echo "<li>• Manage classes, attendance, and timetables</li>";
echo "<li><strong>HOD Features:</strong></li>";
echo "<li>• Login as HOD with username: <strong>hod_cse</strong> (or hod_math, hod_science, etc.) and password: <strong>hod123</strong></li>";
echo "<li>• Access HOD Dashboard for department-specific management</li>";
echo "<li>• Manage classes, attendance, and timetables for your department only</li>";
echo "<li>• View department-specific students, leaves, and on-duty requests</li>";
echo "<li><strong>New Features Added:</strong></li>";
echo "<li>• Class Management (Add/Edit/Delete classes)</li>";
echo "<li>• Attendance Management (Mark attendance by class, subject, date)</li>";
echo "<li>• Timetable Management (Create and manage class schedules)</li>";
echo "<li>• Global Search with disciplinary action alerts</li>";
echo "<li>• Department-wise access control</li>";
echo "</ul>";

echo "<p><a href='index.php'>← Back to Login</a></p>";
?>
