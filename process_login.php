<?php
// Prevent any unwanted output
error_reporting(0);
ini_set('display_errors', 0);

// Start fresh output buffer
ob_start();
session_start();

require_once 'includes/db.php';

// Clear any previous output and set JSON header
ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['error' => 'Invalid request method']));
}

$role = $_POST['role'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

try {
    if ($role === 'student') {
        $check_email = "SELECT * FROM students WHERE s_email = ?";
        $stmt = $conn->prepare($check_email);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['error' => 'Invalid email or password']);
            exit();
        }

        $student = $result->fetch_assoc();
        if ($student['s_password'] !== $password) {
            echo json_encode(['error' => 'Invalid password']);
            exit();
        }

        $_SESSION['user_id'] = $student['s_id'];
        $_SESSION['user_type'] = 'student';
        echo json_encode(['success' => true, 'redirect' => 'student/dashboard.php']);
    } 
    elseif ($role === 'teacher') {
        $check_email = "SELECT * FROM teachers WHERE t_email = ?";
        $stmt = $conn->prepare($check_email);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['error' => 'Invalid email or password']);
            exit();
        }

        $teacher = $result->fetch_assoc();
        if ($teacher['t_password'] !== $password) {
            echo json_encode(['error' => 'Invalid password']);
            exit();
        }

        $_SESSION['user_id'] = $teacher['t_id'];
        $_SESSION['user_type'] = 'teacher';
        echo json_encode(['success' => true, 'redirect' => 'teacher/dashboard.php']);
    }
    elseif ($role === 'admin') {
        $check_username = "SELECT * FROM admins WHERE username = ?";
        $stmt = $conn->prepare($check_username);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['error' => 'Invalid username or password']);
            exit();
        }

        $admin = $result->fetch_assoc();
        if ($admin['password'] !== $password) {
            echo json_encode(['error' => 'Invalid password']);
            exit();
        }

        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_type'] = 'admin';
        echo json_encode(['success' => true, 'redirect' => 'admin/dashboard.php']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'System error occurred']);
}

// End output buffering and exit
ob_end_flush();
exit();
?>