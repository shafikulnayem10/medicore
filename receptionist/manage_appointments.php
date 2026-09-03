<?php
$required_role = 'Receptionist';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$error = '';
$success = '';

// current receptionist's own receptionist_id (to stamp on new appointments)
$rec_row = $conn->query("SELECT receptionist_id FROM receptionist WHERE user_id = " . (int)$_SESSION['user_id'])->fetch_assoc();
$my_receptionist_id = $rec_row ? (int)$rec_row['receptionist_id'] : null;

$valid_statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];

function validate_appointment_input($patient_id, $doctor_id, $reason, $date, $status, $valid_statuses) {
    if ($patient_id <= 0 || $doctor_id <= 0 || $reason === '' || $date === '' || $status === '') {
        return "All fields are required.";
    }
    if (!in_array($status, $valid_statuses)) {
        return "Invalid status.";
    }
    return '';
}

// ---------------- Handle Add ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add') {
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $doctor_id  = (int)($_POST['doctor_id'] ?? 0);
    $reason     = trim($_POST['reason'] ?? '');
    $date       = trim($_POST['appointment_date'] ?? '');
    $status     = $_POST['status'] ?? '';

    $error = validate_appointment_input($patient_id, $doctor_id, $reason, $date, $status, $valid_statuses);
    if ($error === '') {
        $stmt = $conn->prepare("INSERT INTO appointment (patient_id, doctor_id, receptionist_id, reason, appointment_date, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisss", $patient_id, $doctor_id, $my_receptionist_id, $reason, $date, $status);
        if ($stmt->execute()) {
            $success = "Appointment added successfully.";
        } else {
            $error = "Could not add appointment. Please try again.";
        }
    }
}

// ---------------- Handle Edit ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'edit') {
    $appointment_id = (int)$_POST['appointment_id'];
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $doctor_id  = (int)($_POST['doctor_id'] ?? 0);
    $reason     = trim($_POST['reason'] ?? '');
    $date       = trim($_POST['appointment_date'] ?? '');
    $status     = $_POST['status'] ?? '';

    $error = validate_appointment_input($patient_id, $doctor_id, $reason, $date, $status, $valid_statuses);
    if ($error === '') {
        $stmt = $conn->prepare("UPDATE appointment SET patient_id=?, doctor_id=?, reason=?, appointment_date=?, status=? WHERE appointment_id=?");
        $stmt->bind_param("iisssi", $patient_id, $doctor_id, $reason, $date, $status, $appointment_id);
        $stmt->execute();
        $success = "Appointment updated successfully.";
    }
}

// ---------------- Handle Delete ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'delete') {
    $appointment_id = (int)$_POST['appointment_id'];
    $stmt = $conn->prepare("DELETE FROM appointment WHERE appointment_id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $success = "Appointment removed.";
}

// ---------------- Filter tabs + counts ----------------
$filter = $_GET['filter'] ?? 'All';
$counts = [
    'All'       => $conn->query("SELECT COUNT(*) c FROM appointment")->fetch_assoc()['c'],
    'Today'     => $conn->query("SELECT COUNT(*) c FROM appointment WHERE DATE(appointment_date) = CURDATE()")->fetch_assoc()['c'],
    'Pending'   => $conn->query("SELECT COUNT(*) c FROM appointment WHERE status='Pending'")->fetch_assoc()['c'],
    'Confirmed' => $conn->query("SELECT COUNT(*) c FROM appointment WHERE status='Confirmed'")->fetch_assoc()['c'],
    'Cancelled' => $conn->query("SELECT COUNT(*) c FROM appointment WHERE status='Cancelled'")->fetch_assoc()['c'],
];

$where = '';
if ($filter === 'Today') {
    $where = "WHERE DATE(a.appointment_date) = CURDATE()";
} elseif (in_array($filter, ['Pending', 'Confirmed', 'Cancelled'])) {
    $where = "WHERE a.status = '" . $conn->real_escape_string($filter) . "'";
}

