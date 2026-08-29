<?php
session_start();
require_once 'config/db.php';

$error = '';


if (isset($_SESSION['user_id'])) {
    header("Location: " . strtolower($_SESSION['user_type']) . "/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, full_name, password, user_type FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_type'] = $user['user_type'];

           
            $log = $conn->prepare("INSERT INTO authentication (user_id, login_time) VALUES (?, NOW())");
            $log->bind_param("i", $user['user_id']);
            $log->execute();
            $_SESSION['auth_id'] = $log->insert_id;

            // Redirect based on role 
            $redirect_map = [
                'Doctor'       => 'doctor/dashboard.php',
                'Receptionist' => 'receptionist/dashboard.php',
                'Patient'      => 'patient/dashboard.php',
                'Admin'        => 'admin/dashboard.php',
            ];
            header("Location: " . $redirect_map[$user['user_type']]);
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No account found with that email.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MediCore - Login</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="auth-box">
        <h2>MediCore Login</h2>

        <?php if ($error): ?>
            <p class="error-msg"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
            <p class="error-msg">You are not authorized to view that page.</p>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
            <p class="success-msg">Account created. Please log in.</p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label>Email</label>
            <input type="email" name="email" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit">Login</button>
        </form>

        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>
