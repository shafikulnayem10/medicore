<?php
$required_role = 'Admin';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$error = '';
$success = '';

function avatar_color($seed) {
    $palette = ['#e0524a', '#f6a83c', '#c74e7e', '#1a9c76', '#2f6fb5', '#7a54c7'];
    return $palette[$seed % count($palette)];
}
function initials_of($name) {
    $parts = preg_split('/\s+/', trim($name));
    $first = $parts[0][0] ?? '';
    $last  = count($parts) > 1 ? $parts[count($parts) - 1][0] : '';
    return strtoupper($first . $last);
}

// All fields required + phone must be exactly 11 digits + email must be a valid address
function validate_receptionist_input($name, $employee_code, $phone, $email, $gender, $shift, $status) {
    if ($name === '' || $employee_code === '' || $phone === '' || $email === '' || $gender === '' || $shift === '' || $status === '') {
        return "All fields are required.";
    }
    if (!preg_match('/^[0-9]{11}$/', $phone)) {
        return "Phone number must be exactly 11 digits.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Please enter a valid email address.";
    }
    return '';
}

// ---------------- Handle Add ----------------
// Adding a receptionist here creates a real login account (user + receptionist
// rows), the same way register.php does, so your friend's Receptionist-login
// system and this admin page always show the exact same people.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add') {
    $full_name     = trim($_POST['full_name']);
    $email         = trim($_POST['email']);
    $phone         = trim($_POST['phone']);
    $password      = $_POST['password'] ?? '';
    $employee_code = trim($_POST['employee_code']);
    $gender        = $_POST['gender'] ?? '';
    $shift         = $_POST['shift'] ?? '';
    $status        = $_POST['status'] ?? '';

    $error = validate_receptionist_input($full_name, $employee_code, $phone, $email, $gender, $shift, $status);
    if ($error === '' && strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    }

    if ($error === '') {
        $check = $conn->prepare("SELECT user_id FROM user WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            $conn->begin_transaction();
            try {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO user (full_name, email, phone, password, user_type) VALUES (?, ?, ?, ?, 'Receptionist')");
                $stmt->bind_param("ssss", $full_name, $email, $phone, $hashed);
                $stmt->execute();
                $user_id = $stmt->insert_id;

                $r = $conn->prepare("INSERT INTO receptionist (user_id, employee_code, gender, shift, status) VALUES (?, ?, ?, ?, ?)");
                $r->bind_param("issss", $user_id, $employee_code, $gender, $shift, $status);
                $r->execute();

                $conn->commit();
                $success = "Receptionist added successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Could not add receptionist. Please try again.";
            }
        }
    }
}

// ---------------- Handle Edit ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'edit') {
    $receptionist_id = (int)$_POST['receptionist_id'];
    $user_id         = (int)$_POST['user_id'];
    $full_name       = trim($_POST['full_name']);
    $email           = trim($_POST['email']);
    $phone           = trim($_POST['phone']);
    $employee_code   = trim($_POST['employee_code']);
    $gender          = $_POST['gender'] ?? '';
    $shift           = $_POST['shift'] ?? '';
    $status          = $_POST['status'] ?? '';

    $error = validate_receptionist_input($full_name, $employee_code, $phone, $email, $gender, $shift, $status);
    if ($error === '') {
        $stmt = $conn->prepare("UPDATE user SET full_name=?, email=?, phone=? WHERE user_id=?");
        $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
        $stmt->execute();

        $r = $conn->prepare("UPDATE receptionist SET employee_code=?, gender=?, shift=?, status=? WHERE receptionist_id=?");
        $r->bind_param("ssssi", $employee_code, $gender, $shift, $status, $receptionist_id);
        $r->execute();

        $success = "Receptionist updated successfully.";
    }
}

// ---------------- Handle Delete ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'delete') {
    $user_id = (int)$_POST['user_id'];
    $stmt = $conn->prepare("DELETE FROM user WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $success = "Receptionist removed.";
}