$appointments = $conn->query("
    SELECT a.appointment_id, a.appointment_date, a.status, a.reason,
           p.patient_id, up.full_name AS patient_name,
           d.doctor_id, ud.full_name AS doctor_name
    FROM appointment a
    JOIN patient p ON p.patient_id = a.patient_id
    JOIN user up ON up.user_id = p.user_id
    JOIN doctor d ON d.doctor_id = a.doctor_id
    JOIN user ud ON ud.user_id = d.user_id
    $where
    ORDER BY a.appointment_date DESC
")->fetch_all(MYSQLI_ASSOC);

// dropdown data
$patients_list = $conn->query("SELECT p.patient_id, u.full_name FROM patient p JOIN user u ON u.user_id=p.user_id ORDER BY u.full_name")->fetch_all(MYSQLI_ASSOC);
$doctors_list  = $conn->query("SELECT d.doctor_id, u.full_name, d.specialization FROM doctor d JOIN user u ON u.user_id=d.user_id ORDER BY u.full_name")->fetch_all(MYSQLI_ASSOC);

function status_badge_class($status) {
    switch ($status) {
        case 'Confirmed': return 'general';
        case 'Pending':   return 'cabin';
        case 'Cancelled': return 'icu';
        default:          return '';
    }
}

$page_title = 'Manage Appointments';
$active_nav = 'appointments';
require_once '../includes/receptionist_header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>Manage Appointments</h1>
        <p><?php echo (int)$counts['All']; ?> Appointments Across All Dates</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addApptModal')">+ Add New Appointment</button>
</div>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="filter-row">
    <?php foreach (['All', 'Today', 'Pending', 'Confirmed', 'Cancelled'] as $tab): ?>
        <a href="?filter=<?php echo $tab; ?>" class="btn <?php echo $filter === $tab ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
            <?php echo $tab; ?> <span style="opacity:.8;">(<?php echo (int)$counts[$tab]; ?>)</span>
        </a>
    <?php endforeach; ?>
</div>

<div class="card table-card" style="margin-top:16px; overflow-x:auto;">
    <table class="data-table">
        <thead>
            <tr>
                <th>Patient Name</th><th>Doctor Name</th><th>Reason</th><th>Status</th><th>Date & Time</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$appointments): ?>
                <tr class="empty-row"><td colspan="6">No appointments found for this filter.</td></tr>
            <?php endif; ?>
            <?php foreach ($appointments as $a): ?>
                <tr data-id="<?php echo $a['appointment_id']; ?>"
                    data-appointment_id="<?php echo $a['appointment_id']; ?>"
                    data-patient_id="<?php echo $a['patient_id']; ?>"
                    data-doctor_id="<?php echo $a['doctor_id']; ?>"
                    data-reason="<?php echo htmlspecialchars($a['reason']); ?>"
                    data-appointment_date="<?php echo date('Y-m-d\TH:i', strtotime($a['appointment_date'])); ?>"
                    data-status="<?php echo htmlspecialchars($a['status']); ?>">
                    <td><?php echo htmlspecialchars($a['patient_name']); ?></td>
                    <td>Dr. <?php echo htmlspecialchars($a['doctor_name']); ?></td>
                    <td><?php echo htmlspecialchars($a['reason'] ?: '—'); ?></td>
                    <td><span class="badge <?php echo status_badge_class($a['status']); ?>"><?php echo htmlspecialchars($a['status']); ?></span></td>
                    <td><?php echo date('g:i A - M j, Y', strtotime($a['appointment_date'])); ?></td>
                    <td>
                        <button class="icon-btn edit" title="Edit" onclick="fillEditForm(this, 'edit_appt'); openModal('editApptModal')">✎</button>
                        <form method="POST" style="display:inline" onsubmit="return confirmDelete(this, 'Delete this appointment? Any linked bill will also be deleted. This cannot be undone.');">
                            <input type="hidden" name="form_action" value="delete">
                            <input type="hidden" name="appointment_id" value="<?php echo $a['appointment_id']; ?>">
                            <button type="submit" class="icon-btn delete" title="Delete">🗑</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Appointment Modal -->
<div class="modal-overlay" id="addApptModal">
    <div class="modal-box">
        <h2>Add New Appointment</h2>
        <form method="POST">
            <input type="hidden" name="form_action" value="add">
            <div class="form-group"><label>Patient</label>
                <select name="patient_id" required>
                    <option value="">-- Select Patient --</option>
                    <?php foreach ($patients_list as $p): ?>
                        <option value="<?php echo $p['patient_id']; ?>"><?php echo htmlspecialchars($p['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Doctor</label>
                <select name="doctor_id" required>
                    <option value="">-- Select Doctor --</option>
                    <?php foreach ($doctors_list as $d): ?>
                        <option value="<?php echo $d['doctor_id']; ?>">Dr. <?php echo htmlspecialchars($d['full_name']); ?> (<?php echo htmlspecialchars($d['specialization']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Reason</label><input type="text" name="reason" placeholder="e.g. Follow-up" required></div>
            <div class="form-group"><label>Date & Time</label><input type="datetime-local" name="appointment_date" required></div>
            <div class="form-group"><label>Status</label>
                <select name="status" required>
                    <?php foreach ($valid_statuses as $s): ?><option value="<?php echo $s; ?>"><?php echo $s; ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('addApptModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Appointment</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Appointment Modal -->
<div class="modal-overlay" id="editApptModal">
    <div class="modal-box">
        <h2>Edit Appointment</h2>
        <form method="POST">
            <input type="hidden" name="form_action" value="edit">
            <input type="hidden" id="edit_appt_appointment_id" name="appointment_id">
            <div class="form-group"><label>Patient</label>
                <select id="edit_appt_patient_id" name="patient_id" required>
                    <?php foreach ($patients_list as $p): ?>
                        <option value="<?php echo $p['patient_id']; ?>"><?php echo htmlspecialchars($p['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Doctor</label>
                <select id="edit_appt_doctor_id" name="doctor_id" required>
                    <?php foreach ($doctors_list as $d): ?>
                        <option value="<?php echo $d['doctor_id']; ?>">Dr. <?php echo htmlspecialchars($d['full_name']); ?> (<?php echo htmlspecialchars($d['specialization']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Reason</label><input type="text" id="edit_appt_reason" name="reason" required></div>
            <div class="form-group"><label>Date & Time</label><input type="datetime-local" id="edit_appt_appointment_date" name="appointment_date" required></div>
            <div class="form-group"><label>Status</label>
                <select id="edit_appt_status" name="status" required>
                    <?php foreach ($valid_statuses as $s): ?><option value="<?php echo $s; ?>"><?php echo $s; ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('editApptModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/receptionist_footer.php'; ?>
