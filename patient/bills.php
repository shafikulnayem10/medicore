<?php
$required_role = 'Patient';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$stmt = $conn->prepare("SELECT patient_id FROM patient WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient_id = $stmt->get_result()->fetch_assoc()['patient_id'];

$bills_stmt = $conn->prepare("
    SELECT billing_id, amount, services, payment_status, generated_at
    FROM billing
    WHERE patient_id = ?
    ORDER BY generated_at DESC
");
$bills_stmt->bind_param("i", $patient_id);
$bills_stmt->execute();
$bills = $bills_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_count = count($bills);
$paid_count = 0;
$unpaid_count = 0;
$outstanding = 0;
foreach ($bills as $b) {
    if ($b['payment_status'] === 'Paid') {
        $paid_count++;
    } else {
        $unpaid_count++;
        $outstanding += $b['amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bills - MediCore</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php require 'nav.php'; ?>
    <main class="page-content">
        <div class="page-header">
            <div>
                <h1>My Bills</h1>
                <p class="subtitle">View your hospital invoices</p>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-card">
                <div class="label">Total Bills</div>
                <div class="value"><?php echo $total_count; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Paid</div>
                <div class="value"><?php echo $paid_count; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Unpaid</div>
                <div class="value"><?php echo $unpaid_count; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Outstanding Balance</div>
                <div class="value">Tk <?php echo number_format($outstanding, 2); ?></div>
            </div>
        </div>

        <?php if (count($bills) === 0): ?>
            <p class="empty-msg">No bills yet.</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Bill ID</th>
                <th>Services</th>
                <th>Date</th>
                <th>Total Amount</th>
                <th>Payment Status</th>
            </tr>
            <?php foreach ($bills as $b): ?>
            <tr>
                <td>INV-<?php echo str_pad($b['billing_id'], 4, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo htmlspecialchars($b['services'] ?: '—'); ?></td>
                <td><?php echo date('M d, Y', strtotime($b['generated_at'])); ?></td>
                <td>Tk <?php echo number_format($b['amount'], 2); ?></td>
                <td><span class="badge <?php echo $b['payment_status'] === 'Paid' ? 'badge-confirmed' : 'badge-cancelled'; ?>"><?php echo htmlspecialchars($b['payment_status']); ?></span></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </main>
    </div>
    </div>
</body>
</html>