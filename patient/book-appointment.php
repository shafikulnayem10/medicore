<?php
$required_role = 'Patient';
require_once '../includes/auth_check.php';
require_once '../config/db.php';


$stmt = $conn->prepare("SELECT patient_id FROM patient WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient_id = $stmt->get_result()->fetch_assoc()['patient_id'];


$doctors_result = $conn->query("
    SELECT d.doctor_id, d.specialization, d.qualification, d.experience, u.full_name
    FROM doctor d
    JOIN user u ON d.user_id = u.user_id
    ORDER BY u.full_name
");
$doctors = $doctors_result->fetch_all(MYSQLI_ASSOC);
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
            <?php foreach ($doctors as $doc): ?>
            <tr>
                <td>
                    <div class="avatar-cell">
                        <div class="avatar-round"><?php echo strtoupper(substr($doc['full_name'], 0, 2)); ?></div>
                        <?php echo htmlspecialchars($doc['full_name']); ?>
                    </div>
                </td>
                <td><?php echo htmlspecialchars($doc['specialization'] ?: '—'); ?></td>
                <td><?php echo htmlspecialchars($doc['qualification'] ?: '—'); ?></td>
                <td><?php echo $doc['experience'] !== null ? $doc['experience'] . ' yrs' : '—'; ?></td>
                <td>
                    <button class="btn btn-sm"
                        onclick="openBooking(<?php echo $doc['doctor_id']; ?>, '<?php echo htmlspecialchars(addslashes($doc['full_name'])); ?>', '<?php echo htmlspecialchars(addslashes($doc['specialization'])); ?>')">
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
                document.getElementById('bookingMsg').innerHTML = '<p style="color:red;">All fields are required.</p>';
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
                if (xhr.readyState !== 4) return;

                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);

                        if (data.success) {
                            document.getElementById('bookingMsg').innerHTML = '<p style="color:green;">Appointment requested successfully!</p>';
                         
                            setTimeout(function () {
                                window.location.href = 'appointments.php';
                            }, 900);
                        } else {
                            document.getElementById('bookingMsg').innerHTML = '<p style="color:red;">' + (data.error || 'Booking failed.') + '</p>';
                        }
                    } catch (err) {
                    
                        console.error("Server Response:", xhr.responseText);
                        document.getElementById('bookingMsg').innerHTML = '<p style="color:red;">Server error occurred. Check console.</p>';
                    }
                } else {
                    document.getElementById('bookingMsg').innerHTML = '<p style="color:red;">Network error. Please try again.</p>';
                }
            };

            xhr.send(params);
        });
    </script>
</body>
</html>