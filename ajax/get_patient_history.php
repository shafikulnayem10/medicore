<?php
$required_role = 'Doctor';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

$doc_stmt = $conn->prepare("SELECT doctor_id FROM doctor WHERE user_id = ?");
$doc_stmt->bind_param("i", $_SESSION['user_id']);
$doc_stmt->execute();
$doctor_id = $doc_stmt->get_result()->fetch_assoc()['doctor_id'];


$owns_stmt = $conn->prepare("SELECT 1 FROM appointment WHERE patient_id = ? AND doctor_id = ? LIMIT 1");
$owns_stmt->bind_param("ii", $patient_id, $doctor_id);
$owns_stmt->execute();
if ($owns_stmt->get_result()->num_rows === 0) {
    http_response_code(403);
    echo "<p class='error-msg'>Not authorized to view this patient's history.</p>";
    exit();
}

$p_stmt = $conn->prepare("SELECT u.full_name, p.dob FROM patient p JOIN user u ON p.user_id = u.user_id WHERE p.patient_id = ?");
$p_stmt->bind_param("i", $patient_id);
$p_stmt->execute();
$patient = $p_stmt->get_result()->fetch_assoc();

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
<h2><?php echo htmlspecialchars($patient['full_name']); ?></h2>
<p>Date of Birth: <?php echo htmlspecialchars($patient['dob']); ?></p>

<h3>Prescription History</h3>
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

<h3>Lab Test History</h3>
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