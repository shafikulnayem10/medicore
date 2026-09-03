<?php
$required_role = 'Admin';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$error = '';
$success = '';

// ---------------- Handle Add Ward ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add') {
    $ward_name  = trim($_POST['ward_name']);
    $ward_type  = $_POST['ward_type'] ?? 'General';
    $total_beds = max(0, (int)($_POST['total_beds'] ?? 0));

    if ($ward_name === '' || $total_beds <= 0) {
        $error = "Ward name and a total bed count greater than 0 are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO wards (ward_name, ward_type, total_beds, occupied_beds) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("ssi", $ward_name, $ward_type, $total_beds);
        $stmt->execute();
        $success = "Ward added successfully.";
    }
}

// ---------------- Handle Edit Ward ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'edit') {
    $id         = (int)$_POST['id'];
    $ward_name  = trim($_POST['ward_name']);
    $ward_type  = $_POST['ward_type'] ?? 'General';
    $total_beds = max(0, (int)($_POST['total_beds'] ?? 0));

    // Don't let total_beds drop below beds already occupied
    $current = $conn->query("SELECT occupied_beds FROM wards WHERE id = " . $id)->fetch_assoc();
    if ($current && $total_beds < (int)$current['occupied_beds']) {
        $error = "Total beds can't be less than the " . $current['occupied_beds'] . " beds already occupied.";
    } else {
        $stmt = $conn->prepare("UPDATE wards SET ward_name=?, ward_type=?, total_beds=? WHERE id=?");
        $stmt->bind_param("ssii", $ward_name, $ward_type, $total_beds, $id);
        $stmt->execute();
        $success = "Ward updated successfully.";
    }
}

// ---------------- Handle Delete Ward ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'delete') {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM wards WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $success = "Ward removed.";
}

// ---------------- Handle Assign / Release bed ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['form_action'] ?? '', ['assign', 'release'])) {
    $id = (int)$_POST['id'];
    $ward = $conn->query("SELECT total_beds, occupied_beds FROM wards WHERE id = $id")->fetch_assoc();

    if (!$ward) {
        $error = "Ward not found.";
    } elseif ($_POST['form_action'] === 'assign') {
        if ((int)$ward['occupied_beds'] < (int)$ward['total_beds']) {
            $conn->query("UPDATE wards SET occupied_beds = occupied_beds + 1 WHERE id = $id");
            $success = "Bed assigned.";
        } else {
            $error = "No available beds in this ward.";
        }
    } else { // release
        if ((int)$ward['occupied_beds'] > 0) {
            $conn->query("UPDATE wards SET occupied_beds = occupied_beds - 1 WHERE id = $id");
            $success = "Bed released.";
        } else {
            $error = "No occupied beds to release in this ward.";
        }
    }
}

// ---------------- Fetch wards ----------------
$wards = $conn->query("SELECT * FROM wards ORDER BY id")->fetch_all(MYSQLI_ASSOC);

$page_title = 'Ward & Bed Management';
$active_nav = 'wards';
require_once '../includes/admin_header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>Ward & Bed Management</h1>
        <p>Manage hospital ward inventory and bed assignments</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addWardModal')">+ Add New Ward</button>
