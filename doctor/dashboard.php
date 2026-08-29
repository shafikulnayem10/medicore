<?php
$required_role = 'Doctor';
require_once '../includes/auth_check.php';
require_once '../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Dashboard - MediCore</title>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> (Doctor)</h1>
    <p>This is the Doctor dashboard. Build appointments, prescriptions, and lab test features here.</p>
    <a href="../logout.php">Logout</a>
</body>
</html>
