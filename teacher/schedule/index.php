<?php
require_once __DIR__ . '/../../includes/db.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: ../../login.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Get teacher's schedule
$schedule_query = "SELECT 
                    ss.*,
                    r.room_number,
                    r.capacity as room_capacity,
                    s.subject_code,
                    s.subject_description,
                    s.units,
                    sec.section_code as section_name,
                    d.degree_code,
                    d.degree_name
                  FROM sections_schedules ss
                  LEFT JOIN rooms r ON ss.room_id = r.room_id
                  LEFT JOIN subjects s ON ss.subject_code = s.subject_code
                  LEFT JOIN sections sec ON ss.section_id = sec.section_id
                  LEFT JOIN degrees d ON sec.degree_id = d.degree_id
                  WHERE ss.teacher_id = ?
                  ORDER BY ss.day_of_week, ss.start_time";

$stmt = $conn->prepare($schedule_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$schedules_result = $stmt->get_result();

// Process schedules by day
$schedules_by_day = [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$total_hours = 0;
$total_subjects = 0;
$unique_subjects = [];

while ($schedule = $schedules_result->fetch_assoc()) {
    $day = $schedule['day_of_week'];
    if (in_array($day, $days)) {
        if (!isset($schedules_by_day[$day])) {
            $schedules_by_day[$day] = [];
        }
        $schedules_by_day[$day][] = $schedule;
        
        // Parse start and end times
        $start_time = DateTime::createFromFormat('H:i:s', $schedule['start_time']);
        $end_time = DateTime::createFromFormat('H:i:s', $schedule['end_time']);
        
        if ($start_time && $end_time) {
            // Get hours and minutes
            $start_total_minutes = $start_time->format('H') * 60 + $start_time->format('i');
            $end_total_minutes = $end_time->format('H') * 60 + $end_time->format('i');
            
            // If end time is 00:00, treat it as 12:00 (noon)
            if ($end_total_minutes == 0) {
                $end_total_minutes = 12 * 60; // 12 hours in minutes
            }
            
            // Calculate duration in minutes
            $duration_minutes = $end_total_minutes - $start_total_minutes;
            
            // If duration is more than 3 hours, assume end time should be AM not PM
            // This means subtracting 12 hours worth of minutes
            if ($duration_minutes > 180) { // 3 hours = 180 minutes
                $end_total_minutes -= 12 * 60;
                $duration_minutes = $end_total_minutes - $start_total_minutes;
            }
            
            // If duration is still negative after adjustment, something's wrong
            if ($duration_minutes < 0) {
                // Log or handle the error
                continue;
            }
            
            // Calculate hours
            $hours = $duration_minutes / 60;
            $total_hours += $hours;
        }
        
        // Track unique subjects
        if (!isset($unique_subjects[$schedule['subject_code']])) {
            $unique_subjects[$schedule['subject_code']] = true;
            $total_subjects++;
        }
    }
}

?>

<style>
:root {
    --primary: #3d52a0;
    --secondary: #7091E6;
    --card-border-radius: 0.75rem;
    --transition-speed: 0.3s;
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

/* Table container adjustments */
.table-responsive {
    margin: 0;
    padding: 0;
    border: none;
    overflow-x: hidden;
}

.table {
    width: 100%;
    margin-bottom: 0;
    table-layout: fixed;
}

.card {
    border-radius: var(--card-border-radius);
    box-shadow: 0 4px 6px rgba(61, 82, 160, 0.07);
    border: none;
    transition: transform var(--transition-speed);
    position: relative;
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.card-header {
    background: linear-gradient(145deg, var(--primary) 0%, var(--secondary) 100%) !important;
    color: white;
    border-radius: 0 !important;
    padding: 1rem 1.5rem;
}

/* Add padding to content */
h2.mb-4 {
    padding: 1rem;
}

.card-body {
    padding: 1.5rem;
}

/* Table styles */
.table thead th {
    font-size: 14px;
    font-weight: 600;
    padding: 12px;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

/* Set fixed widths and prevent overflow */
.table th:nth-child(1), 
.table td:nth-child(1) {
    width: 150px;
    max-width: 150px;
}

.table th:nth-child(2), 
.table td:nth-child(2) {
    width: 250px;
    max-width: 250px;
}

.table th:nth-child(3), 
.table td:nth-child(3) {
    width: 120px;
    max-width: 120px;
}

.table th:nth-child(4), 
.table td:nth-child(4) {
    width: 180px;
    max-width: 180px;
}

.table th:nth-child(5), 
.table td:nth-child(5) {
    width: 250px;
    max-width: 250px;
}

/* Prevent horizontal scrollbar */
.table td, .table th {
    white-space: normal !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    word-wrap: break-word !important;
}

.table tr {
    transition: all var(--transition-speed);
}

.table tbody tr:hover {
    background-color: rgba(61, 82, 160, 0.05);
    transform: scale(1.002);
}

/* Stats cards */
.stats-card {
    background-color: #fff;
    border-radius: var(--card-border-radius);
    padding: 1.25rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.stats-card .stats-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.stats-card .stats-icon i {
    font-size: 1.5rem;
    color: white;
}

.stats-card .stats-info h3 {
    font-size: 1.5rem;
    margin-bottom: 0.25rem;
}

.stats-card .stats-info p {
    color: #6c757d;
    margin: 0;
}

/* No schedule message */
.no-schedule {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
    background-color: #f8f9fa;
    border-radius: var(--card-border-radius);
    margin-bottom: 1rem;
}

.no-schedule i {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: #adb5bd;
}
</style>

<main>
    <h2 class="mb-4">My Teaching Schedule</h2>
    <div class="container-fluid" data-page="schedule">
        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background-color: var(--primary);">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo number_format($total_hours, 1); ?></h3>
                        <p>Total Teaching Hours/Week</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background-color: var(--secondary);">
                        <i class="bi bi-book"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo $total_subjects; ?></h3>
                        <p>Unique Subjects</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background-color: #198754;">
                        <i class="bi bi-calendar-week"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo count(array_filter($days, function($day) use ($schedules_by_day) { 
                            return isset($schedules_by_day[$day]) && !empty($schedules_by_day[$day]); 
                        })); ?></h3>
                        <p>Teaching Days</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Schedule Cards -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-calendar3 me-2"></i>
                            Schedule Overview
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($days as $day) { ?>
                            <h6 class="fw-bold mb-3"><?php echo $day; ?></h6>
                            <?php if (isset($schedules_by_day[$day]) && !empty($schedules_by_day[$day])) { ?>
                                <div class="table-responsive mb-4">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th class="px-3">Time</th>
                                                <th class="px-3">Subject</th>
                                                <th class="px-3">Section</th>
                                                <th class="px-3">Room</th>
                                                <th class="px-3">Degree</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($schedules_by_day[$day] as $schedule) { ?>
                                                <tr>
                                                    <td class="px-3">
                                                        <?php 
                                                            $start = new DateTime($schedule['start_time']);
                                                            $end = new DateTime($schedule['end_time']);
                                                            
                                                            // If end time shows as PM and duration would be > 3 hours, assume it should be AM
                                                            if ($end->format('H') > 12 && ($end->getTimestamp() - $start->getTimestamp()) > 10800) {
                                                                // Subtract 12 hours
                                                                $end->modify('-12 hours');
                                                            }
                                                            
                                                            echo $start->format('h:i A') . ' - ' . $end->format('h:i A'); 
                                                        ?>
                                                    </td>
                                                    <td class="px-3">
                                                        <div class="fw-bold"><?php echo htmlspecialchars($schedule['subject_code']); ?></div>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($schedule['subject_description']); ?>
                                                            (<?php echo $schedule['units']; ?> units)
                                                        </small>
                                                    </td>
                                                    <td class="px-3"><?php echo htmlspecialchars($schedule['section_name']); ?></td>
                                                    <td class="px-3">
                                                        <?php echo htmlspecialchars($schedule['room_number']); ?>
                                                        <small class="text-muted d-block">Capacity: <?php echo $schedule['room_capacity']; ?></small>
                                                    </td>
                                                    <td class="px-3"><?php echo htmlspecialchars($schedule['degree_name']); ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } else { ?>
                                <div class="no-schedule mb-4">
                                    <i class="bi bi-calendar-x d-block"></i>
                                    <p class="mb-0">No classes scheduled for <?php echo $day; ?></p>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
