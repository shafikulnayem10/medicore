<?php
$required_role = 'Patient';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$stmt = $conn->prepare("SELECT patient_id FROM patient WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient_id = $stmt->get_result()->fetch_assoc()['patient_id'];

$rx_stmt = $conn->prepare("
    SELECT pr.prescription_id, pr.medication, pr.instructions, pr.created_at,
           u.full_name AS doctor_name, d.specialization
    FROM prescription pr
    JOIN doctor d ON pr.doctor_id = d.doctor_id
    JOIN user u ON d.user_id = u.user_id
    WHERE pr.patient_id = ?
    ORDER BY pr.created_at DESC
");
$rx_stmt->bind_param("i", $patient_id);
$rx_stmt->execute();
$prescriptions = $rx_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Prescriptions - MediCore</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php require 'nav.php'; ?>
    <main class="page-content">
        <div class="page-header">
            <div>
                <h1>My Prescriptions</h1>
                <p class="subtitle">All prescriptions issued by your care team</p>
            </div>
        </div>

        <?php if (count($prescriptions) === 0): ?>
            <p class="empty-msg">No prescriptions yet.</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Doctor Name</th>
                <th>Specialization</th>
                <th>Date</th>
                <th>Medication</th>
                <th>Instructions</th>
            </tr>
            <?php foreach ($prescriptions as $row): ?>
            <tr>
                <td>
                    <div class="avatar-cell">
                        <div class="avatar-round"><?php echo strtoupper(substr($row['doctor_name'],0,2)); ?></div>
                        Dr. <?php echo htmlspecialchars($row['doctor_name']); ?>
                    </div>
                </td>
                <td><?php echo htmlspecialchars($row['specialization'] ?: '—'); ?></td>
                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                <td><?php echo nl2br(htmlspecialchars($row['medication'] ?: '—')); ?></td>
                <td><?php echo nl2br(htmlspecialchars($row['instructions'] ?: '—')); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </main>
    </div>
    </div>
</body>
</html>