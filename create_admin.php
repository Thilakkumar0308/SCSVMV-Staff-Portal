<?php
// Script to create admin user
require_once 'config/db.php';

echo "<h2>Creating Admin User</h2>";
echo "<style>
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
</style>";

// Check if admin user already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = 'admin'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<p class='info'>Admin user already exists. Checking password...</p>";
    
    // Check if password is correct
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (password_verify('admin123', $user['password'])) {
        echo "<p class='success'>✓ Admin user exists with correct password!</p>";
        echo "<p>You can now <a href='index.php'>login</a> with:</p>";
        echo "<ul>";
        echo "<li>Username: <strong>admin</strong></li>";
        echo "<li>Password: <strong>admin123</strong></li>";
        echo "</ul>";
    } else {
        echo "<p class='error'>✗ Admin user exists but password is incorrect. Updating password...</p>";
        
        // Update password
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
        $stmt->bind_param("s", $hashed_password);
        
        if ($stmt->execute()) {
            echo "<p class='success'>✓ Admin password updated successfully!</p>";
            echo "<p>You can now <a href='index.php'>login</a> with:</p>";
            echo "<ul>";
            echo "<li>Username: <strong>admin</strong></li>";
            echo "<li>Password: <strong>admin123</strong></li>";
            echo "</ul>";
        } else {
            echo "<p class='error'>✗ Failed to update admin password: " . $conn->error . "</p>";
        }
    }
} else {
    echo "<p class='info'>Admin user does not exist. Creating new admin user...</p>";
    
    // Create admin user
    $username = 'admin';
    $password = 'admin123';
    $email = 'admin@school.com';
    $role = 'Admin';
    $full_name = 'System Administrator';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, full_name) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $hashed_password, $email, $role, $full_name);
    
    if ($stmt->execute()) {
        echo "<p class='success'>✓ Admin user created successfully!</p>";
        echo "<p>You can now <a href='index.php'>login</a> with:</p>";
        echo "<ul>";
        echo "<li>Username: <strong>admin</strong></li>";
        echo "<li>Password: <strong>admin123</strong></li>";
        echo "</ul>";
    } else {
        echo "<p class='error'>✗ Failed to create admin user: " . $conn->error . "</p>";
    }
}

// Check if database tables exist
echo "<h3>Database Status Check</h3>";

$tables = ['users', 'students', 'classes', 'subjects', 'leave_requests', 'onduty_requests', 'disciplinary_actions', 'marks', 'settings'];
$missing_tables = [];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $missing_tables[] = $table;
    }
}

if (empty($missing_tables)) {
    echo "<p class='success'>✓ All required tables exist</p>";
} else {
    echo "<p class='error'>✗ Missing tables: " . implode(', ', $missing_tables) . "</p>";
    echo "<p>Please import the database schema from <code>database/student_mgmt.sql</code></p>";
}

// Show current users
echo "<h3>Current Users in Database</h3>";
$result = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at");
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>No users found in database</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Login</a></p>";
?>
