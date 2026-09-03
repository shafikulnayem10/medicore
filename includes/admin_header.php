<?php
/**
 * Shared Admin layout header.
 *
 * Usage — set these BEFORE including this file:
 *   $page_title = 'Manage Doctors';   // used in <title> and browser tab
 *   $active_nav = 'doctors';          // one of: dashboard | doctors | receptionists | wards | activity
 *
 * Must be included AFTER auth_check.php + config/db.php have already run,
 * so $_SESSION['full_name'] / $_SESSION['user_id'] are available.
 */

if (!isset($active_nav)) $active_nav = '';
if (!isset($page_title)) $page_title = 'Admin - MediCore';

$admin_name = $_SESSION['full_name'] ?? 'Admin';
$admin_code = 'MC-' . date('Y') . '-' . str_pad($_SESSION['user_id'] ?? 0, 5, '0', STR_PAD_LEFT);

// Initials for the avatar circle, e.g. "Tasnim Rahman" -> "TR"
$name_parts = preg_split('/\s+/', trim($admin_name));
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

    <form class="search-box" action="search.php" method="GET">
        <button type="submit" style="background:none;border:none;color:inherit;cursor:pointer;padding:0;font-size:16px;line-height:1;">🔍</button>
        <input type="text" name="q" placeholder="Search doctors, receptionists, wards..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
    </form>

    <div class="right-cluster">
        <span class="role-pill">Admin</span>
        <div class="user-chip">
            <div class="avatar"><?php echo htmlspecialchars($initials); ?></div>
            <div>
                <div class="u-name"><?php echo htmlspecialchars($admin_name); ?></div>
                <div class="u-code"><?php echo htmlspecialchars($admin_code); ?></div>
            </div>
        </div>
    </div>
</header>

<div class="layout">
    <aside class="sidebar">
        <div class="section-label">Administration</div>
        <nav>
            <a href="dashboard.php" class="<?php echo $active_nav === 'dashboard' ? 'active' : ''; ?>">🏠 Dashboard</a>
            <a href="manage_doctors.php" class="<?php echo $active_nav === 'doctors' ? 'active' : ''; ?>">👨‍⚕️ Manage Doctors</a>
            <a href="manage_receptionists.php" class="<?php echo $active_nav === 'receptionists' ? 'active' : ''; ?>">🗒️ Manage Receptionists</a>
            <a href="manage_wards.php" class="<?php echo $active_nav === 'wards' ? 'active' : ''; ?>">🛏️ Ward & Bed Mgmt</a>
            <a href="activity_log.php" class="<?php echo $active_nav === 'activity' ? 'active' : ''; ?>">📈 Activity Log</a>
        </nav>

        <div class="spacer"></div>

        <a href="../logout.php" class="logout-btn">Log Out</a>

        <div class="account-label">Account</div>
        <a href="change_password.php" class="change-pw">🔒 Change Password</a>
        <div class="mini-profile">
            <div class="avatar" style="width:28px;height:28px;font-size:11px;"><?php echo htmlspecialchars($initials); ?></div>
            <div>
                <div class="u-name"><?php echo htmlspecialchars($admin_name); ?></div>
                <div class="u-code"><?php echo htmlspecialchars($admin_code); ?></div>
            </div>
        </div>
    </aside>

    <main class="content">
