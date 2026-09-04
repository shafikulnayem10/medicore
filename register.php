<?php
session_start();
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'];
    $user_type = $_POST['user_type'];

    if ($phone !== '' && !preg_match('/^[0-9]{11}$/', $phone)) {
        $error = "Phone number must be exactly 11 digits.";
    }

    if ($error === '') {
        // Check email
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

                $stmt = $conn->prepare("INSERT INTO user (full_name, email, phone, password, user_type) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $full_name, $email, $phone, $hashed, $user_type);
                $stmt->execute();
                $user_id = $stmt->insert_id;

                // Insert into the role-specific table
                switch ($user_type) {
                    case 'Doctor':
                        $specialization = trim($_POST['specialization'] ?? '');
                        $qualification  = trim($_POST['qualification'] ?? '');
                        $experience     = (int)($_POST['experience'] ?? 0);
                        $r = $conn->prepare("INSERT INTO doctor (user_id, specialization, qualification, experience) VALUES (?, ?, ?, ?)");
                        $r->bind_param("issi", $user_id, $specialization, $qualification, $experience);
                        $r->execute();
                        break;

                    case 'Receptionist':
                        $employee_code = trim($_POST['employee_code'] ?? '');
                        $shift         = trim($_POST['shift'] ?? '');
                        $r = $conn->prepare("INSERT INTO receptionist (user_id, employee_code, shift) VALUES (?, ?, ?)");
                        $r->bind_param("iss", $user_id, $employee_code, $shift);
                        $r->execute();
                        break;

                    case 'Patient':
                        $dob = $_POST['dob'] ?? null;
                        $r = $conn->prepare("INSERT INTO patient (user_id, dob) VALUES (?, ?)");
                        $r->bind_param("is", $user_id, $dob);
                        $r->execute();
                        break;

                    case 'Admin':
                        $r = $conn->prepare("INSERT INTO admin (user_id) VALUES (?)");
                        $r->bind_param("i", $user_id);
                        $r->execute();
                        break;
                }

                $conn->commit();
                header("Location: login.php?registered=1");
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MediCore - Register</title>
    <!-- <link rel="stylesheet" href="assets/css/auth.css"> -->
     <link rel="stylesheet" href="assets/css/auth.css?v=2">
</head>
<body>
    <div class="auth-box">
        <div class="auth-brand">
            <div class="logo-circle">🩺</div>
            <h1>MediCore</h1>
            <p>Hospital Management System</p>
        </div>

        <h2>Create your account</h2>

        <?php if ($error): ?>
            <p class="error-msg"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div>
                <label>Full Name</label>
                <input type="text" name="full_name" required>
            </div>

            <div>
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div>
                <label>Phone</label>
                <input type="tel" name="phone" placeholder="e.g. 01712345678" pattern="[0-9]{11}" maxlength="11" inputmode="numeric" title="Phone number must be exactly 11 digits" required>
            </div>

            <div>
                <label>Password</label>
                <input type="password" name="password" required minlength="6">
            </div>

            <div>
                <label>Register as</label>
                <select name="user_type" id="user_type" onchange="toggleFields()" required>
                    <option value="">-- Select role --</option>
                    <option value="Doctor">Doctor</option>
                    <option value="Receptionist">Receptionist</option>
                    <option value="Patient">Patient</option>
                    <option value="Admin">Admin</option>
                </select>
            </div>

            <!-- Doctor-only fields -->
            <div id="doctor-fields" class="role-fields" style="display:none;">
                <div>
                    <label>Specialization</label>
                    <input type="text" name="specialization">
                </div>
                <div>
                    <label>Qualification</label>
                    <input type="text" name="qualification">
                </div>
                <div>
                    <label>Years of Experience</label>
                    <input type="number" name="experience" min="0">
                </div>
            </div>

            <!-- Receptionist-only fields -->
            <div id="receptionist-fields" class="role-fields" style="display:none;">
                <div>
                    <label>Employee Code</label>
                    <input type="text" name="employee_code">
                </div>
                <div>
                    <label>Shift</label>
                    <select name="shift">
                        <option value="">-- Select Shift --</option>
                        <option value="Morning">Morning</option>
                        <option value="Evening">Evening</option>
                        <option value="Night">Night</option>
                    </select>
                </div>
            </div>

            <!-- Patient-only fields -->
            <div id="patient-fields" class="role-fields" style="display:none;">
                <div>
                    <label>Date of Birth</label>
                    <input type="date" name="dob">
                </div>
            </div>

            <button type="submit">Create Account</button>
        </form>

        <p class="switch-link">Already have an account? <a href="login.php">Login here</a></p>
    </div>

    <script>
        function toggleFields() {
            const role = document.getElementById('user_type').value;
            document.querySelectorAll('.role-fields').forEach(el => el.style.display = 'none');
            if (role === 'Doctor') document.getElementById('doctor-fields').style.display = 'flex';
            if (role === 'Receptionist') document.getElementById('receptionist-fields').style.display = 'flex';
            if (role === 'Patient') document.getElementById('patient-fields').style.display = 'flex';
        }
    </script>
</body>
</html>
