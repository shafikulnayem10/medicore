<?php
/**
 * Shared Receptionist layout header.
 *
 * Usage — set these BEFORE including this file:
 *   $page_title = 'Manage Appointments';
 *   $active_nav = 'appointments';   // dashboard | appointments | billing | patients
 *
 * Must be included AFTER auth_check.php + config/db.php have already run.
 */

if (!isset($active_nav)) $active_nav = '';
if (!isset($page_title)) $page_title = 'MediCore';

$rec_name = $_SESSION['full_name'] ?? 'Receptionist';
$rec_code = 'MC-' . date('Y') . '-' . str_pad($_SESSION['user_id'] ?? 0, 5, '0', STR_PAD_LEFT);

$name_parts = preg_split('/\s+/', trim($rec_name));
$initials = strtoupper(substr($name_parts[0], 0, 1) . substr(end($name_parts), 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?> - MediCore</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<header class="topbar">
    <div class="brand"><span class="logo-emoji">🩺</span> MediCore</div>

    <div class="search-box">
        🔍
        <input type="text" placeholder="Search patients, appointments...">
    </div>

    <div class="right-cluster">
        <span class="role-pill">Receptionist</span>
        <div class="user-chip">
            <div class="avatar"><?php echo htmlspecialchars($initials); ?></div>
            <div>
                <div class="u-name"><?php echo htmlspecialchars($rec_name); ?></div>
                <div class="u-code"><?php echo htmlspecialchars($rec_code); ?></div>
            </div>
        </div>
    </div>
</header>

<div class="layout">
    <aside class="sidebar">
        <div class="section-label">Front Desk</div>
        <nav>
            <a href="dashboard.php" class="<?php echo $active_nav === 'dashboard' ? 'active' : ''; ?>">🏠 Dashboard</a>
            <a href="manage_appointments.php" class="<?php echo $active_nav === 'appointments' ? 'active' : ''; ?>">📅 Manage Appointment</a>
            <a href="billing.php" class="<?php echo $active_nav === 'billing' ? 'active' : ''; ?>">🧾 Billing</a>
            <a href="patients.php" class="<?php echo $active_nav === 'patients' ? 'active' : ''; ?>">🗂️ View Patient Profile</a>
        </nav>

        <div class="spacer"></div>

        <a href="../logout.php" class="logout-btn">Log Out</a>

        <div class="account-label">Account</div>
        <a href="change_password.php" class="change-pw">🔒 Change Password</a>
        <div class="mini-profile">
            <div class="avatar" style="width:28px;height:28px;font-size:11px;"><?php echo htmlspecialchars($initials); ?></div>
            <div>
                <div class="u-name"><?php echo htmlspecialchars($rec_name); ?></div>
                <div class="u-code">Receptionist</div>
            </div>
        </div>
    </aside>

    <main class="content">
