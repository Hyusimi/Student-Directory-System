<?php
require_once __DIR__ . '/../../includes/db.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: ../../login.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Get teacher's subjects with sections and student counts
$subjects_query = "SELECT 
                    s.subject_code,
                    s.subject_description,
                    s.units,
                    sec.section_code,
                    sec.section_id,
                    d.degree_code,
                    d.degree_name,
                    ss.start_time,
                    ss.end_time,
                    ss.day_of_week,
                    r.room_number,
                    (SELECT COUNT(*) FROM students_sections WHERE section_id = sec.section_id) as student_count
                  FROM subjects s
                  INNER JOIN sections_schedules ss ON s.subject_code = ss.subject_code
                  INNER JOIN sections sec ON ss.section_id = sec.section_id
                  INNER JOIN degrees d ON sec.degree_id = d.degree_id
                  LEFT JOIN rooms r ON ss.room_id = r.room_id
                  WHERE ss.teacher_id = ?
                  ORDER BY s.subject_code, sec.section_code, ss.day_of_week, ss.start_time";

$stmt = $conn->prepare($subjects_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

// Process subjects
$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subject_code = $row['subject_code'];
    if (!isset($subjects[$subject_code])) {
        $subjects[$subject_code] = [
            'code' => $subject_code,
            'description' => $row['subject_description'],
            'units' => $row['units'],
            'sections' => []
        ];
    }
    
    $section_code = $row['section_code'];
    if (!isset($subjects[$subject_code]['sections'][$section_code])) {
        $subjects[$subject_code]['sections'][$section_code] = [
            'section_id' => $row['section_id'],
            'section_code' => $section_code,
            'degree_code' => $row['degree_code'],
            'degree_name' => $row['degree_name'],
            'student_count' => $row['student_count'],
            'schedules' => []
        ];
    }
    
    $subjects[$subject_code]['sections'][$section_code]['schedules'][] = [
        'day' => $row['day_of_week'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'room' => $row['room_number']
    ];
}

// Get total students per subject
foreach ($subjects as $code => &$subject) {
    $total_students = 0;
    foreach ($subject['sections'] as $section) {
        $total_students += $section['student_count'];
    }
    $subject['total_students'] = $total_students;
}
unset($subject);

?>

<style>
:root {
    --primary: #3d52a0;
    --secondary: #7091E6;
    --card-border-radius: 0.75rem;
    --transition-speed: 0.3s;
}

.card {
    border-radius: var(--card-border-radius);
    box-shadow: 0 4px 6px rgba(61, 82, 160, 0.07);
    border: none;
    transition: transform var(--transition-speed);
    margin-bottom: 1.5rem;
}

.card:hover {
    transform: translateY(-2px);
}

.card-header {
    background: linear-gradient(145deg, var(--primary) 0%, var(--secondary) 100%) !important;
    color: white;
    border-radius: var(--card-border-radius) var(--card-border-radius) 0 0 !important;
    padding: 1rem 1.5rem;
}

.subject-card {
    height: 100%;
}

.subject-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.subject-stats {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(61, 82, 160, 0.05);
    border-radius: 0.5rem;
}

.stat-item {
    flex: 1;
    text-align: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--primary);
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
}

.section-list {
    margin-top: 1rem;
}

.section-item {
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.schedule-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.schedule-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0;
    font-size: 0.875rem;
}

.schedule-item i {
    color: var(--primary);
}

/* Prevent horizontal scroll */
html, body {
    max-width: 100%;
    overflow-x: hidden;
}

/* Main content area */
main {
    margin-left: 5px;
    width: calc(100% - 260px);
    padding: 0;
    overflow-x: hidden;
}

/* Container adjustments */
.container-fluid {
    width: 100%;
    padding: 0;
    margin: 0;
    overflow-x: hidden;
}

/* Add padding to content */
h2.mb-4 {
    padding: 1rem;
}

.card-body {
    padding: 1.5rem;
}
</style>

<main>
    <h2 class="mb-4">My Subjects</h2>
    <div class="container-fluid">
        <div class="row">
            <?php foreach ($subjects as $subject): ?>
            <div class="col-md-6 mb-4">
                <div class="card subject-card">
                    <div class="card-header">
                        <div class="subject-header">
                            <h5 class="mb-0">
                                <?php echo htmlspecialchars($subject['code']); ?>
                                <small class="d-block text-white-50">
                                    <?php echo htmlspecialchars($subject['description']); ?>
                                </small>
                            </h5>
                            <span class="badge bg-white text-primary">
                                <?php echo $subject['units']; ?> units
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="subject-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo count($subject['sections']); ?></div>
                                <div class="stat-label">Sections</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $subject['total_students']; ?></div>
                                <div class="stat-label">Students</div>
                            </div>
                        </div>

                        <div class="section-list">
                            <?php foreach ($subject['sections'] as $section): ?>
                            <div class="section-item">
                                <div class="section-header">
                                    <h6 class="mb-0">
                                        <?php echo htmlspecialchars($section['section_code']); ?>
                                        <small class="text-muted d-block">
                                            <?php echo htmlspecialchars($section['degree_name']); ?>
                                        </small>
                                    </h6>
                                    <span class="badge bg-primary">
                                        <?php echo $section['student_count']; ?> students
                                    </span>
                                </div>
                                <ul class="schedule-list">
                                    <?php foreach ($section['schedules'] as $schedule): ?>
                                    <li class="schedule-item">
                                        <i class="bi bi-clock"></i>
                                        <?php echo $schedule['day']; ?>
                                        <?php echo date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time'])); ?>
                                        <i class="bi bi-building ms-2"></i>
                                        <?php echo htmlspecialchars($schedule['room']); ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($subjects)): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-journal-x display-1 text-muted"></i>
                        <h4 class="mt-3">No Subjects Assigned</h4>
                        <p class="text-muted">You don't have any subjects assigned to you at the moment.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>