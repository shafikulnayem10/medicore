<?php
$required_role = 'Patient';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$stmt = $conn->prepare("SELECT patient_id FROM patient WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient_id = $stmt->get_result()->fetch_assoc()['patient_id'];

$doctors_stmt = $conn->query("
    SELECT d.doctor_id, d.specialization, d.qualification, d.experience, u.full_name
    FROM doctor d
    JOIN user u ON d.user_id = u.user_id
    ORDER BY u.full_name
");
$doctors = $doctors_stmt->fetch_all(MYSQLI_ASSOC);

$specializations = [];
foreach ($doctors as $d) {
    if ($d['specialization'] && !in_array($d['specialization'], $specializations)) {
        $specializations[] = $d['specialization'];
    }
}
sort($specializations);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Appointment - MediCore</title>
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
            width: 460px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-close { float: right; cursor: pointer; font-size: 18px; color: #888; }
        .filter-bar { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
        .filter-bar input[type="text"], .filter-bar select {
            padding: 9px 12px;
            border: 1px solid var(--mint-card-border);
            border-radius: 8px;
            font-size: 13px;
            background: #fff;
        }
        .filter-bar input[type="text"] { flex: 1; min-width: 200px; }
        .doctor-info-box {
            background: var(--mint-bg);
            border: 1px solid var(--mint-card-border);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 13px;
            margin-top: 6px;
        }
    </style>
</head>
<body>
    <?php require 'nav.php'; ?>
    <main class="page-content">
        <div class="page-header">
            <div>
                <h1>Book an Appointment</h1>
                <p class="subtitle">Find a specialist and schedule your visit</p>
            </div>
        </div>

        <div class="filter-bar">
            <input type="text" id="doctorSearch" placeholder="Search doctor by name...">
            <select id="specFilter">
                <option value="">All Specializations</option>
                <?php foreach ($specializations as $s): ?>
                    <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (count($doctors) === 0): ?>
            <p class="empty-msg">No doctors available right now.</p>
        <?php else: ?>
        <table id="doctorTable">
            <tr>
                <th>Doctor Name</th>
                <th>Specialization</th>
                <th>Qualification</th>
                <th>Experience</th>
                <th></th>
            </tr>
            <?php foreach ($doctors as $d): ?>
            <tr data-name="<?php echo htmlspecialchars(strtolower($d['full_name'])); ?>" data-spec="<?php echo htmlspecialchars($d['specialization']); ?>">
                <td>
                    <div class="avatar-cell">
                        <div class="avatar-round"><?php echo strtoupper(substr($d['full_name'],0,2)); ?></div>
                        Dr. <?php echo htmlspecialchars($d['full_name']); ?>
                    </div>
                </td>
                <td><?php echo htmlspecialchars($d['specialization'] ?: '—'); ?></td>
                <td><?php echo htmlspecialchars($d['qualification'] ?: '—'); ?></td>
                <td><?php echo $d['experience'] !== null ? $d['experience'] . ' yrs' : '—'; ?></td>
                <td>
                    <button class="btn btn-sm"
                        onclick="openBooking(<?php echo $d['doctor_id']; ?>, '<?php echo htmlspecialchars(addslashes($d['full_name'])); ?>', '<?php echo htmlspecialchars(addslashes($d['specialization'])); ?>')">
                        Book Now
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </main>
    </div>
    </div>

    <div class="modal-overlay" id="bookingModal">
        <div class="modal-box">
            <span class="modal-close" onclick="closeBooking()">&times;</span>
            <h2 style="margin-top:0;">Confirm Appointment</h2>
            <p class="subtitle" style="margin-top:-8px;">Review the details before submitting</p>

            <label style="font-size:13px; font-weight:600;">Doctor</label>
            <div class="doctor-info-box" id="modalDoctorInfo"></div>

            <form id="bookingForm">
                <div style="display:flex; gap:12px;">
                    <div style="flex:1;">
                        <label style="font-size:13px; font-weight:600;">Preferred Date</label>
                        <input type="date" id="apptDate" name="appt_date" required
                               style="width:100%; padding:9px 12px; margin-top:6px; border:1px solid var(--mint-card-border); border-radius:8px;">
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:13px; font-weight:600;">Time Slot</label>
                        <select id="apptTime" name="appt_time" required
                                style="width:100%; padding:9px 12px; margin-top:6px; border:1px solid var(--mint-card-border); border-radius:8px;">
                            <option value="09:00 AM">09:00 AM</option>
                            <option value="10:00 AM">10:00 AM</option>
                            <option value="11:00 AM">11:00 AM</option>
                            <option value="02:00 PM">02:00 PM</option>
                            <option value="03:00 PM">03:00 PM</option>
                            <option value="04:00 PM">04:00 PM</option>
                        </select>
                    </div>
                </div>

                <label style="font-size:13px; font-weight:600; margin-top:14px; display:block;">Reason for Visit</label>
                <textarea id="apptReason" name="reason" rows="3" placeholder="Briefly describe your symptoms or reason for the visit" required
                          style="width:100%; padding:9px 12px; margin-top:6px; border:1px solid var(--mint-card-border); border-radius:8px; font-family:inherit; box-sizing:border-box;"></textarea>

                <div id="bookingMsg"></div>

                <div style="display:flex; gap:10px; margin-top:18px;">
                    <button type="submit" class="btn" style="flex:1;">Save Changes</button>
                    <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeBooking()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
       r
        document.getElementById('doctorSearch').addEventListener('input', filterDoctors);
        document.getElementById('specFilter').addEventListener('change', filterDoctors);

        function filterDoctors() {
            var q = document.getElementById('doctorSearch').value.toLowerCase();
            var spec = document.getElementById('specFilter').value;
            document.querySelectorAll('#doctorTable tr[data-name]').forEach(function (row) {
                var nameMatch = row.dataset.name.indexOf(q) !== -1;
                var specMatch = (spec === '' || row.dataset.spec === spec);
                row.style.display = (nameMatch && specMatch) ? '' : 'none';
            });
        }

        var selectedDoctorId = null;

        function openBooking(doctorId, doctorName, specialization) {
            selectedDoctorId = doctorId;
            document.getElementById('modalDoctorInfo').innerHTML =
                '<strong>Dr. ' + doctorName + '</strong><br>' + specialization;
            document.getElementById('bookingMsg').innerHTML = '';
            document.getElementById('bookingForm').reset();

            var today = new Date().toISOString().split('T')[0];
            document.getElementById('apptDate').min = today;

            document.getElementById('bookingModal').style.display = 'flex';
        }

        function closeBooking() {
            document.getElementById('bookingModal').style.display = 'none';
        }

        document.getElementById('bookingForm').addEventListener('submit', function (e) {
            e.preventDefault();

            var date = document.getElementById('apptDate').value;
            var time = document.getElementById('apptTime').value;
            var reason = document.getElementById('apptReason').value.trim();

            if (!date || !time || !reason) {
                document.getElementById('bookingMsg').innerHTML = '<p class="error-msg">All fields are required.</p>';
                return;
            }

            var params = "doctor_id=" + encodeURIComponent(selectedDoctorId) +
                         "&appt_date=" + encodeURIComponent(date) +
                         "&appt_time=" + encodeURIComponent(time) +
                         "&reason=" + encodeURIComponent(reason);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../ajax/book_appointment.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            document.getElementById('bookingMsg').innerHTML = '<p class="success-msg">Appointment requested successfully!</p>';
                            setTimeout(function () { window.location.href = 'appointments.php'; }, 900);
                        } else {
                            document.getElementById('bookingMsg').innerHTML = '<p class="error-msg">' + (data.error || 'Booking failed.') + '</p>';
                        }
                    } else {
                        document.getElementById('bookingMsg').innerHTML = '<p class="error-msg">Network error. Please try again.</p>';
                    }
                }
            };
            xhr.send(params);
        });
    </script>
</body>
</html>