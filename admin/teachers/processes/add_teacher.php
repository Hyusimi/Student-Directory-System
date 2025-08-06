<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../includes/db.php';
    
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    // Authorization check
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    // Validate required fields
    $required_fields = ['t_fname', 't_lname', 't_gender', 't_bdate', 't_cnum', 't_email', 't_password', 't_department'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("$field is required");
        }
    }

    // Validate email
    $email = filter_var($_POST['t_email'], FILTER_VALIDATE_EMAIL);
    if ($email === false) {
        throw new Exception('Invalid email format');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check if email already exists
        $check = $conn->prepare("SELECT t_id FROM teachers WHERE t_email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            throw new Exception('Email already exists');
        }

        // Calculate age
        $birthDate = new DateTime($_POST['t_bdate']);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;

        // Insert teacher
        $stmt = $conn->prepare("INSERT INTO teachers (t_fname, t_lname, t_mname, t_suffix, t_gender, t_bdate, t_age, t_cnum, t_email, t_password, t_department, t_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $fname = $_POST['t_fname'];
        $lname = $_POST['t_lname'];
        $mname = $_POST['t_mname'] ?? '';
        $suffix = $_POST['t_suffix'] ?? '';
        $gender = $_POST['t_gender'];
        $bdate = $_POST['t_bdate'];
        $cnum = $_POST['t_cnum'];
        $password = $_POST['t_password'];
        $department = $_POST['t_department'];
        $status = $_POST['t_status'] ?? 'active';

        $stmt->bind_param("ssssssisssss", 
            $fname, $lname, $mname, $suffix, $gender, $bdate, 
            $age, $cnum, $email, $password, $department, $status
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to add teacher');
        }

        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Teacher added successfully',
            'data' => [
                't_id' => $conn->insert_id,
                't_fname' => $fname,
                't_lname' => $lname,
                't_mname' => $mname,
                't_suffix' => $suffix,
                't_gender' => $gender,
                't_bdate' => $bdate,
                't_age' => $age,
                't_cnum' => $cnum,
                't_email' => $email,
                't_password' => $password,
                't_department' => $department,
                't_status' => $status
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
