<?php
// Auto-generated configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'student_mgmt';

// Create connection
$conn = new mysqli($host, $username, $password, $database, $port = 3307);

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset('utf8');

// Function to sanitize input
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Function to redirect
function redirect($url) {
    header('Location: $url');
    exit();
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to check user role
function has_role($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Function to require login
function require_login() {
    if (!is_logged_in()) {
        redirect('index.php');
    }
}

// Function to require specific role
function require_role($role) {
    require_login();
    if (!has_role($role)) {
        redirect('dashboard.php');
    }
}

// Function to get user info
function get_user_info($user_id) {
    global $conn;
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to get setting value
function get_setting($key, $default = '') {
    global $conn;
    $stmt = $conn->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}

// Function to update setting
function update_setting($key, $value) {
    global $conn;
    $stmt = $conn->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?');
    $stmt->bind_param('sss', $key, $value, $value);
    return $stmt->execute();
}
?>