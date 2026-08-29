<?php
$required_role = 'Patient';
require_once '../includes/auth_check.php';
require_once '../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Dashboard - MediCore</title>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> (Patient)</h1>
    <p>This is the Patient dashboard. Build appointment booking, prescriptions, and billing view here.</p>
    <a href="../logout.php">Logout</a>
</body>
</html>
