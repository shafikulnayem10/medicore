<?php
$required_role = 'Doctor';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Lab Test - MediCore</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php require 'nav.php'; ?>

    <div class="page-content">
        <h1>Request Lab Test</h1>
        <p>Patient: <strong><?php echo htmlspecialchars($patient_name); ?></strong></p>

        <div id="formMsg"></div>

        <form id="labTestForm" class="card-form">
            <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
            <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">

            <label>Test Type</label>
            <input type="text" name="test_type" placeholder="e.g. Complete Blood Count" required>

            <label>Notes</label>
            <textarea name="notes" rows="4" placeholder="Any additional notes for the lab"></textarea>

            <button type="submit">Submit Request</button>
        </form>

        <p style="margin-top:20px;"><a class="btn" href="appointments.php">&larr; Back to Appointments</a></p>
    </div>

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
