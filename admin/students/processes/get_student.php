<?php
ini_set('display_errors', 0);
error_reporting(0);
session_start();
require_once __DIR__ . '/../../../includes/db.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => ''];

try {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid student ID');
    }

    $student_id = $_GET['id'];

    $sql = "SELECT s.s_id, s.s_fname, s.s_lname, s.s_mname, s.s_suffix, s.s_gender, 
            s.s_bdate, s.s_cnum, s.s_email, s.s_status, sd.degree_id, sd.degree_code
            FROM students s
            LEFT JOIN students_degrees sd ON s.s_id = sd.s_id
            WHERE s.s_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $student_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $response['status'] = 'success';
            $response['data'] = $result->fetch_assoc();
        } else {
            $response['message'] = 'Student not found';
        }
    } else {
        $response['message'] = 'Failed to fetch student data';
    }

    $stmt->close();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    if (isset($conn)) $conn->close();
}

echo json_encode($response);
?>