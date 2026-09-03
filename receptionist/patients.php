<?php
$required_role = 'Receptionist';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

function initials_of($name) {
    $parts = preg_split('/\s+/', trim($name));
    $first = $parts[0][0] ?? '';
    $last  = count($parts) > 1 ? $parts[count($parts) - 1][0] : '';
    return strtoupper($first . $last);
}
function avatar_color($seed) {
    $palette = ['#2f6fb5', '#1a9c76', '#c25b2c', '#7a54c7', '#c74e7e', '#3aa0a0'];
    return $palette[$seed % count($palette)];
}
function calc_age($dob) {
    if (!$dob) return null;
    $d = new DateTime($dob);
    $now = new DateTime();
    return $now->diff($d)->y;
}

$patients = $conn->query("
    SELECT p.patient_id, p.dob, p.gender, p.medical_condition,
           u.full_name, u.phone,
           (SELECT MAX(appointment_date) FROM appointment WHERE patient_id = p.patient_id) AS last_visit
    FROM patient p
    JOIN user u ON u.user_id = p.user_id
    ORDER BY u.full_name
")->fetch_all(MYSQLI_ASSOC);

$page_title = 'View Patient Profile';
$active_nav = 'patients';
require_once '../includes/receptionist_header.php';
?>

<div class="page-header">
    <h1>Patient List</h1>
    <p><?php echo count($patients); ?> Patients Under Your Care</p>
</div>

<div class="card table-card" style="overflow-x:auto;">
    <div class="table-toolbar">
        <input type="text" class="search-input" placeholder="Search by name or patient ID..."
               oninput="filterTable(this, 'patientsTbody')">
    </div>
    <table class="data-table">
        <thead>
            <tr><th>Patient Name</th><th>Age - Gender</th><th>Phone</th><th>Condition</th><th>Last Visit</th><th></th></tr>
        </thead>
        <tbody id="patientsTbody">
            <?php if (!$patients): ?>
                <tr class="empty-row"><td colspan="6">No patients registered yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($patients as $p):
                $age = calc_age($p['dob']);
            ?>
                <tr>
                    <td>
                        <div class="person-cell">
                            <div class="avatar-sm" style="background:<?php echo avatar_color($p['patient_id']); ?>"><?php echo initials_of($p['full_name']); ?></div>
                            <div class="p-name"><?php echo htmlspecialchars($p['full_name']); ?></div>
                        </div>
                    </td>
                    <td><?php echo $age !== null ? $age : '—'; ?> - <?php echo htmlspecialchars($p['gender'] ?: '—'); ?></td>
                    <td><?php echo htmlspecialchars($p['phone'] ?: '—'); ?></td>
                    <td><span class="badge <?php echo $p['medical_condition'] ? 'general' : ''; ?>"><?php echo htmlspecialchars($p['medical_condition'] ?: 'None noted'); ?></span></td>
                    <td><?php echo $p['last_visit'] ? date('M j, Y', strtotime($p['last_visit'])) : 'No visits yet'; ?></td>
                    <td><a class="btn btn-outline btn-sm" href="patient_profile.php?id=<?php echo $p['patient_id']; ?>">View Profile</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/receptionist_footer.php'; ?>
