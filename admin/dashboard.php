<?php
$required_role = 'Admin';
require_once '../includes/auth_check.php';
require_once '../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - MediCore</title>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> (Admin)</h1>
    <p>This is the Admin dashboard. Build doctor/receptionist/bed management features here.</p>
    <a href="../logout.php">Logout</a>
</body>
</html>
