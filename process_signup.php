<?php
header('Content-Type: application/json');
require_once 'includes/db.php';

function ucwordsWithHyphen($str) {
    if (!$str) return null;
    return implode('-', array_map('ucfirst', explode('-', ucwords($str))));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'];
    
    // Capitalize names before processing
    if ($role === 'student') {
        $_POST['s_fname'] = ucwordsWithHyphen(trim($_POST['s_fname']));
        $_POST['s_lname'] = ucwordsWithHyphen(trim($_POST['s_lname']));
        $_POST['s_mname'] = ucwordsWithHyphen(trim($_POST['s_mname']));
        $_POST['s_suffix'] = ucwordsWithHyphen(trim($_POST['s_suffix']));
    } else {
        $_POST['t_fname'] = ucwordsWithHyphen(trim($_POST['t_fname']));
        $_POST['t_lname'] = ucwordsWithHyphen(trim($_POST['t_lname']));
        $_POST['t_mname'] = ucwordsWithHyphen(trim($_POST['t_mname']));
        $_POST['t_suffix'] = ucwordsWithHyphen(trim($_POST['t_suffix']));
    }
    
    if ($_POST[$role[0] . '_password'] !== $_POST['confirm_password']) {
        die(json_encode(['error' => 'Passwords do not match']));
    }

    try {
        if ($role === 'student') {
            // Start transaction
            $conn->begin_transaction();

            // Store all values in variables before binding
            $fname = $_POST['s_fname'];
            $lname = $_POST['s_lname'];
            $mname = empty($_POST['s_mname']) ? null : $_POST['s_mname'];
            $suffix = empty($_POST['s_suffix']) ? null : $_POST['s_suffix'];
            $gender = $_POST['s_gender'];
            $bdate = $_POST['s_bdate'];
            $cnum = $_POST['s_cnum'];
            $email = $_POST['s_email'];
            $password = $_POST['s_password']; // Remove hashing
            $degree_id = $_POST['s_degree'];

            // Validate degree_id exists
            $check_degree = "SELECT degree_id FROM degrees WHERE degree_id = ?";
            $stmt = $conn->prepare($check_degree);
            $stmt->bind_param("i", $degree_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                die(json_encode(['error' => 'Invalid degree program selected']));
            }

            // Check for existing student
            $check_sql = "SELECT s_id FROM students WHERE 
                LOWER(s_fname) = LOWER(?) AND 
                LOWER(s_lname) = LOWER(?) AND 
                (LOWER(s_mname) = LOWER(?) OR (s_mname IS NULL AND ? IS NULL)) AND 
                (LOWER(s_suffix) = LOWER(?) OR (s_suffix IS NULL AND ? IS NULL)) AND 
                s_bdate = ?";
                
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("sssssss", 
                $fname, 
                $lname, 
                $mname, 
                $mname,
                $suffix,
                $suffix, 
                $bdate
            );

            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                die(json_encode(['error' => 'A student with these details already exists']));
            }

            // Insert new student
            $insert_sql = "INSERT INTO students (s_fname, s_lname, s_mname, s_suffix, s_gender, s_bdate, s_cnum, s_email, s_password) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("sssssssss",
                $fname,
                $lname,
                $mname,
                $suffix,
                $gender,
                $bdate,
                $cnum,
                $email,
                $password
            );

            if ($stmt->execute()) {
                // Get the last inserted student ID
                $student_id = $conn->insert_id;
                
                // Insert into students_degrees junction table
                $insert_degree_sql = "INSERT INTO students_degrees (s_id, degree_id, s_fname, s_lname, s_mname, s_gender) 
                                    VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_degree_sql);
                $stmt->bind_param("iissss", 
                    $student_id, 
                    $degree_id,
                    $fname,
                    $lname,
                    $mname,
                    $gender
                );
                
                if ($stmt->execute()) {
                    $conn->commit();
                    echo json_encode(['success' => 'Account created successfully']);
                } else {
                    $conn->rollback();
                    throw new Exception('Failed to assign degree program');
                }
            } else {
                $conn->rollback();
                throw new Exception('Failed to create account');
            }

        } elseif ($role === 'teacher') {
            // Store all values in variables before binding
            $fname = $_POST['t_fname'];
            $lname = $_POST['t_lname'];
            $mname = empty($_POST['t_mname']) ? null : $_POST['t_mname'];
            $suffix = empty($_POST['t_suffix']) ? null : $_POST['t_suffix'];
            $gender = $_POST['t_gender'];
            $bdate = $_POST['t_bdate'];
            $department = $_POST['t_department'];
            $cnum = $_POST['t_cnum'];
            $email = $_POST['t_email'];
            $password = $_POST['t_password']; // Remove hashing

            // Check for existing teacher
            $check_sql = "SELECT t_id FROM teachers WHERE 
                LOWER(t_fname) = LOWER(?) AND 
                LOWER(t_lname) = LOWER(?) AND 
                (LOWER(t_mname) = LOWER(?) OR (t_mname IS NULL AND ? IS NULL)) AND 
                (LOWER(t_suffix) = LOWER(?) OR (t_suffix IS NULL AND ? IS NULL)) AND 
                t_bdate = ?";
            
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("sssssss", 
                $fname,
                $lname,
                $mname,
                $mname,
                $suffix,
                $suffix,
                $bdate
            );

            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                die(json_encode(['error' => 'A teacher with these details already exists']));
            }

            // Insert new teacher
            $insert_sql = "INSERT INTO teachers (t_fname, t_lname, t_mname, t_suffix, t_gender, t_bdate, t_department, t_cnum, t_email, t_password) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ssssssssss",
                $fname,
                $lname,
                $mname,
                $suffix,
                $gender,
                $bdate,
                $department,
                $cnum,
                $email,
                $password  // Use plain password
            );
        }
        
    } catch (Exception $e) {
        if (isset($conn)) $conn->rollback();
        die(json_encode(['error' => $e->getMessage()]));
    }
}
?>