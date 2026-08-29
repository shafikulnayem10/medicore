<?php
// Copy this file to config/db.php 
// Default XAMPP settings: host=localhost, user=root, password="" (empty)

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'medicore');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}


date_default_timezone_set('Asia/Dhaka');
