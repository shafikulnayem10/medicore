<?php
$required_role = 'Doctor';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

if ($patient_id === 0) {
    header("Location: appointments.php");
    exit();
}


$p_stmt = $conn->prepare("
    SELECT u.full_name, p.dob
    FROM patient p
    JOIN user u ON p.user_id = u.user_id
    WHERE p.patient_id = ?
");
$p_stmt->bind_param("i", $patient_id);
$p_stmt->execute();
$patient = $p_stmt->get_result()->fetch_assoc();

if (!$patient) {
    die("Patient not found.");
}


$rx_stmt = $conn->prepare("
    SELECT pr.medication, pr.instructions, pr.created_at, u.full_name AS doctor_name
    FROM prescription pr
    JOIN doctor d ON pr.doctor_id = d.doctor_id
    JOIN user u ON d.user_id = u.user_id
    WHERE pr.patient_id = ?
    ORDER BY pr.created_at DESC
");
$rx_stmt->bind_param("i", $patient_id);
$rx_stmt->execute();
$prescriptions = $rx_stmt->get_result();


$lab_stmt = $conn->prepare("
    SELECT ltr.test_type, ltres.result_data, ltres.result_date
    FROM lab_test_request ltr
    JOIN lab_test_result ltres ON ltr.lab_request_id = ltres.lab_request_id
    WHERE ltr.patient_id = ?
    ORDER BY ltres.result_date DESC
");
$lab_stmt->bind_param("i", $patient_id);
$lab_stmt->execute();
$lab_results = $lab_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient History - MediCore</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php require 'nav.php'; ?>

    <div class="page-content">
        <h1><?php echo htmlspecialchars($patient['full_name']); ?></h1>
        <p>Date of Birth: <?php echo htmlspecialchars($patient['dob']); ?></p>

        <h2>Prescription History</h2>
        <?php if ($prescriptions->num_rows === 0): ?>
            <p class="empty-msg">No previous prescriptions.</p>
        <?php else: ?>
        <table>
            <tr><th>Date</th><th>Doctor</th><th>Medication</th><th>Instructions</th></tr>
            <?php while ($row = $prescriptions->fetch_assoc()): ?>
            <tr>
                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                <td><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                <td><?php echo nl2br(htmlspecialchars($row['medication'])); ?></td>
                <td><?php echo nl2br(htmlspecialchars($row['instructions'])); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>

        <h2>Lab Test History</h2>
        <?php if ($lab_results->num_rows === 0): ?>
            <p class="empty-msg">No lab test results yet.</p>
        <?php else: ?>
        <table>
            <tr><th>Test Type</th><th>Result</th><th>Date</th></tr>
            <?php while ($row = $lab_results->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['test_type']); ?></td>
                <td><?php echo nl2br(htmlspecialchars($row['result_data'])); ?></td>
                <td><?php echo date('d M Y', strtotime($row['result_date'])); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>

        <p style="margin-top:20px;"><a class="btn" href="appointments.php">&larr; Back to Appointments</a></p>
    </div>
</body>
</html>