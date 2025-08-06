<?php
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');

try {
    // Validate input
    if (empty($_POST['section_name']) || empty($_POST['section_code']) || empty($_POST['max_students'])) {
        throw new Exception('All fields are required');
    }

    $section_name = trim($_POST['section_name']);
    $section_code = trim($_POST['section_code']);
    $max_students = (int)$_POST['max_students'];

    // Extract year level from section code
    if (!preg_match('/^[A-Z]+ ([1-4])[A-Z]$/', $section_code, $matches)) {
        throw new Exception('Invalid section code format');
    }
    $year_level = $matches[1];

    // Check if section code already exists
    $check_stmt = $conn->prepare("SELECT section_id FROM sections WHERE section_code = ?");
    $check_stmt->bind_param("s", $section_code);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception('Section code already exists');
    }

    // Prepare and execute the insert query
    $stmt = $conn->prepare("INSERT INTO sections (section_code, section_name, year_level, max_students) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $section_code, $section_name, $year_level, $max_students);
    
    if (!$stmt->execute()) {
        // Check if it's a duplicate entry error
        if ($stmt->errno == 1062) {
            throw new Exception('Section code already exists');
        } else {
            throw new Exception('Failed to add section: ' . $stmt->error);
        }
    }

    $section_id = $conn->insert_id;
    echo json_encode([
        'success' => true,
        'message' => 'Section added successfully',
        'section_id' => $section_id
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
