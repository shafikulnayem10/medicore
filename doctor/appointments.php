<?php
$required_role = 'Doctor';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

// Get logged-in doctor's ID
$stmt = $conn->prepare("SELECT doctor_id FROM doctor WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctorRow = $stmt->get_result()->fetch_object();

if (!$doctorRow) {
    die("Doctor profile not found.");
}
$doctor_id = $doctorRow->doctor_id;

// Get all appointments for this doctor
$sql = "SELECT a.appointment_id, a.appointment_date, a.status, a.patient_id, u.full_name AS patient_name
        FROM appointment a
        JOIN patient p ON a.patient_id = p.patient_id
        JOIN user u ON p.user_id = u.user_id
        WHERE a.doctor_id = ?
        ORDER BY a.appointment_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
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
    <style>
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(20,50,42,0.45);
            align-items: center; justify-content: center;
            z-index: 999;
        }
        .modal-box {
            background: #fff;
            padding: 24px;
            border-radius: 10px;
            width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-close { float: right; cursor: pointer; font-size: 18px; color: #888; }
    </style>
</head>
<body>
    <?php require 'nav.php'; ?>
    <main class="page-content">
        <div class="page-header">
            <div>
                <h1>My Appointments</h1>
                <p class="subtitle">Review, approve, or update patient appointments</p>
            </div>
        </div>

        <!-- Status filter tabs -->
        <div class="tab-bar" id="tabBar">
            <?php foreach ($counts as $label => $count): ?>
                <button class="tab-btn <?php echo $label === 'All' ? 'active' : ''; ?>" data-filter="<?php echo $label; ?>">
                    <?php echo $label; ?> <span class="count"><?php echo $count; ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <?php if (empty($rows)): ?>
            <p class="empty-msg">No appointments assigned yet.</p>
        <?php else: ?>
        <table id="apptTable">
            <tr>
                <th>Patient</th>
                <th>Date &amp; Time</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php foreach ($rows as $row):
                $id = $row->appointment_id;
            ?>
            <tr id="appt-row-<?php echo $id; ?>" data-status="<?php echo $row->status; ?>">
                <td>
                    <div class="avatar-cell">
                        <div class="avatar-round"><?php echo strtoupper(substr($row->patient_name, 0, 2)); ?></div>
                        <?php echo htmlspecialchars($row->patient_name); ?>
                    </div>
                </td>
                <td><?php echo date('M d, Y - h:i A', strtotime($row->appointment_date)); ?></td>
                <td id="status-cell-<?php echo $id; ?>"><?php echo status_badge($row->status); ?></td>
                <td>
                    <div id="actions-<?php echo $id; ?>" style="display:flex; gap:6px; flex-wrap:wrap;">
                        <?php if ($row->status === 'Pending'): ?>
                            <button class="btn btn-sm btn-approve" onclick="updateStatus(<?php echo $id; ?>, 'Confirmed')">Approve</button>
                            <button class="btn btn-sm btn-reject" onclick="updateStatus(<?php echo $id; ?>, 'Cancelled')">Reject</button>
                        <?php elseif ($row->status === 'Confirmed'): ?>
                            <button class="btn btn-sm btn-done" onclick="updateStatus(<?php echo $id; ?>, 'Completed')">Mark as Done</button>
                        <?php else: ?>
                            <span style="color:var(--text-muted); font-size:12px;">&mdash;</span>
                        <?php endif; ?>
                    </div>
                    <div style="margin-top:6px; display:flex; gap:6px; flex-wrap:wrap;">
                        <a class="btn btn-sm btn-outline" href="#" onclick="openHistory(<?php echo $row->patient_id; ?>); return false;">History</a>
                        <a class="btn btn-sm btn-outline" href="create-prescription.php?appointment_id=<?php echo $id; ?>&patient_id=<?php echo $row->patient_id; ?>">Prescribe</a>
                        <a class="btn btn-sm btn-outline" href="request-lab-test.php?appointment_id=<?php echo $id; ?>&patient_id=<?php echo $row->patient_id; ?>">Lab Test</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </main>
    </div><!-- /.main -->
    </div><!-- /.app-shell -->

    <!-- History modal -->
    <div class="modal-overlay" id="historyModal">
        <div class="modal-box">
            <span class="modal-close" onclick="closeHistory()">&times;</span>
            <div id="historyContent">Loading...</div>
        </div>
    </div>

    <script>
        // Tab filtering
        document.querySelectorAll('.tab-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                var filter = btn.dataset.filter;
                document.querySelectorAll('#apptTable tr[data-status]').forEach(function (row) {
                    row.style.display = (filter === 'All' || row.dataset.status === filter) ? '' : 'none';
                });
            });
        });

        var badgeClass = {
            'Pending': 'badge-pending',
            'Confirmed': 'badge-confirmed',
            'Completed': 'badge-completed',
            'Cancelled': 'badge-cancelled'
        };

        function renderActions(appointmentId, status) {
            var actionsEl = document.getElementById('actions-' + appointmentId);

            if (status === 'Pending') {
                actionsEl.innerHTML =
                    '<button class="btn btn-sm btn-approve" onclick="updateStatus(' + appointmentId + ', \'Confirmed\')">Approve</button>' +
                    '<button class="btn btn-sm btn-reject" onclick="updateStatus(' + appointmentId + ', \'Cancelled\')">Reject</button>';
            } else if (status === 'Confirmed') {
                actionsEl.innerHTML =
                    '<button class="btn btn-sm btn-done" onclick="updateStatus(' + appointmentId + ', \'Completed\')">Mark as Done</button>';
            } else {
                actionsEl.innerHTML = '<span style="color:var(--text-muted); font-size:12px;">&mdash;</span>';
            }
        }

        function updateStatus(appointmentId, newStatus) {
            var params = "appointment_id=" + encodeURIComponent(appointmentId) +
                         "&status=" + encodeURIComponent(newStatus);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../ajax/update_appointment_status.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;

                if (xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        document.getElementById('status-cell-' + appointmentId).innerHTML =
                            '<span class="badge ' + badgeClass[newStatus] + '">' + newStatus + '</span>';

                        document.getElementById('appt-row-' + appointmentId).dataset.status = newStatus;
                        renderActions(appointmentId, newStatus);
                    } else {
                        alert(data.error || 'Update failed.');
                    }
                } else {
                    alert('Network error. Please try again.');
                }
            };

            xhr.send(params);
        }

        function openHistory(patientId) {
            var modal = document.getElementById('historyModal');
            var content = document.getElementById('historyContent');
            content.innerHTML = 'Loading...';
            modal.style.display = 'flex';

            var xhr = new XMLHttpRequest();
            xhr.open("GET", "../ajax/get_patient_history.php?patient_id=" + patientId, true);

            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;
                content.innerHTML = (xhr.status === 200) ? xhr.responseText : '<p class="error-msg">Failed to load history.</p>';
            };

            xhr.send();
        }

        function closeHistory() {
            document.getElementById('historyModal').style.display = 'none';
        }
    </script>
</body>
</html>