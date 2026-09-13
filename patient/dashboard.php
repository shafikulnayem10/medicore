<?php
$required_role = 'Patient';
require_once '../includes/auth_check.php';
require_once '../config/db.php';


$stmt = $conn->prepare("SELECT patient_id FROM patient WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient_id = $stmt->get_result()->fetch_assoc()['patient_id'];


$stmt = $conn->prepare("
    SELECT COUNT(*) AS c FROM appointment
    WHERE patient_id = ? AND status IN ('Pending', 'Confirmed') AND appointment_date >= NOW()
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$upcoming_count = $stmt->get_result()->fetch_assoc()['c'];


$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM prescription WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$rx_count = $stmt->get_result()->fetch_assoc()['c'];


$stmt = $conn->prepare("
    SELECT COUNT(*) AS c, COALESCE(SUM(amount), 0) AS total
    FROM billing
    WHERE patient_id = ? AND payment_status = 'Unpaid'
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$unpaid_row = $stmt->get_result()->fetch_assoc();
$unpaid_count = $unpaid_row['c'];
$unpaid_total = $unpaid_row['total'];


$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM appointment WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$total_count = $stmt->get_result()->fetch_assoc()['c'];


$stmt = $conn->prepare("
    SELECT a.appointment_id, a.appointment_date, a.status, a.reason,
           u.full_name AS doctor_name, d.specialization
    FROM appointment a
    JOIN doctor d ON a.doctor_id = d.doctor_id
    JOIN user u ON d.user_id = u.user_id
    WHERE a.patient_id = ? AND a.status IN ('Pending', 'Confirmed') AND a.appointment_date >= NOW()
    ORDER BY a.appointment_date ASC
    LIMIT 5
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$upcoming_appointments = $stmt->get_result();


$stmt = $conn->prepare("
    SELECT pr.prescription_id, pr.medication, pr.created_at, u.full_name AS doctor_name
    FROM prescription pr
    JOIN doctor d ON pr.doctor_id = d.doctor_id
    JOIN user u ON d.user_id = u.user_id
    WHERE pr.patient_id = ?
    ORDER BY pr.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$recent_prescriptions = $stmt->get_result();


function status_badge($status) {
    $class = 'badge-pending';
    if ($status === 'Confirmed') $class = 'badge-confirmed';
    if ($status === 'Completed') $class = 'badge-completed';
    if ($status === 'Cancelled') $class = 'badge-cancelled';

    return '<span class="badge ' . $class . '">' . htmlspecialchars($status) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Dashboard - MediCore</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php require 'nav.php'; ?>
    <main class="page-content">
        <div class="page-header">
            <div>
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> 👋</h1>
                <p class="subtitle">
                    <?php echo date('l, F j, Y'); ?> &middot;
                    <?php echo $upcoming_count; ?> upcoming appointment<?php echo $upcoming_count == 1 ? '' : 's'; ?>
                </p>
            </div>
            <div class="page-actions">
                <a href="book-appointment.php" class="btn">+ Book Appointment</a>
            </div>
        </div>

       
        <div class="stat-grid">
            <div class="stat-card">
                <div class="label">Upcoming Appointments</div>
                <div class="value"><?php echo $upcoming_count; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Appointments</div>
                <div class="value"><?php echo $total_count; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Prescriptions</div>
                <div class="value"><?php echo $rx_count; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Unpaid Bills</div>
                <div class="value"><?php echo $unpaid_count; ?></div>
                <div class="footnote">Tk <?php echo number_format($unpaid_total, 2); ?> outstanding</div>
            </div>
        </div>

        <div style="display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap;">

           
            <div class="panel" style="flex:2; min-width:340px;">
                <div class="panel-header">
                    <h2>Upcoming Appointments</h2>
                    <a href="appointments.php">View All &rarr;</a>
                </div>

                <?php if ($upcoming_appointments->num_rows === 0): ?>
                    <p class="empty-msg">No upcoming appointments. <a href="book-appointment.php">Book one now</a>.</p>
                <?php else: ?>
                <table>
                    <tr><th>Doctor</th><th>Date &amp; Time</th><th>Status</th></tr>
                    <?php while ($row = $upcoming_appointments->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="avatar-cell">
                                <div class="avatar-round"><?php echo strtoupper(substr($row['doctor_name'], 0, 2)); ?></div>
                                Dr. <?php echo htmlspecialchars($row['doctor_name']); ?>
                            </div>
                        </td>
                        <td><?php echo date('M d, Y - h:i A', strtotime($row['appointment_date'])); ?></td>
                        <td><?php echo status_badge($row['status']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
                <?php endif; ?>
            </div>

          
            <div class="panel" style="flex:1; min-width:260px;">
                <div class="panel-header">
                    <h2>Recent Prescriptions</h2>
                    <a href="prescriptions.php">View All &rarr;</a>
                </div>

                <?php if ($recent_prescriptions->num_rows === 0): ?>
                    <p class="empty-msg">No prescriptions yet.</p>
                <?php else: ?>
                    <?php while ($row = $recent_prescriptions->fetch_assoc()): ?>
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--mint-card-border);">
                        <div class="avatar-cell">
                            <div class="avatar-round"><?php echo strtoupper(substr($row['doctor_name'], 0, 2)); ?></div>
                            <div>
                                <div style="font-size:13px; font-weight:600;">Dr. <?php echo htmlspecialchars($row['doctor_name']); ?></div>
                                <div style="font-size:11px; color:var(--text-muted);"><?php echo date('M j, Y', strtotime($row['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    </div>
    </div>
</body>
</html>