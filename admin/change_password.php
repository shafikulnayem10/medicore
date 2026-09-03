<?php
$required_role = 'Admin';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current  = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password FROM user WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !password_verify($current, $row['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new_pass) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new_pass !== $confirm) {
        $error = "New password and confirmation do not match.";
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE user SET password = ? WHERE user_id = ?");
        $upd->bind_param("si", $hashed, $_SESSION['user_id']);
        $upd->execute();
        $success = "Password updated successfully.";
    }
}

$page_title = 'Change Password';
$active_nav = '';
require_once '../includes/admin_header.php';
?>

<div class="page-header">
    <h1>Change Password</h1>
    <p>Update the password for your Admin account.</p>
</div>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="card" style="max-width:420px;">
    <form method="POST">
        <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
        <div class="form-group"><label>New Password</label><input type="password" name="new_password" minlength="6" required></div>
        <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" minlength="6" required></div>
        <div class="form-actions" style="justify-content:flex-start;">
            <button type="submit" class="btn btn-primary">Update Password</button>
        </div>
    </form>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
