<?php
require_once '../includes/db.php';
session_start();

// Set header to JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['error' => 'Please fill in all fields']);
        exit();
    }

    // Prepare SQL statement to prevent SQL injection
    $query = "SELECT * FROM teachers WHERE t_email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Invalid email or password']);
        exit();
    }

    $teacher = $result->fetch_assoc();
    
    // Verify password
    if ($teacher['t_password'] !== $password) {
        echo json_encode(['error' => 'Invalid email or password']);
        exit();
    }

    // Set session variables
    $_SESSION['user_id'] = $teacher['t_id'];
    $_SESSION['user_type'] = 'teacher';
    $_SESSION['name'] = $teacher['t_fname'] . ' ' . $teacher['t_lname'];

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => 'dashboard.php'
    ]);
    exit();
} else {
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}
?>
