<?php
$required_role = 'Receptionist';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$error = '';
$success = '';

// ---------------- Handle Generate Bill ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'generate') {
    $appointment_id = (int)($_POST['appointment_id'] ?? 0);
    $services       = trim($_POST['services'] ?? '');
    $amount         = trim($_POST['amount'] ?? '');

    if ($appointment_id <= 0 || $services === '' || $amount === '') {
        $error = "All fields are required.";
    } elseif (!is_numeric($amount) || (float)$amount <= 0) {
        $error = "Total amount must be a valid number greater than 0.";
    } else {
        $appt = $conn->query("SELECT patient_id FROM appointment WHERE appointment_id = " . $appointment_id)->fetch_assoc();
        if (!$appt) {
            $error = "Selected appointment not found.";
        } else {
            $patient_id = (int)$appt['patient_id'];
            $amount_val = (float)$amount;
            $stmt = $conn->prepare("INSERT INTO billing (patient_id, appointment_id, amount, services, payment_status) VALUES (?, ?, ?, ?, 'Unpaid')");
            $stmt->bind_param("iids", $patient_id, $appointment_id, $amount_val, $services);
            if ($stmt->execute()) {
                $success = "Bill generated successfully.";
            } else {
                $error = "Could not generate bill. Please try again.";
            }
        }
    }
}

// ---------------- Handle Mark as Paid ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'mark_paid') {
    $billing_id = (int)$_POST['billing_id'];
    $stmt = $conn->prepare("UPDATE billing SET payment_status='Paid' WHERE billing_id=?");
    $stmt->bind_param("i", $billing_id);
    $stmt->execute();
    $success = "Bill marked as paid.";
}

// ---------------- Stat cards ----------------
$total_bills   = $conn->query("SELECT COUNT(*) c FROM billing")->fetch_assoc()['c'];
$total_revenue = $conn->query("SELECT COALESCE(SUM(amount),0) t FROM billing WHERE payment_status='Paid'")->fetch_assoc()['t'];
$total_pending = $conn->query("SELECT COALESCE(SUM(amount),0) t FROM billing WHERE payment_status='Unpaid'")->fetch_assoc()['t'];

// dropdown: appointments (with patient + doctor + date) to attach a bill to
$appointments_list = $conn->query("
    SELECT a.appointment_id, a.appointment_date, up.full_name AS patient_name, ud.full_name AS doctor_name
    FROM appointment a
    JOIN patient p ON p.patient_id = a.patient_id
    JOIN user up ON up.user_id = p.user_id
    JOIN doctor d ON d.doctor_id = a.doctor_id
    JOIN user ud ON ud.user_id = d.user_id
    ORDER BY a.appointment_date DESC
")->fetch_all(MYSQLI_ASSOC);

$recent_bills = $conn->query("
    SELECT b.billing_id, b.amount, b.payment_status, b.generated_at, b.services, u.full_name AS patient_name
    FROM billing b
    JOIN patient p ON p.patient_id = b.patient_id
    JOIN user u ON u.user_id = p.user_id
    ORDER BY b.generated_at DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

$page_title = 'Billing Management';
$active_nav = 'billing';
require_once '../includes/receptionist_header.php';
?>

<div class="page-header">
    <h1>Billing Management</h1>
    <p>Generate Invoices &amp; Track Payment Status</p>
</div>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="stat-grid" style="grid-template-columns: repeat(3,1fr);">
    <div class="card stat-card">
        <div class="stat-top">Total Bills</div>
        <div class="stat-value"><?php echo number_format($total_bills); ?></div>
    </div>
    <div class="card stat-card">
        <div class="stat-top">Total Revenue</div>
        <div class="stat-value" style="color:var(--primary-dark);">৳<?php echo number_format($total_revenue, 2); ?></div>
    </div>
    <div class="card stat-card">
        <div class="stat-top">Total Pending</div>
        <div class="stat-value" style="color:var(--danger);">৳<?php echo number_format($total_pending, 2); ?></div>
    </div>
</div>

<div class="card" style="margin-top:16px; max-width:640px;">
    <h3 style="margin-top:0;">Generate New Bill</h3>
    <form method="POST">
        <input type="hidden" name="form_action" value="generate">
        <div class="form-group">
            <label>Appointment (Patient / Doctor / Date)</label>
            <select name="appointment_id" required>
                <option value="">-- Select Appointment --</option>
                <?php foreach ($appointments_list as $a): ?>
                    <option value="<?php echo $a['appointment_id']; ?>">
                        <?php echo htmlspecialchars($a['patient_name']); ?> — Dr. <?php echo htmlspecialchars($a['doctor_name']); ?> — <?php echo date('M j, Y g:i A', strtotime($a['appointment_date'])); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Services Provided</label>
            <div id="serviceLines">
                <input type="text" name="service_lines[]" placeholder="Service 1" style="margin-bottom:8px;" class="service-line-input">
            </div>
            <button type="button" class="btn btn-outline btn-sm" onclick="addServiceLine()">+ Add Service</button>
            <input type="hidden" name="services" id="servicesHidden">
        </div>

        <div class="form-group"><label>Total Amount - BDT</label><input type="number" step="0.01" min="0.01" name="amount" required></div>

        <div class="form-actions" style="justify-content:flex-start;">
            <button type="submit" class="btn btn-primary" onclick="return collectServiceLines();">Generate Bill</button>
        </div>
    </form>
</div>

<div class="card table-card" style="margin-top:16px; overflow-x:auto;">
    <div class="table-toolbar"><strong>Recent Bills</strong></div>
    <table class="data-table">
        <thead><tr><th>Patient</th><th>Services</th><th>Amount</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
            <?php if (!$recent_bills): ?>
                <tr class="empty-row"><td colspan="6">No bills generated yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($recent_bills as $b): ?>
                <tr>
                    <td><?php echo htmlspecialchars($b['patient_name']); ?></td>
                    <td><?php echo htmlspecialchars($b['services'] ?: '—'); ?></td>
                    <td>৳<?php echo number_format($b['amount'], 2); ?></td>
                    <td><span class="badge <?php echo $b['payment_status']==='Paid'?'general':'icu'; ?>"><?php echo htmlspecialchars($b['payment_status']); ?></span></td>
                    <td><?php echo date('M j, Y', strtotime($b['generated_at'])); ?></td>
                    <td>
                        <?php if ($b['payment_status'] === 'Unpaid'): ?>
                        <form method="POST">
                            <input type="hidden" name="form_action" value="mark_paid">
                            <input type="hidden" name="billing_id" value="<?php echo $b['billing_id']; ?>">
                            <button type="submit" class="btn btn-outline btn-sm">Mark as Paid</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function addServiceLine() {
    const wrap = document.getElementById('serviceLines');
    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'service_lines[]';
    input.placeholder = 'Service ' + (wrap.children.length + 1);
    input.className = 'service-line-input';
    input.style.marginBottom = '8px';
    wrap.appendChild(input);
}
function collectServiceLines() {
    const inputs = document.querySelectorAll('.service-line-input');
    const values = Array.from(inputs).map(i => i.value.trim()).filter(v => v !== '');
    document.getElementById('servicesHidden').value = values.join(', ');
    return true;
}
</script>

<?php require_once '../includes/receptionist_footer.php'; ?>
