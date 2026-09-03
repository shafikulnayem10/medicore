<?php
$required_role = 'Admin';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$q = trim($_GET['q'] ?? '');
$doctors = [];
$receptionists = [];
$wards = [];

if ($q !== '') {
    $like = '%' . $q . '%';

    $stmt = $conn->prepare("
        SELECT d.doctor_id, d.specialization, d.status, u.user_id, u.full_name, u.email, u.phone
        FROM doctor d JOIN user u ON u.user_id = d.user_id
        WHERE u.full_name LIKE ? OR d.specialization LIKE ? OR u.phone LIKE ? OR u.email LIKE ?
        ORDER BY u.full_name
    ");
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $doctors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("
        SELECT r.receptionist_id, r.shift, r.status, u.user_id, u.full_name, u.email, u.phone
        FROM receptionist r JOIN user u ON u.user_id = r.user_id
        WHERE u.full_name LIKE ? OR u.phone LIKE ? OR u.email LIKE ? OR r.shift LIKE ?
        ORDER BY u.full_name
    ");
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $receptionists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("SELECT * FROM wards WHERE ward_name LIKE ? OR ward_type LIKE ? ORDER BY ward_name");
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $wards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$total_results = count($doctors) + count($receptionists) + count($wards);

function initials_of($name) {
    $parts = preg_split('/\s+/', trim($name));
    $first = $parts[0][0] ?? '';
    $last  = count($parts) > 1 ? $parts[count($parts) - 1][0] : '';
    return strtoupper($first . $last);
}

$page_title = 'Search Results';
$active_nav = '';
require_once '../includes/admin_header.php';
?>

<div class="page-header">
    <h1>Search Results</h1>
    <p>
        <?php if ($q === ''): ?>
            Type something in the search bar above to search doctors, receptionists, and wards.
        <?php else: ?>
            <?php echo $total_results; ?> result<?php echo $total_results === 1 ? '' : 's'; ?> for &ldquo;<?php echo htmlspecialchars($q); ?>&rdquo;
        <?php endif; ?>
    </p>
</div>

<?php if ($q !== '' && $total_results === 0): ?>
    <div class="card" style="padding:30px; text-align:center; color:var(--text-muted);">
        No matches found for &ldquo;<?php echo htmlspecialchars($q); ?>&rdquo;.
    </div>
<?php endif; ?>

<?php if ($doctors): ?>
<div class="card table-card" style="margin-bottom:16px;">
    <div class="table-toolbar"><strong>Doctors (<?php echo count($doctors); ?>)</strong></div>
    <table class="data-table">
        <thead>
            <tr><th>Doctor Name</th><th>Email</th><th>Phone</th><th>Specialization</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($doctors as $doc): ?>
            <tr>
                <td>
                    <div class="person-cell">
                        <div class="avatar-sm" style="background:#1a9c76;"><?php echo initials_of($doc['full_name']); ?></div>
                        <div class="p-name">Dr. <?php echo htmlspecialchars($doc['full_name']); ?></div>
                    </div>
                </td>
                <td><?php echo htmlspecialchars($doc['email'] ?: '—'); ?></td>
                <td><?php echo htmlspecialchars($doc['phone'] ?: '—'); ?></td>
                <td><span class="badge"><?php echo htmlspecialchars($doc['specialization']); ?></span></td>
                <td><span class="badge <?php echo $doc['status'] === 'Active' ? 'general' : 'icu'; ?>"><?php echo htmlspecialchars($doc['status'] ?: 'Active'); ?></span></td>
                <td><a class="btn btn-outline btn-sm" href="manage_doctors.php#row-doctor-<?php echo $doc['doctor_id']; ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if ($receptionists): ?>
<div class="card table-card" style="margin-bottom:16px;">
    <div class="table-toolbar"><strong>Receptionists (<?php echo count($receptionists); ?>)</strong></div>
    <table class="data-table">
        <thead>
            <tr><th>Name</th><th>Email</th><th>Phone</th><th>Shift</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($receptionists as $rec): ?>
            <tr>
                <td>
                    <div class="person-cell">
                        <div class="avatar-sm" style="background:#e0524a;"><?php echo initials_of($rec['full_name']); ?></div>
                        <div class="p-name"><?php echo htmlspecialchars($rec['full_name']); ?></div>
                    </div>
                </td>
                <td><?php echo htmlspecialchars($rec['email'] ?: '—'); ?></td>
                <td><?php echo htmlspecialchars($rec['phone'] ?: '—'); ?></td>
                <td><?php echo htmlspecialchars($rec['shift'] ?: '—'); ?></td>
                <td><span class="badge <?php echo $rec['status'] === 'Active' ? 'general' : 'icu'; ?>"><?php echo htmlspecialchars($rec['status'] ?: 'Active'); ?></span></td>
                <td><a class="btn btn-outline btn-sm" href="manage_receptionists.php#row-receptionist-<?php echo $rec['receptionist_id']; ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if ($wards): ?>
<div class="card" style="margin-bottom:16px; padding:18px;">
    <strong>Wards (<?php echo count($wards); ?>)</strong>
    <div class="ward-grid" style="margin-top:14px;">
        <?php foreach ($wards as $ward):
            $total = (int)$ward['total_beds'];
            $occupied = (int)$ward['occupied_beds'];
            $available = $total - $occupied;
            $type_class = strtolower($ward['ward_type']);
        ?>
        <div class="card ward-card">
            <div class="ward-top">
                <h3><?php echo htmlspecialchars($ward['ward_name']); ?></h3>
                <span class="badge <?php echo $type_class; ?>"><?php echo htmlspecialchars($ward['ward_type']); ?></span>
            </div>
            <div class="ward-stats">
                <div>Available<strong class="available-num <?php echo $available <= 0 ? 'zero' : ''; ?>"><?php echo $available; ?></strong></div>
                <div style="text-align:right;">Total<strong><?php echo $total; ?></strong></div>
            </div>
            <div class="ward-actions">
                <a class="btn btn-outline" href="manage_wards.php#row-ward-<?php echo $ward['id']; ?>">Open in Ward Mgmt</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/admin_footer.php'; ?>
