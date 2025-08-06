<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized access']));
}

require_once __DIR__ . '/../../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $t_id = $_POST['t_id'];
    $t_fname = $_POST['t_fname'];
    $t_lname = $_POST['t_lname'];
    $t_mname = $_POST['t_mname'];
    $t_suffix = $_POST['t_suffix'];
    $t_gender = $_POST['t_gender'];
    $t_bdate = $_POST['t_bdate'];
    $t_cnum = $_POST['t_cnum'];
    $t_email = $_POST['t_email'];
    $t_department = $_POST['t_department'] ?? null;
    $t_status = $_POST['t_status'];

    // Calculate age based on birthdate
    $birthDate = new DateTime($t_bdate);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;

    try {
        // Prepare update statement
        $stmt = $conn->prepare("UPDATE teachers SET 
            t_fname = ?, t_lname = ?, t_mname = NULLIF(?, ''), 
            t_suffix = NULLIF(?, ''), t_gender = ?, t_bdate = ?, 
            t_cnum = ?, t_email = NULLIF(?, ''), t_department = ?, t_status = ? 
            WHERE t_id = ?");
        
        $stmt->bind_param("ssssssssssi", 
            $t_fname, $t_lname, $t_mname, $t_suffix, $t_gender, $t_bdate,
            $t_cnum, $t_email, $t_department, $t_status, $t_id
        );

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Teacher updated successfully',
                'data' => [
                    't_id' => $t_id,
                    't_fname' => $t_fname,
                    't_lname' => $t_lname,
                    't_mname' => $t_mname,
                    't_suffix' => $t_suffix,
                    't_gender' => $t_gender,
                    't_bdate' => $t_bdate,
                    't_cnum' => $t_cnum,
                    't_email' => $t_email,
                    't_department' => $t_department,
                    't_status' => $t_status
                ]
            ]);
        } else {
            throw new Exception('Failed to update teacher');
        }

    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

    $stmt->close();
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);