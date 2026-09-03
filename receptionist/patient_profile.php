<?php
$required_role = 'Receptionist';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$patient_id = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';

function initials_of($name) {
    $parts = preg_split('/\s+/', trim($name));
    $first = $parts[0][0] ?? '';
    $last  = count($parts) > 1 ? $parts[count($parts) - 1][0] : '';
    return strtoupper($first . $last);
}
function calc_age($dob) {
    if (!$dob) return null;
    $d = new DateTime($dob);
    return (new DateTime())->diff($d)->y;
}

// ---------------- Handle profile update ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'update_profile') {
    $dob     = trim($_POST['dob'] ?? '');
    $gender  = $_POST['gender'] ?? '';
    $cond    = trim($_POST['medical_condition'] ?? '');

    if ($gender === '') {
        $error = "Gender is required.";
    } else {
        $stmt = $conn->prepare("UPDATE patient SET dob=?, gender=?, medical_condition=? WHERE patient_id=?");
        $dob_or_null = $dob !== '' ? $dob : null;
        $stmt->bind_param("sssi", $dob_or_null, $gender, $cond, $patient_id);
        $stmt->execute();
        $success = "Patient profile updated.";
    }
}

$stmt = $conn->prepare("
    SELECT p.patient_id, p.dob, p.gender, p.medical_condition, u.full_name, u.email, u.phone
    FROM patient p JOIN user u ON u.user_id = p.user_id
    WHERE p.patient_id = ?
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    require_once '../includes/receptionist_header.php';
    echo '<div class="card" style="padding:24px;">Patient not found. <a href="patients.php">Back to Patient List</a></div>';
    require_once '../includes/receptionist_footer.php';
    exit;
}

$appointments = $conn->query("
    SELECT a.appointment_id, a.appointment_date, a.status, a.reason, u.full_name AS doctor_name
    FROM appointment a
    JOIN doctor d ON d.doctor_id = a.doctor_id
    JOIN user u ON u.user_id = d.user_id
    WHERE a.patient_id = $patient_id
    ORDER BY a.appointment_date DESC
")->fetch_all(MYSQLI_ASSOC);

$bills = $conn->query("
    SELECT billing_id, amount, payment_status, generated_at
    FROM billing WHERE patient_id = $patient_id
    ORDER BY generated_at DESC
")->fetch_all(MYSQLI_ASSOC);

$age = calc_age($patient['dob']);

$page_title = 'Patient Profile';
$active_nav = 'patients';
require_once '../includes/receptionist_header.php';
?>

<div class="page-header">
    <p style="margin-bottom:6px;"><a href="patients.php" style="color:var(--primary-dark);">&larr; Back to Patient List</a></p>
    <h1><?php echo htmlspecialchars($patient['full_name']); ?></h1>
    <p>Patient ID: PT-<?php echo str_pad($patient['patient_id'], 3, '0', STR_PAD_LEFT); ?></p>
</div>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="stat-grid" style="grid-template-columns: repeat(3,1fr);">
    <div class="card stat-card">
        <div class="stat-top">Age</div>
        <div class="stat-value"><?php echo $age !== null ? $age : '—'; ?></div>
    </div>
    <div class="card stat-card">
        <div class="stat-top">Phone</div>
        <div class="stat-value" style="font-size:16px;"><?php echo htmlspecialchars($patient['phone'] ?: '—'); ?></div>
    </div>
    <div class="card stat-card">
        <div class="stat-top">Email</div>
        <div class="stat-value" style="font-size:16px;word-break:break-all;"><?php echo htmlspecialchars($patient['email']); ?></div>
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <h3 style="margin-top:0;">Edit Profile Info</h3>
    <form method="POST">
        <input type="hidden" name="form_action" value="update_profile">
        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:14px;">
            <div class="form-group"><label>Date of Birth</label><input type="date" name="dob" value="<?php echo htmlspecialchars($patient['dob'] ?? ''); ?>"></div>
            <div class="form-group"><label>Gender</label>
                <select name="gender" required>
                    <option value="">-- Select --</option>
                    <option value="Male" <?php echo $patient['gender']==='Male'?'selected':''; ?>>Male</option>
                    <option value="Female" <?php echo $patient['gender']==='Female'?'selected':''; ?>>Female</option>
                    <option value="Other" <?php echo $patient['gender']==='Other'?'selected':''; ?>>Other</option>
                </select>
            </div>
            <div class="form-group"><label>Medical Condition</label><input type="text" name="medical_condition" value="<?php echo htmlspecialchars($patient['medical_condition'] ?? ''); ?>" placeholder="e.g. Hypertension"></div>
        </div>
        <div class="form-actions" style="justify-content:flex-start;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<div class="card table-card" style="margin-top:16px; overflow-x:auto;">
    <div class="table-toolbar"><strong>Appointment History</strong></div>
    <table class="data-table">
        <thead><tr><th>Doctor</th><th>Reason</th><th>Status</th><th>Date & Time</th></tr></thead>
        <tbody>
            <?php if (!$appointments): ?>
                <tr class="empty-row"><td colspan="4">No appointments yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($appointments as $a): ?>
                <tr>
                    <td>Dr. <?php echo htmlspecialchars($a['doctor_name']); ?></td>
                    <td><?php echo htmlspecialchars($a['reason'] ?: '—'); ?></td>
                    <td><span class="badge"><?php echo htmlspecialchars($a['status']); ?></span></td>
                    <td><?php echo date('g:i A - M j, Y', strtotime($a['appointment_date'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card table-card" style="margin-top:16px; overflow-x:auto;">
    <div class="table-toolbar"><strong>Billing History</strong></div>
    <table class="data-table">
        <thead><tr><th>Bill ID</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
            <?php if (!$bills): ?>
                <tr class="empty-row"><td colspan="4">No bills yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($bills as $b): ?>
                <tr>
                    <td>BILL-<?php echo str_pad($b['billing_id'], 4, '0', STR_PAD_LEFT); ?></td>
                    <td>৳<?php echo number_format($b['amount'], 2); ?></td>
                    <td><span class="badge <?php echo $b['payment_status']==='Paid'?'general':'icu'; ?>"><?php echo htmlspecialchars($b['payment_status']); ?></span></td>
                    <td><?php echo date('M j, Y', strtotime($b['generated_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/receptionist_footer.php'; ?>
