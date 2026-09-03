<?php
$required_role = 'Doctor';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
if ($patient_id === 0) {
    header("Location: appointments.php");
    exit();
}

$p_stmt = $conn->prepare("SELECT u.full_name, p.dob FROM patient p JOIN user u ON p.user_id = u.user_id WHERE p.patient_id = ?");
$p_stmt->bind_param("i", $patient_id);
$p_stmt->execute();
$patient = $p_stmt->get_result()->fetch_assoc();
if (!$patient) { die("Patient not found."); }

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
$prescriptions_rows = $prescriptions->fetch_all(MYSQLI_ASSOC);

$lab_stmt = $conn->prepare("
    SELECT ltr.test_type, ltres.result_data, ltres.result_date
    FROM lab_test_request ltr
    JOIN lab_test_result ltres ON ltr.lab_request_id = ltres.lab_request_id
    WHERE ltr.patient_id = ?
    ORDER BY ltres.result_date DESC
");
$lab_stmt->bind_param("i", $patient_id);
$lab_stmt->execute();
$lab_rows = $lab_stmt->get_result()->fetch_all(MYSQLI_ASSOC);


$timeline = [];
foreach ($prescriptions_rows as $r) {
    $timeline[] = ['date' => $r['created_at'], 'title' => 'Prescription: ' . $r['medication'], 'by' => $r['doctor_name']];
}
foreach ($lab_rows as $r) {
    $timeline[] = ['date' => $r['result_date'], 'title' => 'Lab Result: ' . $r['test_type'], 'by' => null];
}
usort($timeline, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Profile - MediCore</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php require 'nav.php'; ?>
    <main class="page-content">
        <p><a href="appointments.php" style="color:var(--primary); text-decoration:none; font-size:13px;">&larr; Back</a></p>
        <div class="page-header">
            <h1>Patient Profile</h1>
        </div>

        <div class="panel" style="margin-bottom:16px;">
            <div class="avatar-cell">
                <div class="avatar-round" style="width:44px; height:44px; font-size:16px;"><?php echo strtoupper(substr($patient['full_name'],0,2)); ?></div>
                <div>
                    <div style="font-weight:700; font-size:16px;"><?php echo htmlspecialchars($patient['full_name']); ?></div>
                    <div style="font-size:12px; color:var(--text-muted);">DOB: <?php echo htmlspecialchars($patient['dob']); ?></div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="content-tabs">
                <button class="tab-link active" data-tab="history">Medical History</button>
                <button class="tab-link" data-tab="prescriptions">Prescriptions</button>
                <button class="tab-link" data-tab="lab">Lab Reports</button>
            </div>

            <div class="tab-panel active" id="tab-history">
                <?php if (count($timeline) === 0): ?>
                    <p class="empty-msg">No medical history recorded yet.</p>
                <?php else: ?>
                    <?php foreach ($timeline as $item): ?>
                        <div style="padding:10px 0; border-bottom:1px solid var(--mint-card-border);">
                            <div style="font-size:11px; color:var(--text-muted);">&#9656; <?php echo date('M j, Y', strtotime($item['date'])); ?></div>
                            <div style="font-weight:600; font-size:14px;"><?php echo htmlspecialchars($item['title']); ?></div>
                            <?php if ($item['by']): ?><div style="font-size:12px; color:var(--text-muted);">by Dr. <?php echo htmlspecialchars($item['by']); ?></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="tab-panel" id="tab-prescriptions">
                <?php if (count($prescriptions_rows) === 0): ?>
                    <p class="empty-msg">No previous prescriptions.</p>
                <?php else: ?>
                <table>
                    <tr><th>Date</th><th>Doctor</th><th>Medication</th><th>Instructions</th></tr>
                    <?php foreach ($prescriptions_rows as $row): ?>
                    <tr>
                        <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                        <td>Dr. <?php echo htmlspecialchars($row['doctor_name']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($row['medication'])); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($row['instructions'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
            </div>

            <div class="tab-panel" id="tab-lab">
                <?php if (count($lab_rows) === 0): ?>
                    <p class="empty-msg">No lab test results yet.</p>
                <?php else: ?>
                <table>
                    <tr><th>Test Type</th><th>Result</th><th>Date</th></tr>
                    <?php foreach ($lab_rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['test_type']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($row['result_data'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['result_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
    </div><!-- /.main -->
    </div><!-- /.app-shell -->

    <script>
        document.querySelectorAll('.tab-link').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.tab-link').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
            });
        });
    </script>
</body>
</html>
