<?php
$required_role = 'Doctor';
require_once '../includes/auth_check.php';
require_once '../config/db.php';


$stmt = $conn->prepare("SELECT doctor_id, specialization FROM doctor WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$doctor_id = $doctor['doctor_id'];


$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM appointment WHERE doctor_id = ?");
$count_stmt->bind_param("i", $doctor_id);
$count_stmt->execute();
$total_appointments = $count_stmt->get_result()->fetch_assoc()['total'];

$pending_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM appointment WHERE doctor_id = ? AND status = 'Pending'");
$pending_stmt->bind_param("i", $doctor_id);
$pending_stmt->execute();
$pending_appointments = $pending_stmt->get_result()->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Dashboard - MediCore</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php require 'nav.php'; ?>

    <div class="page-content">
        <h1>Welcome, Dr. <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
        <p><?php echo htmlspecialchars($doctor['specialization']); ?></p>

        <div style="display:flex; gap:16px; margin-top:20px;">
            <div class="card-form" style="text-align:center;">
                <h2 style="margin:0; font-size:28px;"><?php echo $total_appointments; ?></h2>
                <p>Total Appointments</p>
            </div>
            <div class="card-form" style="text-align:center;">
                <h2 style="margin:0; font-size:28px;"><?php echo $pending_appointments; ?></h2>
                <p>Pending Appointments</p>
            </div>
        </div>

        <div style="margin-top:24px;">
            <a href="appointments.php" class="btn">View Appointments</a>
            <a href="lab-results.php" class="btn">View Lab Results</a>
        </div>
    </div>
</body>
</html>