</div>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="ward-grid">
    <?php if (!$wards): ?>
        <p>No wards yet. Click "+ Add New Ward" to create one (e.g. "Cardiac ICU", type ICU, 12 total beds).</p>
    <?php endif; ?>
    <?php foreach ($wards as $ward):
        $total = (int)$ward['total_beds'];
        $occupied = (int)$ward['occupied_beds'];
        $available = $total - $occupied;
        $pct = $total > 0 ? round(($occupied / $total) * 100) : 0;
        $type_class = strtolower($ward['ward_type']);
    ?>
    <div class="card ward-card"
         id="row-ward-<?php echo $ward['id']; ?>"
         data-id="<?php echo $ward['id']; ?>"
         data-ward_name="<?php echo htmlspecialchars($ward['ward_name']); ?>"
         data-ward_type="<?php echo htmlspecialchars($ward['ward_type']); ?>"
         data-total_beds="<?php echo $total; ?>">
        <div class="ward-top">
            <h3><?php echo htmlspecialchars($ward['ward_name']); ?></h3>
            <span class="badge <?php echo $type_class; ?>"><?php echo htmlspecialchars($ward['ward_type']); ?></span>
        </div>

        <div class="ward-stats">
            <div>Available<strong class="available-num <?php echo $available <= 0 ? 'zero' : ''; ?>"><?php echo $available; ?></strong></div>
            <div style="text-align:right;">Total<strong><?php echo $total; ?></strong></div>
        </div>

        <div class="progress-track">
            <div class="progress-fill <?php echo $available <= 0 ? 'full' : ''; ?>" style="width:<?php echo $pct; ?>%"></div>
        </div>
        <div class="occupied-note"><?php echo $occupied; ?>/<?php echo $total; ?> occupied</div>

        <?php if ($available <= 0): ?>
            <div class="no-beds-note">No beds available</div>
        <?php endif; ?>

        <div class="ward-actions">
            <form method="POST">
                <input type="hidden" name="form_action" value="assign">
                <input type="hidden" name="id" value="<?php echo $ward['id']; ?>">
                <button type="submit" class="btn btn-primary" <?php echo $available <= 0 ? 'disabled' : ''; ?>>Assign Bed</button>
            </form>
            <form method="POST">
                <input type="hidden" name="form_action" value="release">
                <input type="hidden" name="id" value="<?php echo $ward['id']; ?>">
                <button type="submit" class="btn btn-outline" <?php echo $occupied <= 0 ? 'disabled' : ''; ?>>Release Bed</button>
            </form>
        </div>

        <div class="ward-actions" style="margin-top:8px;">
            <button type="button" class="btn btn-outline btn-sm" onclick="fillEditForm(this, 'edit_ward'); openModal('editWardModal')">✎ Edit Ward</button>
            <form method="POST" onsubmit="return confirmDelete(this, 'Delete ward &quot;<?php echo htmlspecialchars(addslashes($ward['ward_name'])); ?>&quot;? This cannot be undone.');">
                <input type="hidden" name="form_action" value="delete">
                <input type="hidden" name="id" value="<?php echo $ward['id']; ?>">
                <button type="submit" class="btn btn-outline btn-sm" style="color:#e0524a;" title="Delete ward">🗑 Delete</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Add Ward Modal -->
<div class="modal-overlay" id="addWardModal">
    <div class="modal-box">
        <h2>Add New Ward</h2>
        <form method="POST">
            <input type="hidden" name="form_action" value="add">
            <div class="form-group"><label>Ward Name</label><input type="text" name="ward_name" placeholder="e.g. Cardiac ICU" required></div>
            <div class="form-group"><label>Ward Type</label>
                <select name="ward_type">
                    <option value="General">General</option>
                    <option value="ICU">ICU</option>
                    <option value="Cabin">Cabin</option>
                </select>
            </div>
            <div class="form-group"><label>Total Beds</label><input type="number" name="total_beds" min="1" required></div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('addWardModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Ward</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Ward Modal -->
<div class="modal-overlay" id="editWardModal">
    <div class="modal-box">
        <h2>Edit Ward</h2>
        <form method="POST">
            <input type="hidden" name="form_action" value="edit">
            <input type="hidden" id="edit_ward_id" name="id">
            <div class="form-group"><label>Ward Name</label><input type="text" id="edit_ward_ward_name" name="ward_name" required></div>
            <div class="form-group"><label>Ward Type</label>
                <select id="edit_ward_ward_type" name="ward_type">
                    <option value="General">General</option>
                    <option value="ICU">ICU</option>
                    <option value="Cabin">Cabin</option>
                </select>
            </div>
            <div class="form-group"><label>Total Beds</label><input type="number" id="edit_ward_total_beds" name="total_beds" min="0" required></div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('editWardModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
