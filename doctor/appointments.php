<?php
$required_role = 'Doctor';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$stmt = $conn->prepare("SELECT doctor_id FROM doctor WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor_id = $stmt->get_result()->fetch_assoc()['doctor_id'];

$appt_stmt = $conn->prepare("
    SELECT a.appointment_id, a.appointment_date, a.status,
           a.patient_id, u.full_name AS patient_name
    FROM appointment a
    JOIN patient p ON a.patient_id = p.patient_id
    JOIN user u ON p.user_id = u.user_id
    WHERE a.doctor_id = ?
    ORDER BY a.appointment_date DESC
");
$appt_stmt->bind_param("i", $doctor_id);
$appt_stmt->execute();
$appointments = $appt_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointments - MediCore</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            align-items: center; justify-content: center;
            z-index: 999;
        }
        .modal-box {
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-close {
            float: right;
            cursor: pointer;
            font-size: 18px;
            color: #777;
        }
        select.status-select {
            padding: 5px 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .status-msg {
            font-size: 12px;
            margin-left: 6px;
        }
    </style>
</head>
<body>
    <?php require 'nav.php'; ?>

    <div class="page-content">
        <h1>My Appointments</h1>

        <?php if ($appointments->num_rows === 0): ?>
            <p class="empty-msg">No appointments assigned yet.</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Patient</th>
                <th>Date &amp; Time</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $appointments->fetch_assoc()): ?>
            <tr id="appt-row-<?php echo $row['appointment_id']; ?>">
                <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                <td><?php echo date('d M Y, h:i A', strtotime($row['appointment_date'])); ?></td>
                <td>
                    <select class="status-select" data-appointment-id="<?php echo $row['appointment_id']; ?>" onchange="updateStatus(this)">
                        <?php foreach (['Pending', 'Confirmed', 'Completed', 'Cancelled'] as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo $s === $row['status'] ? 'selected' : ''; ?>><?php echo $s; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="status-msg" id="status-msg-<?php echo $row['appointment_id']; ?>"></span>
                </td>
                <td>
                    <a class="btn" href="#" onclick="openHistory(<?php echo $row['patient_id']; ?>); return false;">History</a>
                    <a class="btn" href="create-prescription.php?appointment_id=<?php echo $row['appointment_id']; ?>&patient_id=<?php echo $row['patient_id']; ?>">Prescribe</a>
                    <a class="btn" href="request-lab-test.php?appointment_id=<?php echo $row['appointment_id']; ?>&patient_id=<?php echo $row['patient_id']; ?>">Request Lab Test</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>
    </div>

   
    <div class="modal-overlay" id="historyModal">
        <div class="modal-box">
            <span class="modal-close" onclick="closeHistory()">&times;</span>
            <div id="historyContent">Loading...</div>
        </div>
    </div>

    <script>
       
        function updateStatus(selectEl) {
            const appointmentId = selectEl.dataset.appointmentId;
            const newStatus = selectEl.value;
            const msgEl = document.getElementById('status-msg-' + appointmentId);

            msgEl.textContent = 'Saving...';
            msgEl.style.color = '#777';

            const params = "appointment_id=" + encodeURIComponent(appointmentId) +
                           "&status=" + encodeURIComponent(newStatus);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../ajax/update_appointment_status.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            msgEl.textContent = 'Saved';
                            msgEl.style.color = '#1e7e34';
                        } else {
                            msgEl.textContent = data.error || 'Failed';
                            msgEl.style.color = '#c0392b';
                        }
                    } else {
                        msgEl.textContent = 'Network error';
                        msgEl.style.color = '#c0392b';
                    }
                    setTimeout(function () { msgEl.textContent = ''; }, 2000);
                }
            };

            xhr.send(params);
        }

     
        function openHistory(patientId) {
            const modal = document.getElementById('historyModal');
            const content = document.getElementById('historyContent');
            content.innerHTML = 'Loading...';
            modal.style.display = 'flex';

            var xhr = new XMLHttpRequest();
            xhr.open("GET", "../ajax/get_patient_history.php?patient_id=" + patientId, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        content.innerHTML = xhr.responseText;
                    } else {
                        content.innerHTML = '<p class="error-msg">Failed to load history.</p>';
                    }
                }
            };
            xhr.send();
        }

        function closeHistory() {
            document.getElementById('historyModal').style.display = 'none';
        }
    </script>
</body>
</html>