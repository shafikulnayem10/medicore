<?php
$required_role = 'Doctor';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$stmt = $conn->prepare("SELECT doctor_id FROM doctor WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor_id = $stmt->get_result()->fetch_assoc()['doctor_id'];


$appt_stmt = $conn->prepare("
    SELECT a.appointment_id, a.appointment_date, a.status,
           a.patient_id, u.full_name AS patient_name
    FROM appointment a
    JOIN patient p ON a.patient_id = p.patient_id
    JOIN user u ON p.user_id = u.user_id
    WHERE a.doctor_id = ?
    ORDER BY a.appointment_date DESC
");
$appt_stmt->bind_param("i", $doctor_id);
$appt_stmt->execute();
$appointments = $appt_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointments - MediCore</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php require 'nav.php'; ?>

    <div class="page-content">
        <h1>My Appointments</h1>

        <?php if ($appointments->num_rows === 0): ?>
            <p class="empty-msg">No appointments assigned yet.</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Patient</th>
                <th>Date &amp; Time</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $appointments->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                <td><?php echo date('d M Y, h:i A', strtotime($row['appointment_date'])); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td>
                    <a class="btn" href="patient-history.php?patient_id=<?php echo $row['patient_id']; ?>">History</a>
                    <a class="btn" href="create-prescription.php?appointment_id=<?php echo $row['appointment_id']; ?>&patient_id=<?php echo $row['patient_id']; ?>">Prescribe</a>
                    <a class="btn" href="request-lab-test.php?appointment_id=<?php echo $row['appointment_id']; ?>&patient_id=<?php echo $row['patient_id']; ?>">Request Lab Test</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>
    </div>
</body>
</html>