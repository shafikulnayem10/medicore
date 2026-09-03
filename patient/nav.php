<?php

$current_page = basename($_SERVER['PHP_SELF']);

function nav_active($page, $current) {
    return $page === $current ? 'active' : '';
}
?>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">🩺 MediCore</div>

        <div class="section-label">Care</div>
        <a class="nav-link <?php echo nav_active('dashboard.php', $current_page); ?>" href="dashboard.php">Dashboard</a>
        <a class="nav-link <?php echo nav_active('book-appointment.php', $current_page); ?>" href="book-appointment.php">Book Appointment</a>
        <a class="nav-link <?php echo nav_active('appointments.php', $current_page); ?>" href="appointments.php">My Appointments</a>
        <a class="nav-link <?php echo nav_active('prescriptions.php', $current_page); ?>" href="prescriptions.php">Prescriptions</a>
        <a class="nav-link <?php echo nav_active('bills.php', $current_page); ?>" href="bills.php">My Bills</a>

        <div class="section-label">Account</div>

        <div class="account-card">
            <div class="avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
            <div>
                <div class="name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                <div class="role">Patient</div>
            </div>
        </div>
        <a class="logout-link" href="../logout.php">Log Out</a>
    </aside>

    <div class="main">
        <header class="topbar">
            <input type="text" class="search" placeholder="Search doctors, appointments...">
            <div class="spacer"></div>
            <div class="doctor-chip">
                <div class="avatar-sm"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            </div>
        </header>