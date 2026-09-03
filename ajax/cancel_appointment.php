<?php
$required_role = 'Patient';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

header('Content-Type: application/json');

$appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;

if ($appointment_id === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid input.']);
    exit();
}

$pat_stmt = $conn->prepare("SELECT patient_id FROM patient WHERE user_id = ?");
$pat_stmt->bind_param("i", $_SESSION['user_id']);
$pat_stmt->execute();
$patient_id = $pat_stmt->get_result()->fetch_assoc()['patient_id'];

// only allow cancelling own, still-Pending appointments
$check_stmt = $conn->prepare("SELECT appointment_id FROM appointment WHERE appointment_id = ? AND patient_id = ? AND status = 'Pending'");
$check_stmt->bind_param("ii", $appointment_id, $patient_id);
$check_stmt->execute();

if ($check_stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'This appointment can no longer be cancelled.']);
    exit();
}

$update_stmt = $conn->prepare("UPDATE appointment SET status = 'Cancelled' WHERE appointment_id = ?");
$update_stmt->bind_param("i", $appointment_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database update failed.']);
}