<?php
// Utility functions for the Student Management System

if (!function_exists('has_role')) {
    function has_role($role) {
        // Use unified session key set at login
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
}

if (!function_exists('has_any_role')) {
    function has_any_role($roles) {
        if (!isset($_SESSION['role'])) {
            return false;
        }
        
        if (is_string($roles)) {
            return $_SESSION['role'] === $roles;
        }
        
        if (is_array($roles)) {
            return in_array($_SESSION['role'], $roles);
        }
        
        return false;
    }
}

if (!function_exists('require_role')) {
    function require_role($role) {
        if (!has_role($role)) {
            redirect('dashboard.php');
        }
    }
}

if (!function_exists('require_any_role')) {
    function require_any_role($roles) {
        if (!has_any_role($roles)) {
            redirect('dashboard.php');
        }
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit();
    }
}

if (!function_exists('get_setting')) {
    function get_setting($key, $default = '') {
        global $conn;
        
        $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['setting_value'];
        }
        
        return $default;
    }
}

if (!function_exists('set_setting')) {
    function set_setting($key, $value, $description = '') {
        global $conn;
        
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, description = ?");
        $stmt->bind_param("sssss", $key, $value, $description, $value, $description);
        
        return $stmt->execute();
    }
}

if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

function format_date($date, $format = 'M d, Y') {
    return $date ? date($format, strtotime($date)) : 'Not set';
}

function format_time($time, $format = 'g:i A') {
    return $time ? date($format, strtotime($time)) : 'Not set';
}

function get_user_department_id() {
    return $_SESSION['user_department_id'] ?? null;
}

function get_user_department_name() {
    global $conn;
    
    $department_id = get_user_department_id();
    if (!$department_id) {
        return 'No Department';
    }
    
    $stmt = $conn->prepare("SELECT department_name FROM departments WHERE id = ?");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['department_name'];
    }
    
    return 'Unknown Department';
}

function can_manage_department($department_id) {
    // Admin can manage all departments
    if (has_role('Admin')) {
        return true;
    }
    
    // DeptAdmin can manage all departments
    if (has_role('DeptAdmin')) {
        return true;
    }
    
    // HOD can only manage their own department
    if (has_role('HOD')) {
        return get_user_department_id() == $department_id;
    }
    
    return false;
}

function get_department_options() {
    global $conn;
    
    $departments = [];
    $result = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name");
    
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
    
    return $departments;
}

function get_class_options($department_id = null) {
    global $conn;
    
    $classes = [];
    
    if ($department_id) {
        $stmt = $conn->prepare("SELECT id, class_name, section FROM classes WHERE department_id = ? ORDER BY class_name, section");
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name, section");
    }
    
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
    
    return $classes;
}

function get_subject_options($class_id = null) {
    global $conn;
    
    $subjects = [];
    
    if ($class_id) {
        $stmt = $conn->prepare("SELECT id, subject_name FROM subjects WHERE class_id = ? ORDER BY subject_name");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT id, subject_name FROM subjects ORDER BY subject_name");
    }
    
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    
    return $subjects;
}

function get_teacher_options() {
    global $conn;
    
    $teachers = [];
    $result = $conn->query("SELECT id, full_name FROM users WHERE role = 'Teacher' ORDER BY full_name");
    
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
    
    return $teachers;
}

function get_hod_options() {
    global $conn;
    
    $hods = [];
    $result = $conn->query("SELECT id, full_name FROM users WHERE role = 'HOD' ORDER BY full_name");
    
    while ($row = $result->fetch_assoc()) {
        $hods[] = $row;
    }
    
    return $hods;
}

function log_activity($action, $details = '') {
    global $conn;
    
    $user_id = $_SESSION['user_id'] ?? null;
    $timestamp = date('Y-m-d H:i:s');
    
    // You can create an activity_log table if needed
    // For now, we'll just return true
    return true;
}

function send_notification($user_id, $title, $message, $type = 'info') {
    // You can implement notification system here
    // For now, we'll just return true
    return true;
}

function generate_student_id($class_id) {
    global $conn;
    
    // Get class info
    $stmt = $conn->prepare("SELECT class_name, section FROM classes WHERE id = ?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$row = $result->fetch_assoc()) {
        return false;
    }
    
    $class_name = str_replace(' ', '', $row['class_name']);
    $section = $row['section'];
    $year = date('Y');
    
    // Get next sequence number
    $stmt = $conn->prepare("SELECT COUNT(*) + 1 as next_num FROM students WHERE student_id LIKE ?");
    $pattern = $class_name . $section . $year . '%';
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $next_num = $result->fetch_assoc()['next_num'];
    
    return $class_name . $section . $year . str_pad($next_num, 3, '0', STR_PAD_LEFT);
}

function calculate_attendance_percentage($student_id, $class_id, $subject_id = null, $start_date = null, $end_date = null) {
    global $conn;
    
    $where_conditions = ["student_id = ?", "class_id = ?"];
    $params = [$student_id, $class_id];
    $types = "ii";
    
    if ($subject_id) {
        $where_conditions[] = "subject_id = ?";
        $params[] = $subject_id;
        $types .= "i";
    }
    
    if ($start_date) {
        $where_conditions[] = "attendance_date >= ?";
        $params[] = $start_date;
        $types .= "s";
    }
    
    if ($end_date) {
        $where_conditions[] = "attendance_date <= ?";
        $params[] = $end_date;
        $types .= "s";
    }
    
    $query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN status = 'Excused' THEN 1 ELSE 0 END) as excused
        FROM attendance 
        WHERE " . implode(" AND ", $where_conditions);
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $total = $row['total'];
        $present = $row['present'];
        
        if ($total > 0) {
            return round(($present / $total) * 100, 2);
        }
    }
    
    return 0;
}
?>
