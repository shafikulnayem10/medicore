<?php
$required_role = 'Doctor';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

header('Content-Type: application/json');

$stmt = $conn->prepare("SELECT doctor_id FROM doctor WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor_id = $stmt->get_result()->fetch_assoc()['doctor_id'];

$appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
$patient_id     = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
$medication     = trim($_POST['medication'] ?? '');
$instructions   = trim($_POST['instructions'] ?? '');

if ($appointment_id === 0 || $patient_id === 0 || $medication === '') {
    echo json_encode(['success' => false, 'error' => 'Medication field is required.']);
    exit();
}

$ins_stmt = $conn->prepare("INSERT INTO prescription (appointment_id, patient_id, doctor_id, medication, instructions) VALUES (?, ?, ?, ?, ?)");
$ins_stmt->bind_param("iiiss", $appointment_id, $patient_id, $doctor_id, $medication, $instructions);

if ($ins_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Prescription created successfully.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Something went wrong. Please try again.']);
}