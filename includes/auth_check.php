<?php
/**
 * Shared authentication guard.
 *
 * Usage — put this at the very top of any protected page, BEFORE any HTML output:
 *
 *     <?php
 *     $required_role = 'Doctor';   // 'Doctor' | 'Admin' | 'Receptionist' | 'Patient'
 *     require_once '../includes/auth_check.php';
 *     ?>
 *
 * If $required_role is not set, this only checks that SOME user is logged in
 * (use that for pages any logged-in role can view).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Not logged in at all -> back to login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: /medicore/login.php");
    exit();
}

// Logged in, but wrong role trying to access someone else's module
if (isset($required_role) && $_SESSION['user_type'] !== $required_role) {
    header("Location: /medicore/login.php?error=unauthorized");
    exit();
}
