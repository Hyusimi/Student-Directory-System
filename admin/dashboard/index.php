<?php
require_once __DIR__ . '/../../includes/db.php';

// Fetch statistics
$stats = [
    'students' => $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'],
    'teachers' => $conn->query("SELECT COUNT(*) as count FROM teachers")->fetch_assoc()['count'],
    'sections' => $conn->query("SELECT COUNT(*) as count FROM sections")->fetch_assoc()['count'],
    'subjects' => $conn->query("SELECT COUNT(*) as count FROM subjects")->fetch_assoc()['count']
];

// Fetch recent activities (using ID as order since created_at is not available)
$recent_activities_query = "
    (SELECT 
        s_id as id,
        CONCAT(s_fname, ' ', s_lname) as name,
        LEFT(s_fname, 1) as first_initial,
        LEFT(s_lname, 1) as last_initial,
        'Student' as type,
        'Added new student' as action,
        'fa-user-graduate' as icon
    FROM students
    ORDER BY s_id DESC)
    UNION ALL
    (SELECT 
        t_id as id,
        CONCAT(t_fname, ' ', t_lname) as name,
        LEFT(t_fname, 1) as first_initial,
        LEFT(t_lname, 1) as last_initial,
        'Teacher' as type,
        'Added new teacher' as action,
        'fa-chalkboard-teacher' as icon
    FROM teachers
    ORDER BY t_id DESC)
    ORDER BY id DESC";

$recent_activities = $conn->query($recent_activities_query);

// Fetch section statistics
$section_stats_query = "
    SELECT 
        s.section_code, 
        COUNT(ss.s_id) as student_count,
        s.max_students,
        ROUND((COUNT(ss.s_id) / s.max_students) * 100) as fill_percentage
    FROM sections s
    LEFT JOIN students_sections ss ON s.section_id = ss.section_id
    GROUP BY s.section_id
    ORDER BY fill_percentage DESC";
$section_stats = $conn->query($section_stats_query);
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Welcome, Admin!</h1>
        <p class="text-muted mb-0">Here's what's happening in your school today.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/Project/admin/reports/download_report.php" class="btn btn-primary">
            <span class="material-icons align-middle" style="font-size: 1rem;">download</span> Download Report
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <span class="material-icons text-primary" style="font-size: 2.5rem;">school</span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1">Total Students</p>
                        <h4 class="mb-0 fw-bold"><?php echo number_format($stats['students']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <span class="material-icons text-success" style="font-size: 2.5rem;">person_pin</span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1">Total Teachers</p>
                        <h4 class="mb-0 fw-bold"><?php echo number_format($stats['teachers']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <span class="material-icons text-info" style="font-size: 2.5rem;">groups</span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1">Total Sections</p>
                        <h4 class="mb-0 fw-bold"><?php echo number_format($stats['sections']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <span class="material-icons text-warning" style="font-size: 2.5rem;">menu_book</span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1">Total Subjects</p>
                        <h4 class="mb-0 fw-bold"><?php echo number_format($stats['subjects']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Section Capacity -->
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-light">
                <div class="d-flex align-items-center">
                    <div>
                        <h5 class="card-title mb-0 text-white">Section Capacity</h5>
                        <p class="text-white small mb-0">Current student distribution across sections</p>
                    </div>
                </div>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php while ($section = $section_stats->fetch_assoc()): ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($section['section_code']); ?></h6>
                                <small class="text-muted">
                                    <?php echo $section['student_count']; ?> of <?php echo $section['max_students']; ?> students
                                </small>
                            </div>
                            <div class="text-end">
                                <h6 class="mb-0"><?php echo $section['fill_percentage']; ?>%</h6>
                            </div>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar <?php 
                                echo $section['fill_percentage'] >= 90 ? 'bg-danger' : 
                                    ($section['fill_percentage'] >= 75 ? 'bg-warning' : 'bg-success'); 
                            ?>" style="width: <?php echo $section['fill_percentage']; ?>%"></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="avatar-sm rounded-circle bg-primary bg-opacity-10">
                            <i class="fas fa-history text-primary"></i>
                        </div>
                    </div>
                    <h5 class="card-title mb-0">Recent Activities</h5>
                </div>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <div class="list-group list-group-flush">
                    <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                        <div class="list-group-item border-0 px-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm rounded-circle bg-light d-flex align-items-center justify-content-center">
                                        <span class="fw-bold text-<?php 
                                            echo $activity['type'] === 'Student' ? 'primary' : 'success'; 
                                        ?>"><?php echo $activity['first_initial'] . $activity['last_initial']; ?></span>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="d-flex align-items-center">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($activity['name']); ?></h6>
                                        <div class="flex-shrink-0 ms-2">
                                            <span class="badge bg-<?php 
                                                echo $activity['type'] === 'Student' ? 'primary' : 'success'; 
                                            ?> bg-opacity-10 text-<?php 
                                                echo $activity['type'] === 'Student' ? 'primary' : 'success'; 
                                            ?>">
                                                <i class="fas <?php echo $activity['icon']; ?> me-1"></i>
                                                <?php echo $activity['type']; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-0"><?php echo $activity['action']; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>

<style>
.avatar-sm {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.progress {
    background-color: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.6s ease;
}

/* Custom Scrollbar Styling */
.card-body::-webkit-scrollbar {
    width: 8px;
}

.card-body::-webkit-scrollbar-track {
    background: #f8f9fa;
    border-radius: 4px;
}

.card-body::-webkit-scrollbar-thumb {
    background: #dee2e6;
    border-radius: 4px;
}

.card-body::-webkit-scrollbar-thumb:hover {
    background: #adb5bd;
}

/* For Firefox */
.card-body {
    scrollbar-width: thin;
    scrollbar-color: #dee2e6 #f8f9fa;
}
</style>
