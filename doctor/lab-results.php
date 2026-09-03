<?php
$required_role = 'Doctor';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$stmt = $conn->prepare("SELECT doctor_id FROM doctor WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor_id = $stmt->get_result()->fetch_assoc()['doctor_id'];


$results_stmt = $conn->prepare("
    SELECT ltres.result_data, ltres.result_file, ltres.result_date,
           ltr.test_type, u.full_name AS patient_name, ltr.patient_id
    FROM lab_test_result ltres
    JOIN lab_test_request ltr ON ltres.lab_request_id = ltr.lab_request_id
    JOIN appointment a ON ltr.appointment_id = a.appointment_id
    JOIN patient p ON ltr.patient_id = p.patient_id
    JOIN user u ON p.user_id = u.user_id
    WHERE a.doctor_id = ?
    ORDER BY ltres.result_date DESC
");
$results_stmt->bind_param("i", $doctor_id);
$results_stmt->execute();
$results = $results_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Test Results - MediCore</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php require 'nav.php'; ?>

    <div class="page-content">
        <h1>Lab Test Results</h1>

        <?php if ($results->num_rows === 0): ?>
            <p class="empty-msg">No lab results available yet.</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Patient</th>
                <th>Test Type</th>
                <th>Result</th>
                <th>Date</th>
                <th></th>
            </tr>
            <?php while ($row = $results->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                <td><?php echo htmlspecialchars($row['test_type']); ?></td>
                <td><?php echo nl2br(htmlspecialchars($row['result_data'])); ?></td>
                <td><?php echo date('d M Y', strtotime($row['result_date'])); ?></td>
                <td><a class="btn" href="patient-history.php?patient_id=<?php echo $row['patient_id']; ?>">Full History</a></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>
    </div>
</body>
</html>