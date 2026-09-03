<?php

$current_page = basename($_SERVER['PHP_SELF']);

function nav_active($page, $current) {
    return $page === $current ? 'active' : '';
}
?>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">🩺 MediCore</div>

        <div class="section-label">Clinical</div>
        <a class="nav-link <?php echo nav_active('dashboard.php', $current_page); ?>" href="dashboard.php">Dashboard</a>
        <a class="nav-link <?php echo nav_active('appointments.php', $current_page); ?>" href="appointments.php">Appointments</a>
        <a class="nav-link <?php echo nav_active('lab-results.php', $current_page); ?>" href="lab-results.php">Lab Test Results</a>

        <div class="section-label">Account</div>
        <a class="nav-link" href="../logout.php" style="display:none;"></a>

        <div class="account-card">
            <div class="avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
            <div>
                <div class="name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                <div class="role">Doctor</div>
            </div>
        </div>
        <a class="logout-link" href="../logout.php">Log Out</a>
    </aside>

    <div class="main">
        <header class="topbar">
            <input type="text" class="search" placeholder="Search records, doctors, patients...">
            <div class="spacer"></div>
            <div class="doctor-chip">
                <div class="avatar-sm"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                Dr. <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            </div>
        </header>
