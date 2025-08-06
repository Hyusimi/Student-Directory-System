<?php
session_start();
error_reporting(0); // Disable error reporting for production
ini_set('display_errors', 0); // Disable error display

header('Content-Type: application/json');
require_once '../includes/db.php';

try {
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception('Invalid request method');
    }

    if (!isset($_POST['username']) || !isset($_POST['password'])) {
        throw new Exception('Missing credentials');
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Admin login only
    $sql = "SELECT * FROM admins WHERE username = ?";  // Changed 'admin' to 'admins'
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        echo json_encode(['error' => 'Invalid username or password']);
        exit;
    }

    $user = $result->fetch_assoc();
    
    // Check if admin_id exists in the result
    if (!isset($user['admin_id'])) {  // Changed from 'id' to 'admin_id'
        throw new Exception('Invalid user data');
    }

    // For testing, use direct comparison. In production, use password_verify
    if ($password === $user['password']) {
        $_SESSION['user_id'] = $user['admin_id'];  // Changed from 'id' to 'admin_id'
        $_SESSION['user_type'] = 'admin';
        echo json_encode([
            'success' => true,
            'redirect' => '/Project/admin/dashboard.php'
        ]);
        exit;
    }

    echo json_encode(['error' => 'Invalid username or password']);

} catch (Exception $e) {
    error_log('Admin login error: ' . $e->getMessage());
    echo json_encode([
        'error' => 'Invalid username or password'  // Generic error for users
    ]);
    exit;
}
?>