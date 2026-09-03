<?php
$required_role = 'Doctor';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$stmt = $conn->prepare("SELECT doctor_id FROM doctor WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor_id = $stmt->get_result()->fetch_assoc()['doctor_id'];

$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
$patient_id     = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
if ($appointment_id === 0 || $patient_id === 0) {
    header("Location: appointments.php");
    exit();
}

$p_stmt = $conn->prepare("SELECT u.full_name FROM patient p JOIN user u ON p.user_id = u.user_id WHERE p.patient_id = ?");
$p_stmt->bind_param("i", $patient_id);
$p_stmt->execute();
$patient_name = $p_stmt->get_result()->fetch_assoc()['full_name'] ?? 'Unknown';


$my_requests_stmt = $conn->prepare("
    SELECT ltr.test_type, ltr.requested_at, u.full_name AS patient_name,
           CASE WHEN res.lab_result_id IS NULL THEN 'Pending' ELSE 'Done' END AS req_status
    FROM lab_test_request ltr
    JOIN appointment a ON ltr.appointment_id = a.appointment_id
    JOIN patient p ON ltr.patient_id = p.patient_id
    JOIN user u ON p.user_id = u.user_id
    LEFT JOIN lab_test_result res ON ltr.lab_request_id = res.lab_request_id
    WHERE a.doctor_id = ?
    ORDER BY ltr.requested_at DESC
    LIMIT 10
");
$my_requests_stmt->bind_param("i", $doctor_id);
$my_requests_stmt->execute();
$my_requests = $my_requests_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Test Request - MediCore</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php require 'nav.php'; ?>
    <main class="page-content">
        <div class="page-header">
            <div>
                <h1>Lab Test Request</h1>
                <p class="subtitle">Order lab tests for your patients</p>
            </div>
        </div>

        <div class="card-form">
            <div id="formMsg"></div>

            <label>Patient</label>
            <input type="text" value="<?php echo htmlspecialchars($patient_name); ?>" disabled>

            <form id="labTestForm">
                <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">

                <label>Test Name</label>
                <input type="text" name="test_type" placeholder="e.g. Complete blood count, lipid panel" required>

                <label>Additional Notes</label>
                <textarea name="notes" rows="3" placeholder="Optional clinical notes for the lab"></textarea>

                <button type="submit">Submit Request</button>
            </form>
        </div>

        <div class="panel" style="margin-top:24px; max-width:900px;">
            <div class="panel-header"><h2>My Lab Requests</h2></div>
            <?php if ($my_requests->num_rows === 0): ?>
                <p class="empty-msg">No lab requests yet.</p>
            <?php else: ?>
            <table>
                <tr><th>Patient</th><th>Test Name</th><th>Requested Date</th><th>Status</th></tr>
                <?php while ($row = $my_requests->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div class="avatar-cell">
                            <div class="avatar-round"><?php echo strtoupper(substr($row['patient_name'],0,2)); ?></div>
                            <?php echo htmlspecialchars($row['patient_name']); ?>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($row['test_type']); ?></td>
                    <td><?php echo date('M j, Y', strtotime($row['requested_at'])); ?></td>
                    <td><span class="badge <?php echo $row['req_status'] === 'Done' ? 'badge-done' : 'badge-pending'; ?>"><?php echo $row['req_status']; ?></span></td>
                </tr>
                <?php endwhile; ?>
            </table>
            <?php endif; ?>
        </div>

        <p style="margin-top:20px;"><a class="btn btn-outline" href="appointments.php">&larr; Back to Appointments</a></p>
    </main>
    </div><!-- /.main -->
    </div><!-- /.app-shell -->

    <script>
        document.getElementById('labTestForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const form = e.target;
            const msgBox = document.getElementById('formMsg');
            msgBox.innerHTML = '';

            const params = "appointment_id=" + encodeURIComponent(form.appointment_id.value) +
                           "&patient_id=" + encodeURIComponent(form.patient_id.value) +
                           "&test_type=" + encodeURIComponent(form.test_type.value) +
                           "&notes=" + encodeURIComponent(form.notes.value);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../ajax/submit_lab_test.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            msgBox.innerHTML = '<p class="success-msg">' + data.message + '</p>';
                            form.reset();
                        } else {
                            msgBox.innerHTML = '<p class="error-msg">' + data.error + '</p>';
                        }
                    } else {
                        msgBox.innerHTML = '<p class="error-msg">Network error. Please try again.</p>';
                    }
                }
            };
            xhr.send(params);
        });
    </script>
</body>
</html>