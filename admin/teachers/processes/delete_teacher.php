<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../includes/db.php';
    
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    // Authorization check
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    // Validate teacher ID
    if (empty($_POST['t_id'])) {
        throw new Exception('Teacher ID is required');
    }

    $teacher_id = filter_var($_POST['t_id'], FILTER_VALIDATE_INT);
    if ($teacher_id === false) {
        throw new Exception('Invalid teacher ID format');
    }

    // Check if teacher exists
    $check = $conn->prepare("SELECT t_id FROM teachers WHERE t_id = ?");
    if (!$check) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $check->bind_param("i", $teacher_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Teacher not found');
    }
    $check->close();

    // Delete from teachers
    $stmt = $conn->prepare("DELETE FROM teachers WHERE t_id = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $teacher_id);
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete teacher');
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('No teacher was deleted');
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Teacher deleted successfully'
    ]);
    exit;

} catch (Throwable $e) {
    // Log error for debugging
    error_log("Delete teacher error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
} finally {
    // Clean up
    if (isset($check) && $check instanceof mysqli_stmt) {
        $check->close();
    }
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
