<?php
$required_role = 'Doctor';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : (isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0);
$patient_id     = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : (isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0);

if ($appointment_id === 0 || $patient_id === 0) {
    header("Location: appointments.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_type = trim($_POST['test_type']);
    $notes     = trim($_POST['notes']);

    if ($test_type === '') {
        $error = "Test type is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO lab_test_request (appointment_id, patient_id, test_type, notes) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $appointment_id, $patient_id, $test_type, $notes);
        if ($stmt->execute()) {
            $success = "Lab test request submitted successfully.";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
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

        <?php if ($success): ?><p class="success-msg"><?php echo $success; ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error-msg"><?php echo $error; ?></p><?php endif; ?>

        <form method="POST" class="card-form">
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
</body>
</html>