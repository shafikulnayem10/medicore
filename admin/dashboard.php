<?php
$required_role = 'Admin';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

// ---------------- Stat cards (only backed by modules that actually exist) ----------------
$total_doctors  = $conn->query("SELECT COUNT(*) c FROM doctor")->fetch_assoc()['c'];
$active_doctors = $conn->query("SELECT COUNT(*) c FROM doctor WHERE status = 'Active'")->fetch_assoc()['c'];
$specialty_count = $conn->query("SELECT COUNT(DISTINCT specialization) c FROM doctor WHERE specialization IS NOT NULL AND specialization <> ''")->fetch_assoc()['c'];

$total_receptionists  = $conn->query("SELECT COUNT(*) c FROM receptionist")->fetch_assoc()['c'];
$active_receptionists = $conn->query("SELECT COUNT(*) c FROM receptionist WHERE status = 'Active'")->fetch_assoc()['c'];

$ward_totals = $conn->query("SELECT COUNT(*) wards, COALESCE(SUM(total_beds),0) total_beds, COALESCE(SUM(occupied_beds),0) occupied_beds FROM wards")->fetch_assoc();
$total_wards     = (int)$ward_totals['wards'];
$total_beds      = (int)$ward_totals['total_beds'];
$occupied_beds   = (int)$ward_totals['occupied_beds'];
$available_beds  = $total_beds - $occupied_beds;
$occupancy_pct   = $total_beds > 0 ? round(($occupied_beds / $total_beds) * 100) : 0;

$page_title = 'Admin Dashboard';
$active_nav = 'dashboard';
require_once '../includes/admin_header.php';
?>

<div class="page-header">
    <h1>Good morning, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]); ?> 👋</h1>
    <p><?php echo date('l, F j, Y'); ?> &middot; System overview</p>
</div>

<div class="stat-grid">
    <div class="card stat-card">
        <div class="stat-top">Total Doctors <span class="stat-icon">🩺</span></div>
        <div class="stat-value"><?php echo number_format($total_doctors); ?></div>
        <div class="stat-sub"><?php echo (int)$active_doctors; ?> active &middot; across <?php echo (int)$specialty_count; ?> specialties</div>
    </div>
    <div class="card stat-card">
        <div class="stat-top">Total Receptionists <span class="stat-icon">🗒️</span></div>
        <div class="stat-value"><?php echo number_format($total_receptionists); ?></div>
        <div class="stat-sub"><?php echo (int)$active_receptionists; ?> active</div>
    </div>
    <div class="card stat-card">
        <div class="stat-top">Total Wards <span class="stat-icon">🛏️</span></div>
        <div class="stat-value"><?php echo number_format($total_wards); ?></div>
        <div class="stat-sub"><?php echo number_format($total_beds); ?> beds total</div>
    </div>
    <div class="card stat-card">
        <div class="stat-top">Bed Occupancy <span class="stat-icon">📊</span></div>
        <div class="stat-value"><?php echo $occupancy_pct; ?>%</div>
        <div class="stat-sub"><?php echo number_format($available_beds); ?> available / <?php echo number_format($total_beds); ?> total</div>
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <h3 style="margin-top:0;">Quick Actions</h3>
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <a href="manage_doctors.php" class="btn btn-primary">+ Add / Manage Doctors</a>
        <a href="manage_receptionists.php" class="btn btn-outline">+ Add / Manage Receptionists</a>
        <a href="manage_wards.php" class="btn btn-outline">+ Add / Manage Wards</a>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
