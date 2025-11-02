<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/daemail.php';
session_start();

// Always return JSON
header('Content-Type: application/json');

// Access control
if (!has_any_role(['HOD', 'Admin', 'Teacher'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $_POST['action'];
$response = ['success' => false, 'message' => 'Unknown error'];

/* ======================================================
   ADD DISCIPLINARY ACTION
====================================================== */
if ($action === 'add') {
    $student_id  = intval($_POST['student_id'] ?? 0);
    $action_type = sanitize_input($_POST['action_type'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $action_date = $_POST['action_date'] ?? date('Y-m-d');
    $status      = $_POST['status'] ?? 'Active';
    $imposed_by  = intval($_SESSION['user_id'] ?? 0);

    if (!$student_id || !$action_type || !$description) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO disciplinary_actions 
        (student_id, action_type, description, action_date, imposed_by, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("isssis", $student_id, $action_type, $description, $action_date, $imposed_by, $status);

    if ($stmt->execute()) {
        // Fetch student info for email
        $stmt2 = $conn->prepare("SELECT email, first_name, last_name FROM students WHERE id = ?");
        $stmt2->bind_param("i", $student_id);
        $stmt2->execute();
        $stmt2->bind_result($email, $fname, $lname);
        $stmt2->fetch();
        $stmt2->close();

        // Send email notification
        if (!empty($email)) {
            sendDAEmail($email, trim("$fname $lname"), $action_type, $description, $action_date, 'add');
        }

        $response = ['success' => true, 'message' => 'Disciplinary action recorded successfully'];
    } else {
        $response = ['success' => false, 'message' => 'DB insert failed: ' . $conn->error];
    }
}

/* ======================================================
   EDIT DISCIPLINARY ACTION
====================================================== */
elseif ($action === 'edit') {
    if (!has_any_role(['HOD', 'Admin'])) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    $id          = intval($_POST['id'] ?? 0);
    $action_type = sanitize_input($_POST['action_type'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $action_date = $_POST['action_date'] ?? '';
    $status      = $_POST['status'] ?? 'Active';

    $stmt = $conn->prepare("
        UPDATE disciplinary_actions
        SET action_type = ?, description = ?, action_date = ?, status = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssssi", $action_type, $description, $action_date, $status, $id);

    if ($stmt->execute()) {
        $response = ['success' => true, 'message' => 'Record updated successfully'];
    } else {
        $response = ['success' => false, 'message' => 'Update failed: ' . $conn->error];
    }
}

/* ======================================================
   DELETE DISCIPLINARY ACTION
====================================================== */
elseif ($action === 'delete') {
    if (!has_any_role(['HOD', 'Admin'])) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    $id = intval($_POST['id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM disciplinary_actions WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $response = ['success' => true, 'message' => 'Record deleted successfully'];
    } else {
        $response = ['success' => false, 'message' => 'Delete failed: ' . $conn->error];
    }
}

/* ======================================================
   INVALID ACTION
====================================================== */
else {
    $response = ['success' => false, 'message' => 'Invalid action'];
}

echo json_encode($response);
exit;
?>
