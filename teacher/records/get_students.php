<?php
require_once '../../includes/db.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['section_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Section ID is required']);
    exit();
}

$section_id = intval($_GET['section_id']);
$teacher_id = $_SESSION['user_id'];

// Verify that this section belongs to the teacher
$verify_query = "SELECT 1 FROM sections_schedules WHERE section_id = ? AND teacher_id = ? LIMIT 1";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $section_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access to this section']);
    exit();
}

// Get students in the section
$query = "SELECT 
            s.student_id,
            s.student_number,
            CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name) as name
          FROM students s
          INNER JOIN students_sections ss ON s.student_id = ss.student_id
          WHERE ss.section_id = ?
          ORDER BY s.last_name, s.first_name";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $section_id);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = [
        'student_number' => $row['student_number'],
        'name' => $row['name']
    ];
}

echo json_encode([
    'success' => true,
    'students' => $students
]);
