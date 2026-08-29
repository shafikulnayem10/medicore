<?php
$required_role = 'Receptionist';
require_once '../includes/auth_check.php';
require_once '../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receptionist Dashboard - MediCore</title>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> (Receptionist)</h1>
    <p>This is the Receptionist dashboard. Build appointment management and billing features here.</p>
    <a href="../logout.php">Logout</a>
</body>
</html>
