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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Prescription - MediCore</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php require 'nav.php'; ?>
    <main class="page-content">
        <div class="page-header">
            <div>
                <h1>Create Prescription</h1>
                <p class="subtitle">Issue a new prescription for a patient</p>
            </div>
        </div>

        <div class="card-form">
            <div id="formMsg"></div>

            <label>Patient</label>
            <input type="text" value="<?php echo htmlspecialchars($patient_name); ?>" disabled>

            <form id="prescriptionForm">
                <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">

                <label>Medication *</label>
                <input type="text" name="medication" placeholder="e.g. Metformin 500mg - twice daily" required>

                <label>Additional Notes</label>
                <textarea name="instructions" rows="4" placeholder="Dosage instructions, precautions, follow-up date etc."></textarea>

                <button type="submit">Save Prescription</button>
            </form>
        </div>

        <p style="margin-top:20px;"><a class="btn btn-outline" href="appointments.php">&larr; Back to Appointments</a></p>
    </main>
    </div><!-- /.main -->
    </div><!-- /.app-shell -->

    <script>
        document.getElementById('prescriptionForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const form = e.target;
            const msgBox = document.getElementById('formMsg');
            msgBox.innerHTML = '';

            const params = "appointment_id=" + encodeURIComponent(form.appointment_id.value) +
                           "&patient_id=" + encodeURIComponent(form.patient_id.value) +
                           "&medication=" + encodeURIComponent(form.medication.value) +
                           "&instructions=" + encodeURIComponent(form.instructions.value);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../ajax/submit_prescription.php", true);
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