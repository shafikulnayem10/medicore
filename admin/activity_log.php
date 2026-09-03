<?php
$required_role = 'Admin';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$logs = $conn->query("
    SELECT a.auth_id, a.login_time, a.logout_time, u.full_name, u.user_type
    FROM authentication a
    JOIN user u ON u.user_id = a.user_id
    ORDER BY a.login_time DESC
    LIMIT 100
")->fetch_all(MYSQLI_ASSOC);

$page_title = 'Activity Log';
$active_nav = 'activity';
require_once '../includes/admin_header.php';
?>

<div class="page-header">
    <h1>Activity Log</h1>
    <p>Recent login activity across all roles</p>
</div>

<div class="card table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>User</th>
                <th>Role</th>
                <th>Login Time</th>
                <th>Logout Time</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$logs): ?>
                <tr class="empty-row"><td colspan="4">No activity recorded yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['full_name']); ?></td>
                    <td><span class="badge"><?php echo htmlspecialchars($log['user_type']); ?></span></td>
                    <td><?php echo $log['login_time'] ? date('M j, Y g:i A', strtotime($log['login_time'])) : '—'; ?></td>
                    <td><?php echo $log['logout_time'] ? date('M j, Y g:i A', strtotime($log['logout_time'])) : '<span style="color:#1a9c76;">Active session</span>'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
