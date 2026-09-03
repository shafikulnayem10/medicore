<nav style="background:#1c6ea4; padding:12px 20px; display:flex; gap:18px; align-items:center;">
    <strong style="color:#fff; margin-right:10px;">MediCore - Doctor</strong>
    <a href="dashboard.php" style="color:#fff; text-decoration:none;">Dashboard</a>
    <a href="appointments.php" style="color:#fff; text-decoration:none;">Appointments</a>
    <a href="lab-results.php" style="color:#fff; text-decoration:none;">Lab Results</a>
    <a href="../logout.php" style="color:#fff; text-decoration:none; margin-left:auto;">Logout (<?php echo htmlspecialchars($_SESSION['full_name']); ?>)</a>
</nav>