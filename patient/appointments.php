<?php
$required_role = 'Patient';
require_once '../includes/auth_check.php';
require_once '../config/db.php';


$stmt = $conn->prepare("SELECT patient_id FROM patient WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patientRow = $stmt->get_result()->fetch_object();

if (!$patientRow) {
    die("Patient profile not found.");
}
$patient_id = $patientRow->patient_id;


$stmt = $conn->prepare("
    SELECT a.appointment_id, a.appointment_date, a.status, a.reason,
           u.full_name AS doctor_name, d.specialization
    FROM appointment a
    JOIN doctor d ON a.doctor_id = d.doctor_id
    JOIN user u ON d.user_id = u.user_id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();


$rows = [];
$counts = [
    'All' => 0,
    'Pending' => 0,
    'Confirmed' => 0,
    'Completed' => 0,
    'Cancelled' => 0,
];

while ($row = $result->fetch_object()) {
    $rows[] = $row;
    $counts['All']++;
    $counts[$row->status]++;
}


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
    <title>My Appointments - MediCore</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php require 'nav.php'; ?>
    <main class="page-content">
        <div class="page-header">
            <div>
                <h1>My Appointments</h1>
                <p class="subtitle">Review your past and upcoming visits</p>
            </div>
        </div>

       
        <div class="tab-bar" id="tabBar">
            <?php foreach ($counts as $label => $count): ?>
                <button class="tab-btn <?php echo $label === 'All' ? 'active' : ''; ?>" data-filter="<?php echo $label; ?>">
                    <?php echo $label; ?> <span class="count"><?php echo $count; ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <?php if (empty($rows)): ?>
            <p class="empty-msg">No appointments yet. <a href="book-appointment.php">Book one now</a>.</p>
        <?php else: ?>
        <table id="apptTable">
            <tr>
                <th>Doctor Name</th>
                <th>Specialization</th>
                <th>Date</th>
                <th>Time</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php foreach ($rows as $row):
                $id = $row->appointment_id;
            ?>
            <tr id="appt-row-<?php echo $id; ?>" data-status="<?php echo $row->status; ?>">
                <td>
                    <div class="avatar-cell">
                        <div class="avatar-round"><?php echo strtoupper(substr($row->doctor_name, 0, 2)); ?></div>
                        <?php echo htmlspecialchars($row->doctor_name); ?>
                    </div>
                </td>
                <td><?php echo htmlspecialchars($row->specialization ?: '—'); ?></td>
                <td><?php echo date('M d, Y', strtotime($row->appointment_date)); ?></td>
                <td><?php echo date('h:i A', strtotime($row->appointment_date)); ?></td>
                <td><?php echo htmlspecialchars($row->reason ?: '—'); ?></td>
                <td id="status-cell-<?php echo $id; ?>"><?php echo status_badge($row->status); ?></td>
                <td id="action-cell-<?php echo $id; ?>">
                    <?php if ($row->status === 'Pending'): ?>
                        <button class="btn btn-sm btn-reject" onclick="cancelAppt(<?php echo $id; ?>)">Cancel</button>
                    <?php else: ?>
                        <span style="color:var(--text-muted); font-size:12px;">&mdash;</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </main>
    </div>
    </div>

    <script>
       
        document.querySelectorAll('.tab-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.tab-btn').forEach(function (b) {
                    b.classList.remove('active');
                });
                btn.classList.add('active');

                var filter = btn.dataset.filter;
                document.querySelectorAll('#apptTable tr[data-status]').forEach(function (row) {
                    row.style.display = (filter === 'All' || row.dataset.status === filter) ? '' : 'none';
                });
            });
        });

       
        function cancelAppt(appointmentId) {
            if (!confirm('Cancel this appointment?')) {
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../ajax/cancel_appointment.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;

                if (xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);

                    if (data.success) {
                   
                        document.getElementById('status-cell-' + appointmentId).innerHTML =
                            '<span class="badge badge-cancelled">Cancelled</span>';

                    
                        document.getElementById('appt-row-' + appointmentId).dataset.status = 'Cancelled';

                       
                        document.getElementById('action-cell-' + appointmentId).innerHTML =
                            '<span style="color:var(--text-muted); font-size:12px;">&mdash;</span>';
                    } else {
                        alert(data.error || 'Cancel failed.');
                    }
                } else {
                    alert('Network error. Please try again.');
                }
            };

            xhr.send("appointment_id=" + encodeURIComponent(appointmentId));
        }
    </script>
</body>
</html>