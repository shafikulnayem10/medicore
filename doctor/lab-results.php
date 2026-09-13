<?php
$required_role = 'Doctor';
require_once '../includes/auth_check.php';
require_once '../config/db.php';


$stmt = $conn->prepare("SELECT doctor_id FROM doctor WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor_id = $stmt->get_result()->fetch_assoc()['doctor_id'];


$stmt = $conn->prepare("
    SELECT ltr.lab_request_id, ltr.test_type, ltr.requested_at,
           u.full_name AS patient_name, res.result_data, res.result_date
    FROM lab_test_request ltr
    JOIN appointment a ON ltr.appointment_id = a.appointment_id
    JOIN patient p ON ltr.patient_id = p.patient_id
    JOIN user u ON p.user_id = u.user_id
    LEFT JOIN lab_test_result res ON ltr.lab_request_id = res.lab_request_id
    WHERE a.doctor_id = ?
    ORDER BY ltr.requested_at DESC
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();


$all_rows = [];
$pending_count = 0;
$done_count = 0;

while ($row = $result->fetch_assoc()) {
   
    if ($row['result_data'] === null) {
        $row['req_status'] = 'Pending';
        $pending_count++;
    } else {
        $row['req_status'] = 'Done';
        $done_count++;
    }

    $all_rows[] = $row;
}

$total_count = count($all_rows);


function status_class($status) {
    if ($status === 'Done') {
        return 'badge-done';
    }
    return 'badge-pending';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Test Results - MediCore</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php require 'nav.php'; ?>
    <main class="page-content">
        <div class="page-header">
            <div>
                <h1>Lab Test Results</h1>
                <p class="subtitle">Track requested tests and view completed results</p>
            </div>
        </div>

        
        <div class="tab-bar" id="tabBar">
            <button class="tab-btn active" data-filter="All">All <span class="count"><?php echo $total_count; ?></span></button>
            <button class="tab-btn" data-filter="Pending">Pending <span class="count"><?php echo $pending_count; ?></span></button>
            <button class="tab-btn" data-filter="Done">Done <span class="count"><?php echo $done_count; ?></span></button>
        </div>

        <?php if ($total_count === 0): ?>
            <p class="empty-msg">No lab requests yet.</p>
        <?php else: ?>
        <table id="labTable">
            <tr>
                <th>Patient</th>
                <th>Test Name</th>
                <th>Status</th>
                <th>Request Date</th>
                <th>Result</th>
            </tr>
            <?php foreach ($all_rows as $row): ?>
            <tr data-status="<?php echo $row['req_status']; ?>">
                <td>
                    <div class="avatar-cell">
                        <div class="avatar-round"><?php echo strtoupper(substr($row['patient_name'], 0, 2)); ?></div>
                        <?php echo htmlspecialchars($row['patient_name']); ?>
                    </div>
                </td>
                <td><?php echo htmlspecialchars($row['test_type']); ?></td>
                <td>
                    <span class="badge <?php echo status_class($row['req_status']); ?>">
                        <?php echo $row['req_status']; ?>
                    </span>
                </td>
                <td><?php echo date('M j, Y', strtotime($row['requested_at'])); ?></td>
                <td>
                    <?php if ($row['result_data']): ?>
                        <?php echo nl2br(htmlspecialchars($row['result_data'])); ?>
                    <?php else: ?>
                        <span class="empty-msg">Awaiting result</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </main>
    </div><!-- /.main -->
    </div><!-- /.app-shell -->

    <script>
      
        document.querySelectorAll('.tab-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
               
                document.querySelectorAll('.tab-btn').forEach(function (b) {
                    b.classList.remove('active');
                });
                btn.classList.add('active');

                var filter = btn.dataset.filter;

               
                document.querySelectorAll('#labTable tr[data-status]').forEach(function (row) {
                    if (filter === 'All' || row.dataset.status === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>