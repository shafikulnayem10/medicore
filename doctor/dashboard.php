<?php
$required_role = 'Doctor';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$stmt = $conn->prepare("SELECT doctor_id, specialization FROM doctor WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$doctor_id = $doctor['doctor_id'];


$today_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM appointment WHERE doctor_id = ? AND DATE(appointment_date) = CURDATE()");
$today_stmt->bind_param("i", $doctor_id);
$today_stmt->execute();
$today_count = $today_stmt->get_result()->fetch_assoc()['c'];

$patients_stmt = $conn->prepare("SELECT COUNT(DISTINCT patient_id) AS c FROM appointment WHERE doctor_id = ?");
$patients_stmt->bind_param("i", $doctor_id);
$patients_stmt->execute();
$patient_count = $patients_stmt->get_result()->fetch_assoc()['c'];

$pending_lab_stmt = $conn->prepare("
    SELECT COUNT(*) AS c FROM lab_test_request ltr
    JOIN appointment a ON ltr.appointment_id = a.appointment_id
    LEFT JOIN lab_test_result res ON ltr.lab_request_id = res.lab_request_id
    WHERE a.doctor_id = ? AND res.lab_result_id IS NULL
");
$pending_lab_stmt->bind_param("i", $doctor_id);
$pending_lab_stmt->execute();
$pending_lab_count = $pending_lab_stmt->get_result()->fetch_assoc()['c'];

$rx_month_stmt = $conn->prepare("
    SELECT COUNT(*) AS c FROM prescription
    WHERE doctor_id = ? AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
");
$rx_month_stmt->bind_param("i", $doctor_id);
$rx_month_stmt->execute();
$rx_month_count = $rx_month_stmt->get_result()->fetch_assoc()['c'];


$today_list_stmt = $conn->prepare("
    SELECT a.appointment_id, a.appointment_date, a.status, a.patient_id, u.full_name AS patient_name
    FROM appointment a
    JOIN patient p ON a.patient_id = p.patient_id
    JOIN user u ON p.user_id = u.user_id
    WHERE a.doctor_id = ? AND DATE(a.appointment_date) = CURDATE()
    ORDER BY a.appointment_date ASC
");
$today_list_stmt->bind_param("i", $doctor_id);
$today_list_stmt->execute();
$today_appointments = $today_list_stmt->get_result();


$recent_stmt = $conn->prepare("
    SELECT DISTINCT p.patient_id, u.full_name, MAX(a.appointment_date) AS last_visit
    FROM appointment a
    JOIN patient p ON a.patient_id = p.patient_id
    JOIN user u ON p.user_id = u.user_id
    WHERE a.doctor_id = ?
    GROUP BY p.patient_id, u.full_name
    ORDER BY last_visit DESC
    LIMIT 5
");
$recent_stmt->bind_param("i", $doctor_id);
$recent_stmt->execute();
$recent_patients = $recent_stmt->get_result();

function status_badge($status) {
    $map = [
        'Pending'   => 'badge-pending',
        'Confirmed' => 'badge-confirmed',
        'Completed' => 'badge-completed',
        'Cancelled' => 'badge-cancelled',
    ];
    $class = $map[$status] ?? 'badge-pending';
    return "<span class=\"badge $class\">" . htmlspecialchars($status) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Dashboard - MediCore</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php require 'nav.php'; ?>
    <main class="page-content">
        <div class="page-header">
            <div>
                <h1>Good morning, Dr. <?php echo htmlspecialchars($_SESSION['full_name']); ?> 👋</h1>
                <p class="subtitle"><?php echo date('l, F j, Y'); ?> &middot; <?php echo $today_count; ?> appointment<?php echo $today_count == 1 ? '' : 's'; ?> today</p>
            </div>
            <div class="page-actions">
                <a href="appointments.php" class="btn">+ Add Prescription</a>
                <a href="appointments.php" class="btn btn-outline">Request Lab Test</a>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-card">
                <div class="label">Today's Appointments</div>
                <div class="value"><?php echo $today_count; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Patients</div>
                <div class="value"><?php echo $patient_count; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Pending Lab Requests</div>
                <div class="value"><?php echo $pending_lab_count; ?></div>
                <div class="footnote">awaiting results</div>
            </div>
            <div class="stat-card">
                <div class="label">Prescriptions Written</div>
                <div class="value"><?php echo $rx_month_count; ?></div>
                <div class="footnote">this month</div>
            </div>
        </div>

        <div style="display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap;">
            <div class="panel" style="flex:2; min-width:340px;">
                <div class="panel-header">
                    <h2>Today's Appointments</h2>
                    <a href="appointments.php">View All &rarr;</a>
                </div>
                <?php if ($today_appointments->num_rows === 0): ?>
                    <p class="empty-msg">No appointments scheduled for today.</p>
                <?php else: ?>
                <table>
                    <tr><th>Patient</th><th>Time</th><th>Status</th><th>Action</th></tr>
                    <?php while ($row = $today_appointments->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="avatar-cell">
                                <div class="avatar-round"><?php echo strtoupper(substr($row['patient_name'],0,2)); ?></div>
                                <?php echo htmlspecialchars($row['patient_name']); ?>
                            </div>
                        </td>
                        <td><?php echo date('h:i A', strtotime($row['appointment_date'])); ?></td>
                        <td><?php echo status_badge($row['status']); ?></td>
                        <td><a class="btn btn-sm btn-outline" href="appointments.php">View</a></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
                <?php endif; ?>
            </div>

            <div class="panel" style="flex:1; min-width:260px;">
                <div class="panel-header">
                    <h2>Recent Patients</h2>
                </div>
                <?php if ($recent_patients->num_rows === 0): ?>
                    <p class="empty-msg">No patients yet.</p>
                <?php else: ?>
                    <?php while ($row = $recent_patients->fetch_assoc()): ?>
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--mint-card-border);">
                        <div class="avatar-cell">
                            <div class="avatar-round"><?php echo strtoupper(substr($row['full_name'],0,2)); ?></div>
                            <div>
                                <div style="font-size:13px; font-weight:600;"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                <div style="font-size:11px; color:var(--text-muted);"><?php echo date('M j, Y', strtotime($row['last_visit'])); ?></div>
                            </div>
                        </div>
                        <a class="btn btn-sm btn-outline" href="patient-history.php?patient_id=<?php echo $row['patient_id']; ?>">View</a>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    </div><!-- /.main -->
    </div><!-- /.app-shell -->
</body>
</html>
