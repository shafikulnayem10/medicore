<?php
$required_role = 'Doctor';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

header('Content-Type: application/json');

$appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
$status         = $_POST['status'] ?? '';
$allowed        = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];

if ($appointment_id === 0 || !in_array($status, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input.']);
    exit();
}


$doc_stmt = $conn->prepare("SELECT doctor_id FROM doctor WHERE user_id = ?");
$doc_stmt->bind_param("i", $_SESSION['user_id']);
$doc_stmt->execute();
$doctor_id = $doc_stmt->get_result()->fetch_assoc()['doctor_id'];

$check_stmt = $conn->prepare("SELECT appointment_id FROM appointment WHERE appointment_id = ? AND doctor_id = ?");
$check_stmt->bind_param("ii", $appointment_id, $doctor_id);
$check_stmt->execute();

if ($check_stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Not authorized for this appointment.']);
    exit();
}

$update_stmt = $conn->prepare("UPDATE appointment SET status = ? WHERE appointment_id = ?");
$update_stmt->bind_param("si", $status, $appointment_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database update failed.']);
}