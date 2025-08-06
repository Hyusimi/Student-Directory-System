<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../includes/db.php';
    
    if (session_status() === PHP_SESSION_NONE) session_start();

    // Check if it's an AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    $response = ['success' => false, 'message' => ''];

    // Authorization check
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    // Method check
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validation
    if (empty($_POST['s_id'])) {
        throw new Exception('Student ID is required');
    }

    $id = filter_var($_POST['s_id'], FILTER_VALIDATE_INT);
    $email = !empty($_POST['s_email']) ? filter_var($_POST['s_email'], FILTER_VALIDATE_EMAIL) : null;

    if ($id === false) {
        throw new Exception('Invalid student ID');
    }
    if ($email === false && !empty($_POST['s_email'])) {
        throw new Exception('Invalid email format');
    }

    // Database operations
    $conn->begin_transaction();

    // Clear existing data
    $stmt1 = $conn->prepare("UPDATE students_degrees SET s_fname = NULL, s_lname = NULL, s_mname = NULL, s_gender = NULL WHERE s_id = ?");
    $stmt1->bind_param("i", $id);
    $stmt1->execute();
    $stmt1->close();

    // Prepare variables
    $fname = $_POST['s_fname'];
    $lname = $_POST['s_lname'];
    $mname = $_POST['s_mname'] ?? '';
    $suffix = $_POST['s_suffix'] ?? '';
    $gender = $_POST['s_gender'];
    $bdate = $_POST['s_bdate'];
    $cnum = $_POST['s_cnum'] ?? '';
    $status = $_POST['s_status'];
    $degree_id = $_POST['degree_id'];

    // Get degree information
    $degree_query = $conn->prepare("SELECT degree_code FROM degrees WHERE degree_id = ?");
    $degree_query->bind_param("i", $degree_id);
    $degree_query->execute();
    $degree_result = $degree_query->get_result();
    $degree_data = $degree_result->fetch_assoc();
    $degree_code = $degree_data['degree_code'];
    $degree_query->close();

    // Update main student record
    $stmt2 = $conn->prepare("UPDATE students SET 
        s_fname = ?, s_lname = ?, s_mname = NULLIF(?, ''), 
        s_suffix = NULLIF(?, ''), s_gender = ?, s_bdate = ?, 
        s_cnum = NULLIF(?, ''), s_email = NULLIF(?, ''), s_status = ? 
        WHERE s_id = ?");

    $stmt2->bind_param("sssssssssi", 
        $fname, $lname, $mname, $suffix, $gender, $bdate,
        $cnum, $email, $status, $id
    );
    $stmt2->execute();
    $stmt2->close();

    // Update degrees record
    $stmt3 = $conn->prepare("UPDATE students_degrees SET 
        s_fname = ?, s_lname = ?, s_mname = NULLIF(?, ''), s_gender = ?,
        degree_id = ?, degree_code = ?
        WHERE s_id = ?");

    $stmt3->bind_param("ssssssi", $fname, $lname, $mname, $gender, $degree_id, $degree_code, $id);
    $stmt3->execute();
    $stmt3->close();

    $conn->commit();
    
    $response = [
        'success' => true,
        'message' => 'Student updated successfully',
        'title' => 'Success!',
        'data' => [
            's_id' => $id,
            's_fname' => $fname,
            's_lname' => $lname,
            's_mname' => $mname,
            's_suffix' => $suffix,
            's_gender' => $gender,
            's_bdate' => $bdate,
            's_cnum' => $cnum,
            's_email' => $email,
            's_status' => $status,
            'degree_id' => $degree_id,
            'degree_code' => $degree_code
        ]
    ];
    
    echo json_encode($response);
    exit;

} catch (Throwable $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    $response = [
        'success' => false, 
        'message' => $e->getMessage(),
        'title' => 'Error!' // Add title for modal
    ];
    http_response_code(500);
    echo json_encode($response);
    exit;
}
