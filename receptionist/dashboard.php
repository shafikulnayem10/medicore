<?php
$required_role = 'Receptionist';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$page_title = 'Receptionist Dashboard';
$active_nav = 'dashboard';
require_once '../includes/receptionist_header.php';
?>

<div class="page-header">
    <h1>Good morning, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]); ?> 👋</h1>
    <p><?php echo date('l, F j, Y'); ?> &middot; Front Desk</p>
</div>

<div class="card">
    <h3 style="margin-top:0;">Quick Actions</h3>
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <a href="manage_appointments.php" class="btn btn-primary">+ Manage Appointments</a>
        <a href="billing.php" class="btn btn-outline">🧾 Billing</a>
        <a href="patients.php" class="btn btn-outline">🗂️ View Patient Profile</a>
    </div>
</div>

<?php require_once '../includes/receptionist_footer.php'; ?>
