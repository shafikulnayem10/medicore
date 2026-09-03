<?php
$required_role = 'Doctor';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

header('Content-Type: application/json');

$appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
$patient_id     = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
$test_type      = trim($_POST['test_type'] ?? '');
$notes          = trim($_POST['notes'] ?? '');

if ($appointment_id === 0 || $patient_id === 0 || $test_type === '') {
    echo json_encode(['success' => false, 'error' => 'Test type is required.']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO lab_test_request (appointment_id, patient_id, test_type, notes) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $appointment_id, $patient_id, $test_type, $notes);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Lab test request submitted successfully.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Something went wrong. Please try again.']);
}
