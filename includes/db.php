<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'records');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$conn->set_charset("utf8mb4");

// Set wait_timeout and interactive_timeout
$conn->query("SET session wait_timeout=28800");
$conn->query("SET session interactive_timeout=28800");