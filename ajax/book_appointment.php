<?php
$required_role = 'Patient';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

header('Content-Type: application/json');

$doctor_id  = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
$appt_date  = $_POST['appt_date'] ?? '';
$appt_time  = $_POST['appt_time'] ?? '';
$reason     = trim($_POST['reason'] ?? '');

if ($doctor_id === 0 || $appt_date === '' || $appt_time === '' || $reason === '') {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit();
}


$doc_check = $conn->prepare("SELECT doctor_id FROM doctor WHERE doctor_id = ?");
$doc_check->bind_param("i", $doctor_id);
$doc_check->execute();
if ($doc_check->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Selected doctor not found.']);
    exit();
}


$datetime = DateTime::createFromFormat('Y-m-d h:i A', $appt_date . ' ' . $appt_time);
if (!$datetime) {
    echo json_encode(['success' => false, 'error' => 'Invalid date or time.']);
    exit();
}
if ($datetime < new DateTime('today')) {
    echo json_encode(['success' => false, 'error' => 'Appointment date cannot be in the past.']);
    exit();
}
$appointment_datetime = $datetime->format('Y-m-d H:i:s');


$pat_stmt = $conn->prepare("SELECT patient_id FROM patient WHERE user_id = ?");
$pat_stmt->bind_param("i", $_SESSION['user_id']);
$pat_stmt->execute();
$patient_row = $pat_stmt->get_result()->fetch_assoc();

if (!$patient_row) {
    echo json_encode(['success' => false, 'error' => 'Patient profile not found.']);
    exit();
}
$patient_id = $patient_row['patient_id'];

$insert_stmt = $conn->prepare("
    INSERT INTO appointment (patient_id, doctor_id, reason, appointment_date, status)
    VALUES (?, ?, ?, ?, 'Pending')
");
$insert_stmt->bind_param("iiss", $patient_id, $doctor_id, $reason, $appointment_datetime);

if ($insert_stmt->execute()) {
    echo json_encode(['success' => true, 'appointment_id' => $insert_stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database insert failed.']);
}