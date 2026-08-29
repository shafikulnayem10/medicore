<?php
session_start();
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $user_type = $_POST['user_type']; 

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

            $stmt = $conn->prepare("INSERT INTO user (full_name, email, password, user_type) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $full_name, $email, $hashed, $user_type);
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
                    $r = $conn->prepare("INSERT INTO receptionist (user_id, employee_code) VALUES (?, ?)");
                    $r->bind_param("is", $user_id, $employee_code);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MediCore - Register</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="auth-box">
        <h2>Create a MediCore Account</h2>

        <?php if ($error): ?>
            <p class="error-msg"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <label>Full Name</label>
            <input type="text" name="full_name" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Password</label>
            <input type="password" name="password" required minlength="6">

            <label>Register as</label>
            <select name="user_type" id="user_type" onchange="toggleFields()" required>
                <option value="">-- Select role --</option>
                <option value="Doctor">Doctor</option>
                <option value="Receptionist">Receptionist</option>
                <option value="Patient">Patient</option>
                <option value="Admin">Admin</option>
            </select>

            <!-- Doctor-only fields -->
            <div id="doctor-fields" class="role-fields" style="display:none;">
                <label>Specialization</label>
                <input type="text" name="specialization">
                <label>Qualification</label>
                <input type="text" name="qualification">
                <label>Years of Experience</label>
                <input type="number" name="experience" min="0">
            </div>

            <!-- Receptionist-only fields -->
            <div id="receptionist-fields" class="role-fields" style="display:none;">
                <label>Employee Code</label>
                <input type="text" name="employee_code">
            </div>

            <!-- Patient-only fields -->
            <div id="patient-fields" class="role-fields" style="display:none;">
                <label>Date of Birth</label>
                <input type="date" name="dob">
            </div>

            <button type="submit">Register</button>
        </form>

        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>

    <script>
        function toggleFields() {
            const role = document.getElementById('user_type').value;
            document.querySelectorAll('.role-fields').forEach(el => el.style.display = 'none');
            if (role === 'Doctor') document.getElementById('doctor-fields').style.display = 'block';
            if (role === 'Receptionist') document.getElementById('receptionist-fields').style.display = 'block';
            if (role === 'Patient') document.getElementById('patient-fields').style.display = 'block';
        }
    </script>
</body>
</html>
