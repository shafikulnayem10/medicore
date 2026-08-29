<?php
session_start();
require_once 'config/db.php';


if (isset($_SESSION['auth_id'])) {
    $stmt = $conn->prepare("UPDATE authentication SET logout_time = NOW() WHERE auth_id = ?");
    $stmt->bind_param("i", $_SESSION['auth_id']);
    $stmt->execute();
}

$_SESSION = [];
session_destroy();

header("Location: login.php");
exit();