// ---------------- Fetch list ----------------
$receptionists = $conn->query("
    SELECT r.receptionist_id, r.employee_code, r.gender, r.shift, r.status,
           u.user_id, u.full_name, u.email, u.phone, u.created_at
    FROM receptionist r
    JOIN user u ON u.user_id = r.user_id
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$page_title = 'Manage Receptionists';
$active_nav = 'receptionists';
require_once '../includes/admin_header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>Manage Receptionists</h1>
        <p><?php echo count($receptionists); ?> receptionists on staff</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addRecModal')">+ Add New Receptionist</button>
</div>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="card table-card" style="overflow-x:auto;">
    <div class="table-toolbar">
        <input type="text" class="search-input" placeholder="Search by name..."
               oninput="filterTable(this, 'recTbody')">
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Employee Code</th>
                <th>Shift</th>
                <th>Status</th>
                <th>Date Added</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="recTbody">
            <?php if (!$receptionists): ?>
                <tr class="empty-row"><td colspan="8">No receptionists yet. Click "+ Add New Receptionist" to add one, or wait for one to register.</td></tr>
            <?php endif; ?>
            <?php foreach ($receptionists as $rec): ?>
                <tr id="row-receptionist-<?php echo $rec['receptionist_id']; ?>"
                    data-id="<?php echo $rec['receptionist_id']; ?>"
                    data-receptionist_id="<?php echo $rec['receptionist_id']; ?>"
                    data-user_id="<?php echo $rec['user_id']; ?>"
                    data-full_name="<?php echo htmlspecialchars($rec['full_name']); ?>"
                    data-email="<?php echo htmlspecialchars($rec['email']); ?>"
                    data-phone="<?php echo htmlspecialchars($rec['phone']); ?>"
                    data-employee_code="<?php echo htmlspecialchars($rec['employee_code']); ?>"
                    data-gender="<?php echo htmlspecialchars($rec['gender']); ?>"
                    data-shift="<?php echo htmlspecialchars($rec['shift']); ?>"
                    data-status="<?php echo htmlspecialchars($rec['status']); ?>">
                    <td>
                        <div class="person-cell">
                            <div class="avatar-sm" style="background:<?php echo avatar_color($rec['receptionist_id']); ?>">
                                <?php echo initials_of($rec['full_name']); ?>
                            </div>
                            <div>
                                <div class="p-name"><?php echo htmlspecialchars($rec['full_name']); ?></div>
                                <div class="p-code">RC-<?php echo str_pad($rec['receptionist_id'], 3, '0', STR_PAD_LEFT); ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($rec['email']); ?></td>
                    <td><?php echo htmlspecialchars($rec['phone'] ?: '—'); ?></td>
                    <td><?php echo htmlspecialchars($rec['employee_code'] ?: '—'); ?></td>
                    <td><?php echo htmlspecialchars($rec['shift'] ?: '—'); ?></td>
                    <td><span class="badge <?php echo $rec['status'] === 'Active' ? 'general' : 'icu'; ?>"><?php echo htmlspecialchars($rec['status'] ?: 'Active'); ?></span></td>
                    <td><?php echo date('M j, Y', strtotime($rec['created_at'])); ?></td>
                    <td>
                        <button class="icon-btn edit" title="Edit" onclick="fillEditForm(this, 'edit_rec'); openModal('editRecModal')">✎</button>
                        <form method="POST" style="display:inline" onsubmit="return confirmDelete(this, 'Remove <?php echo htmlspecialchars(addslashes($rec['full_name'])); ?>? This deletes their login account too. This cannot be undone.');">
                            <input type="hidden" name="form_action" value="delete">
                            <input type="hidden" name="user_id" value="<?php echo $rec['user_id']; ?>">
                            <button type="submit" class="icon-btn delete" title="Delete">🗑</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Receptionist Modal -->
<div class="modal-overlay" id="addRecModal">
    <div class="modal-box">
        <h2>Add New Receptionist</h2>
        <p style="font-size:13px; color:var(--text-muted); margin-top:-8px;">This creates a real login account for the receptionist.</p>
        <form method="POST">
            <input type="hidden" name="form_action" value="add">
            <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" required placeholder="name@example.com"></div>
            <div class="form-group"><label>Phone</label>
                <input type="tel" name="phone" required pattern="[0-9]{11}" maxlength="11" inputmode="numeric" placeholder="e.g. 01712345678" title="Phone number must be exactly 11 digits">
            </div>
            <div class="form-group"><label>Login Password</label><input type="password" name="password" minlength="6" required></div>
            <div class="form-group"><label>Employee Code</label><input type="text" name="employee_code" placeholder="e.g. REC-005" required></div>
            <div class="form-group"><label>Gender</label>
                <select name="gender" required>
                    <option value="">-- Select --</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group"><label>Shift</label>
                <select name="shift" required>
                    <option value="">-- Select --</option>
                    <option value="Morning">Morning</option>
                    <option value="Evening">Evening</option>
                    <option value="Night">Night</option>
                </select>
            </div>
            <div class="form-group"><label>Status</label>
                <select name="status" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('addRecModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Receptionist</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Receptionist Modal -->
<div class="modal-overlay" id="editRecModal">
    <div class="modal-box">
        <h2>Edit Receptionist</h2>
        <form method="POST">
            <input type="hidden" name="form_action" value="edit">
            <input type="hidden" id="edit_rec_receptionist_id" name="receptionist_id">
            <input type="hidden" id="edit_rec_user_id" name="user_id">
            <div class="form-group"><label>Full Name</label><input type="text" id="edit_rec_full_name" name="full_name" required></div>
            <div class="form-group"><label>Email</label><input type="email" id="edit_rec_email" name="email" required></div>
            <div class="form-group"><label>Phone</label>
                <input type="tel" id="edit_rec_phone" name="phone" required pattern="[0-9]{11}" maxlength="11" inputmode="numeric" title="Phone number must be exactly 11 digits">
            </div>
            <div class="form-group"><label>Employee Code</label><input type="text" id="edit_rec_employee_code" name="employee_code" required></div>
            <div class="form-group"><label>Gender</label>
                <select id="edit_rec_gender" name="gender" required>
                    <option value="">-- Select --</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group"><label>Shift</label>
                <select id="edit_rec_shift" name="shift" required>
                    <option value="">-- Select --</option>
                    <option value="Morning">Morning</option>
                    <option value="Evening">Evening</option>
                    <option value="Night">Night</option>
                </select>
            </div>
            <div class="form-group"><label>Status</label>
                <select id="edit_rec_status" name="status" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('editRecModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
