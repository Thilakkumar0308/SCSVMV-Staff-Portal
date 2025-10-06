<?php
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_GET['class_id']) || empty($_GET['class_id'])) {
    echo json_encode([]);
    exit;
}

$class_id = (int)$_GET['class_id'];

$stmt = $conn->prepare("SELECT id, subject_name FROM subjects WHERE class_id = ? ORDER BY subject_name");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

echo json_encode($subjects);
?>
