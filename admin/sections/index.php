<?php
require_once __DIR__ . '/../../includes/db.php';

// Debug: Print total number of students
$debug_query = "SELECT COUNT(*) as total FROM students";
$debug_result = $conn->query($debug_query);
$total_students = $debug_result->fetch_assoc()['total'];

// Debug: Print total number of assigned students
$debug_assigned = "SELECT COUNT(DISTINCT s_id) as total FROM students_sections";
$assigned_result = $conn->query($debug_assigned);
$total_assigned = $assigned_result->fetch_assoc()['total'];

// Update unassigned students query to include degree information 
$unassigned_query = "SELECT s.s_id, s.s_lname, s.s_fname, s.s_mname, sd.degree_code 
                     FROM students s 
                     LEFT JOIN students_sections ss ON s.s_id = ss.s_id 
                     LEFT JOIN students_degrees sd ON s.s_id = sd.s_id 
                     WHERE ss.s_id IS NULL 
                     AND sd.status = 'Active'
                     ORDER BY s.s_lname, s.s_fname";
$unassigned_students = $conn->query($unassigned_query);

// Reset the result pointer for the main display
$unassigned_students = $conn->query($unassigned_query);

// Fetch all sections with their degree codes
$sections_query = "SELECT section_id, section_code, year_level FROM sections";
$sections_result = $conn->query($sections_query);
$all_sections = [];
while($section = $sections_result->fetch_assoc()) {
    // Split section code into degree and section number (e.g., "BSIT 3A" -> ["BSIT", "3A"])
    $parts = explode(' ', $section['section_code'], 2);
    $section['degree_code'] = $parts[0];
    $section['section_name'] = isset($parts[1]) ? $parts[1] : '';
    $all_sections[] = $section;
}

// Modify sections query to include student count and max_students
$query = "SELECT s.*, 
          (SELECT COUNT(*) FROM students_sections ss WHERE ss.section_id = s.section_id) as student_count,
          s.max_students,
          sa.t_id, 
          CONCAT(sa.t_lname, ', ', sa.t_fname, ' ', COALESCE(LEFT(sa.t_mname, 1), ''), '.') as advisor_name 
          FROM sections s 
          LEFT JOIN sections_advisors sa ON s.section_id = sa.section_id";
$sections = $conn->query($query);

// Add this after the existing debug queries
$teachers_query = "SELECT t.t_id, t.t_lname, t.t_fname, t.t_mname 
                  FROM teachers t
                  LEFT JOIN sections_advisors sa ON t.t_id = sa.t_id
                  WHERE sa.t_id IS NULL
                  ORDER BY t.t_lname, t.t_fname";
$teachers = $conn->query($teachers_query);
?>

<!-- Update the container -->
<div class="container-fluid">
    <!-- Add message containers -->
    <div id="messageContainer" class="position-fixed start-50 translate-middle-x" style="z-index: 1060; top: 20px;"></div>
    <div id="notificationContainer" class="position-fixed start-50 translate-middle-x" style="z-index: 1060; top: 20px;"></div>
    <div id="alertContainer" class="position-fixed start-50 translate-middle-x" style="z-index: 1060; top: 20px;"></div>

    <!-- Add breadcrumb header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Section Management</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="?page=dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active">Sections</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Unassigned Students Section -->
    <div class="card mt-3" id="unassignedStudentsCard"> 
        <div class="card-header bg-warning d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Unassigned Students</h5>
        </div>
        <div class="card-body p-0">
            <?php if($unassigned_students->num_rows > 0) { ?>
            <div class="table-responsive mt-3" id="unassignedTableContainer" style="overflow-x: hidden;">
                <table class="table table-hover align-middle mb-0" id="unassignedStudentsTable" style="min-width: 100%;">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Degree</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $displayed_count = 0;
                        while($student = $unassigned_students->fetch_assoc()) {
                            if ($displayed_count >= 5) break;
                            $displayed_count++;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['s_id']); ?></td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars($student['s_lname'] . ', ' . 
                                        $student['s_fname'] . ' ' . 
                                        ($student['s_mname'] ? substr($student['s_mname'], 0, 1) . '.' : '')); 
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($student['degree_code']); ?></td>
                                <td>
                                    <button type="button" 
                                            class="btn btn-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#assignModal"
                                            data-student-id="<?php echo $student['s_id']; ?>"
                                            data-degree-code="<?php echo $student['degree_code']; ?>">
                                        Assign to Section
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class='alert alert-info mx-3 my-3' id="displayedCountAlert" style="width: calc(100% - 2rem);">
                Students displayed: <span id="displayedCount"><?php echo $displayed_count; ?></span>
            </div>
            <?php } else { ?>
                <div class="alert alert-success mx-3 my-3" id="noUnassignedMessage"> 
                    No unassigned students found. All students have been assigned to sections.
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- Single Assignment Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Student to Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="assignStudentForm">
                    <div class="modal-body">
                        <input type="hidden" name="student_id" id="studentIdInput">
                        <div id="studentInfoDisplay"></div>
                        <div class="mb-3">
                            <label for="section" class="form-label">Select Section</label>
                            <select name="section_id" class="form-select" required id="sectionSelect">
                                <option value="">Choose a section...</option>
                                <?php foreach($all_sections as $section): ?>
                                    <option value="<?php echo $section['section_id']; ?>" 
                                            data-degree-code="<?php echo $section['degree_code']; ?>">
                                        <?php echo $section['section_code']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">
                                Note: Only sections matching the student's degree program can be assigned.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Assign Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Replace add section button -->
    <div class="row mb-4 mt-4">
        <div class="col">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                <i class="bi bi-plus-lg me-2"></i>Add New Section
            </button>
        </div>
    </div>

    <!-- Update the section cards container -->
    <div class="row g-3">
        <?php while($section = $sections->fetch_assoc()) { 
            // Get students in this section
            $students_query = "SELECT s.s_id, s.s_lname, s.s_fname, s.s_mname, sd.degree_code, sec.section_code 
                              FROM students s
                              JOIN students_sections ss ON s.s_id = ss.s_id
                              JOIN students_degrees sd ON s.s_id = sd.s_id
                              JOIN sections sec ON ss.section_id = sec.section_id
                              WHERE ss.section_id = " . $section['section_id'] . "
                              ORDER BY s.s_lname, s.s_fname";
            $students_result = $conn->query($students_query);
        ?>
            <div class="col-12 col-lg-4"> 
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 d-flex align-items-center">
                                <span class="section-code"><?php echo htmlspecialchars($section['section_code']); ?></span>
                            </h5>
                            <div class="btn-group btn-group-sm">
                                <button type="button" 
                                        class="btn btn-primary px-2 edit-section-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editSectionModal"
                                        data-section-id="<?php echo $section['section_id']; ?>"
                                        data-section-code="<?php echo htmlspecialchars($section['section_code']); ?>"
                                        data-year-level="<?php echo htmlspecialchars($section['year_level']); ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" 
                                        class="btn btn-danger px-2 delete-section-btn"
                                        data-section-id="<?php echo $section['section_id']; ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-info">
                            <p class="mb-1" id="yearLevel-<?php echo $section['section_id']; ?>">
                                Year Level: <?php echo $section['year_level']; ?>
                            </p>
                            <p class="mb-1">
                                <strong>Advisor:</strong>
                                <div class="advisor-container" data-section-id="<?php echo $section['section_id']; ?>">
                                    <div class="advisor-name">
                                        <?php if ($section['advisor_name']): ?>
                                            <span class="text-primary"><?php echo htmlspecialchars($section['advisor_name']); ?></span>
                                            <div class="btn-group btn-group-sm ms-2">
                                                <button type="button" 
                                                        class="btn btn-primary px-2 edit-advisor-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#assignAdvisorModal"
                                                        data-section-id="<?php echo $section['section_id']; ?>"
                                                        data-advisor-id="<?php echo isset($section['advisor_id']) ? $section['advisor_id'] : ''; ?>"
                                                        data-mode="edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-danger delete-advisor-btn"
                                                        data-section-id="<?php echo $section['section_id']; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted me-2">No advisor assigned</span>
                                            <button type="button" 
                                                    class="btn btn-sm btn-success assign-advisor-btn"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#assignAdvisorModal"
                                                    data-section-id="<?php echo $section['section_id']; ?>"
                                                    data-mode="add">
                                                <i class="bi bi-person-plus"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </p>
                        </div>
                        
                        <!-- Assigned Students List -->
                        <div class="assigned-students">
                            <?php if($students_result->num_rows > 0) { ?>
                            <div class="table-responsive mt-3">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Degree</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($student = $students_result->fetch_assoc()) { ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    echo htmlspecialchars($student['s_lname'] . ', ' . 
                                                         $student['s_fname'] . ' ' . 
                                                         substr($student['s_mname'], 0, 1) . '.');
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($student['degree_code']); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" 
                                                                class="btn btn-secondary edit-student-btn"
                                                                data-student-id="<?php echo $student['s_id']; ?>"
                                                                data-student-name="<?php echo htmlspecialchars($student['s_lname'] . ', ' . $student['s_fname']); ?>"
                                                                data-current-section="<?php echo htmlspecialchars($student['section_code']); ?>"
                                                                data-degree-code="<?php echo htmlspecialchars($student['degree_code']); ?>">
                                                            <i class="bi bi-arrow-repeat"></i> Transfer
                                                        </button>
                                                        <button type="button" 
                                                                class="btn btn-danger remove-student-btn"
                                                                data-student-id="<?php echo $student['s_id']; ?>"
                                                                data-student-name="<?php echo htmlspecialchars($student['s_lname'] . ', ' . $student['s_fname']); ?>"
                                                                data-section-id="<?php echo $section['section_id']; ?>"
                                                                data-degree-code="<?php echo htmlspecialchars($student['degree_code']); ?>">
                                                            <i class="bi bi-x-lg"></i> Remove
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php } else { ?>
                                <p class="text-muted mt-3">No students assigned to this section.</p>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Add Section Modal -->
    <div class="modal fade" id="addSectionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addSectionForm" method="POST" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Section Name</label>
                            <input type="text" name="section_name" class="form-control" 
                                   placeholder="e.g., Information Technology 3A" required>
                            <div class="invalid-feedback">Please enter a section name</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Section Code</label>
                            <input type="text" name="section_code" class="form-control" 
                                   placeholder="e.g., BSIT 3A" required
                                   pattern="[A-Z]+ [1-4][A-Z]"
                                   title="Format: DEGREE YearLetter (e.g., BSIT 3A)">
                            <div class="invalid-feedback">Please enter a valid section code (e.g., BSIT 3A)</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Maximum Students</label>
                            <input type="number" name="max_students" class="form-control" 
                                   value="40" min="1" max="100" required>
                            <div class="invalid-feedback">Please enter a valid maximum number of students (1-100)</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="addSectionForm" class="btn btn-primary">Add Section</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Section Modal -->
    <div class="modal fade" id="editSectionModal" tabindex="-1" aria-labelledby="editSectionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSectionModalLabel">Edit Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editSectionForm" novalidate>
                    <div class="modal-body">
                        <input type="hidden" id="editSectionId" name="section_id">
                        <div class="mb-3">
                            <label for="editSectionCode" class="form-label">Section Code</label>
                            <input type="text" class="form-control" id="editSectionCode" name="section_code" required
                                   pattern="[A-Z]+ [1-4][A-Z]"
                                   title="Format: DEGREE YearLetter (e.g., BSIT 3A)">
                            <div class="invalid-feedback">Please enter a valid section code (e.g., BSIT 3A)</div>
                        </div>
                        <div class="mb-3">
                            <label for="editYearLevel" class="form-label">Year Level</label>
                            <select class="form-control" id="editYearLevel" name="year_level" required>
                                <option value="">Select Year Level</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                            <div class="invalid-feedback">Please select a year level</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Transfer Student Modal -->
    <div class="modal fade" id="transferStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transfer Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="transferStudentForm">
                    <div class="modal-body">
                        <input type="hidden" name="student_id" id="transferStudentId">
                        <div id="transferStudentInfo" class="mb-3"></div>
                        <div class="mb-3">
                            <label for="newSectionSelect" class="form-label">Select New Section</label>
                            <select name="new_section_id" class="form-select" required id="newSectionSelect">
                                <option value="">Choose a section...</option>
                                <?php foreach($all_sections as $section): ?>
                                    <option value="<?php echo $section['section_id']; ?>" 
                                            data-degree-code="<?php echo $section['degree_code']; ?>">
                                        <?php echo $section['section_code']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Transfer Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Advisor Modal -->
    <div class="modal fade" id="assignAdvisorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Section Advisor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="assignAdvisorForm">
                    <div class="modal-body">
                        <input type="hidden" name="section_id" id="advisorSectionId">
                        <input type="hidden" name="section_code" id="advisorSectionCode">
                        <div class="mb-3">
                            <label class="form-label">Select Advisor</label>
                            <select name="advisor_id" id="teacherSelect" class="form-select" required>
                                <option value="">Choose an advisor...</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Assign Advisor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- Update the style block - ONLY these specific styles need to change -->
<style>
body {
    background: #EDE8F5;
    margin: 0;
    padding: 0;
}

.assigned-students .table thead th {
    background: none !important;
}

.card-header .btn {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
    transition: all 0.2s ease-in-out;
}

.card-header .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header .btn i {
    margin-right: 0.25rem;
}

.edit-section-btn:hover {
    background-color: #0d6efd;
    color: white;
}

.delete-section-btn:hover {
    background-color: #dc3545;
    color: white;
}

.assign-advisor-btn:hover {
    background-color: #198754;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.assign-advisor-btn {
    background-color: #198754;
    border-color: #198754;
    color: white;
}

.assign-advisor-btn:hover {
    background-color: #146c43;
    border-color: #146c43;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.15);
}

.edit-section-btn {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

.edit-section-btn:hover {
    background-color: #0b5ed7;
    border-color: #0b5ed7;
}

.delete-section-btn {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

.delete-section-btn:hover {
    background-color: #bb2d3b;
    border-color: #bb2d3b;
}

.edit-advisor-btn {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

.edit-advisor-btn:hover {
    background-color: #0b5ed7;
    border-color: #0b5ed7;
}

.delete-advisor-btn {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

.delete-advisor-btn:hover {
    background-color: #bb2d3b;
    border-color: #bb2d3b;
}

.card-info {
    margin-bottom: 0.5rem;
}

.assigned-students {
    margin-top: 0.5rem;
}

.assigned-students h6 {
    font-size: 0.9rem;
}

/* Card and Layout Adjustments */
.container-fluid {
    margin-top: -20px;
    margin-left: -15px;
    background: #EDE8F5;
    min-height: 100vh;
    padding: 25px;
    overflow-x: hidden; 
    max-width: 100%;    
}

/* Spacing Adjustments */
.card-info {
    margin-bottom: 0.75rem;
}

.card-info p {
    margin-bottom: 0.5rem;
}

.assigned-students {
    margin-top: 0.75rem;
}

/* Table Adjustments */
.table-responsive {
    margin: 0;
    overflow-x: hidden;
    width: 100%; 
}

/* Remove any potential horizontal overflow */
.row {
    margin-right: -12.5px; 
    margin-left: -12.5px;  
}

/* Update column spacing */
.col-12 {
    padding-right: 12.5px;  
    padding-left: 12.5px;   
}

/* Ensure content doesn't overflow */
* {
    max-width: none;
}

/* Custom Scrollbar Styles */
::-webkit-scrollbar {
    width: 8px;
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: rgba(108, 117, 125, 0.5);
    border-radius: 10px;
    border: 2px solid transparent;
    background-clip: padding-box;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(108, 117, 125, 0.8);
    border: 2px solid transparent;
    background-clip: padding-box;
}

::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.4);
    border-radius: 10px;
}

/* For Firefox */
* {
    scrollbar-width: thin;
    scrollbar-color: rgba(108, 117, 125, 0.5) rgba(255, 255, 255, 0.4);
}
</style>

<!-- Add JavaScript at the bottom of the file -->
<script>
function showAlert(type, message) {
    const alertContainer = document.getElementById('alertContainer');
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    alertContainer.appendChild(alertDiv);

    // Auto dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

$(document).ready(function() {
    const $addForm = $('#addSectionForm');
    if (!$addForm.length) return;

    $addForm.on('submit', function(e) {
        e.preventDefault();
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true);

        $.ajax({
            url: '/Project/admin/sections/processes/add_section.php',
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addSectionModal').modal('hide');
                    $addForm[0].reset();
                    $addForm.removeClass('was-validated');
                    showAlert('success', 'Section added successfully');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('danger', response.message || 'Failed to add section');
                }
            },
            error: function() {
                showAlert('danger', 'Server error occurred while adding section');
            },
            complete: function() {
                $submitBtn.prop('disabled', false);
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Advisor assignment handling
    const assignAdvisorModal = document.getElementById('assignAdvisorModal');
    if (assignAdvisorModal) {
        assignAdvisorModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const mode = button.getAttribute('data-mode');
            const sectionId = button.getAttribute('data-section-id');
            const advisorId = button.getAttribute('data-advisor-id');

            // Fetch available teachers
            fetch(`/Project/admin/sections/processes/get_available_teachers.php?mode=${mode}&current_advisor=${advisorId}`)
                .then(response => response.json())
                .then(data => {
                    const teacherSelect = document.getElementById('teacherSelect');
                    teacherSelect.innerHTML = '<option value="">Select Advisor</option>';
                    data.teachers.forEach(teacher => {
                        const option = document.createElement('option');
                        option.value = teacher.t_id;
                        option.textContent = `${teacher.t_lname}, ${teacher.t_fname} ${teacher.t_mname || ''}`;
                        if (mode === 'edit' && teacher.t_id === advisorId) {
                            option.selected = true;
                        }
                        teacherSelect.appendChild(option);
                    });
                });

            // Update form action and section ID
            const form = assignAdvisorModal.querySelector('form');
            form.setAttribute('action', `/Project/admin/sections/processes/${mode === 'edit' ? 'update' : 'assign'}_advisor.php`);
            const sectionIdInput = form.querySelector('input[name="section_id"]');
            sectionIdInput.value = sectionId;
        });
    }
});

$(document).ready(function() {
    // Initialize Bootstrap modals
    const editSectionModal = document.getElementById('editSectionModal');
    const bsEditModal = new bootstrap.Modal(editSectionModal);

    // Edit Section Button Click Handler
    $(document).on('click', '.edit-section-btn', function(e) {
        e.preventDefault();
        const button = $(this);
        const sectionId = button.attr('data-section-id');
        const sectionCode = button.attr('data-section-code');
        const yearLevel = button.attr('data-year-level');
        
        // Set form values
        const form = $('#editSectionForm');
        form[0].reset(); // Reset form before setting new values
        $('#editSectionId').val(sectionId);
        $('#editSectionCode').val(sectionCode);
        $('#editYearLevel').val(yearLevel);
        
        // Show modal
        bsEditModal.show();
    });

    // Form Submit Handler
    $('#editSectionForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        if (!form[0].checkValidity()) {
            e.stopPropagation();
            form.addClass('was-validated');
            return;
        }
        
        const formData = new FormData(form[0]);
        const sectionId = formData.get('section_id');
        const sectionCode = formData.get('section_code');
        const yearLevel = formData.get('year_level');
        
        fetch('/Project/admin/sections/processes/edit_section.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the card and button
                const editButton = $(`.edit-section-btn[data-section-id="${sectionId}"]`);
                const card = editButton.closest('.card');
                
                // Update text content
                card.find('.section-code').text(sectionCode);
                card.find(`#yearLevel-${sectionId}`).text(`Year Level: ${yearLevel}`);
                
                // Update button data attributes
                editButton.attr({
                    'data-section-code': sectionCode,
                    'data-year-level': yearLevel,
                    'data-section-id': sectionId
                });
                
                // Hide modal and show success
                bsEditModal.hide();
                form.removeClass('was-validated');
                showAlert('success', 'Section updated successfully');
            } else {
                showAlert('danger', data.message || 'Failed to update section');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Server error occurred');
        });
    });

    // Modal Close Handler
    editSectionModal.addEventListener('hidden.bs.modal', function() {
        const form = $('#editSectionForm');
        form.removeClass('was-validated');
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
        $('body').css('padding-right', '');
    });

    // Assign Modal Handler
    const assignModal = document.getElementById('assignModal');
    if (assignModal) {
        assignModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const studentId = button.getAttribute('data-student-id');
            const studentDegree = button.getAttribute('data-degree-code');
            
            // Set the student ID in the hidden input
            document.getElementById('studentIdInput').value = studentId;
            
            // Filter sections based on student's degree
            const sectionSelect = document.getElementById('sectionSelect');
            Array.from(sectionSelect.options).forEach(option => {
                const sectionDegree = option.getAttribute('data-degree-code');
                option.disabled = sectionDegree && sectionDegree !== studentDegree;
                if (option.disabled) {
                    option.style.display = 'none';
                } else {
                    option.style.display = '';
                }
            });
            
            // Reset selection
            sectionSelect.value = '';
        });

        // Handle form submission
        const assignForm = document.getElementById('assignStudentForm');
        assignForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/Project/admin/sections/processes/assign_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update section count badge
                    const sectionId = formData.get('section_id');
                    const sectionCountBadge = $(`#section-count-${sectionId}`);
                    const currentCountSpan = sectionCountBadge.find('.current-count');
                    const currentCount = parseInt(currentCountSpan.text());
                    currentCountSpan.text(currentCount + 1);
                    
                    showAlert('success', 'Student assigned successfully');
                    const modal = bootstrap.Modal.getInstance(assignModal);
                    modal.hide();
                    setTimeout(() => location.reload(), 500);
                } else {
                    showAlert('danger', data.message || 'Failed to assign student');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Server error occurred');
            });
        });
    }
});

$(document).ready(function() {
    // Transfer Student Button Handler
    $(document).on('click', '.edit-student-btn', function() {
        const studentId = $(this).data('student-id');
        const studentName = $(this).data('student-name');
        const currentSection = $(this).data('current-section');
        const degreeCode = $(this).data('degree-code');
        
        $('#transferStudentId').val(studentId);
        $('#transferStudentInfo').html(`
            <p><strong>Student:</strong> ${studentName}</p>
            <p><strong>Current Section:</strong> ${currentSection}</p>
            <p><strong>Degree Program:</strong> ${degreeCode}</p>
        `);
        
        // Filter sections based on degree code
        const $sectionSelect = $('#newSectionSelect');
        $sectionSelect.find('option').each(function() {
            const sectionDegreeCode = $(this).data('degree-code');
            if (sectionDegreeCode === degreeCode) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        // Reset section select
        $sectionSelect.val('');
        
        new bootstrap.Modal('#transferStudentModal').show();
    });

    // Transfer Student Form Submit
    $('#transferStudentForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('/Project/admin/sections/processes/transfer_student.php', {
            method: 'POST',
            body: JSON.stringify(Object.fromEntries(formData)),
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Student transferred successfully');
                location.reload(); // Reload to update the lists
            } else {
                showAlert('danger', data.message || 'Failed to transfer student');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Server error occurred');
        });
    });
});

// Remove Student Handler
$(document).on('click', '.remove-student-btn', function() {
    const studentId = $(this).data('student-id');
    const studentName = $(this).data('student-name');
    const sectionId = $(this).data('section-id');
    const degreeCode = $(this).data('degree-code');
    
    if (confirm(`Are you sure you want to remove ${studentName} from this section?`)) {
        fetch('/Project/admin/sections/processes/remove_student.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                student_id: studentId,
                section_id: sectionId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove from section
                const studentRow = $(this).closest('tr');
                studentRow.fadeOut(300, function() {
                    $(this).remove();
                    
                    // Update section count badge
                    const sectionCountBadge = $(`#section-count-${sectionId}`);
                    const currentCountSpan = sectionCountBadge.find('.current-count');
                    const currentCount = parseInt(currentCountSpan.text());
                    currentCountSpan.text(currentCount - 1);
                    
                    // Check if section is now empty
                    const sectionTable = $(this).closest('table');
                    if (sectionTable.find('tbody tr').length === 0) {
                        sectionTable.closest('.table-responsive').replaceWith(
                            '<p class="text-muted mt-3">No students assigned to this section.</p>'
                        );
                    }
                });

                // Handle unassigned students table
                const unassignedTable = $('#unassignedStudentsTable');
                const displayedCountSpan = $('#displayedCount');
                const displayedCount = parseInt(displayedCountSpan.text() || '0');

                if (displayedCount < 5) {
                    // Prepare new row
                    const newRow = `
                        <tr style="display: none;">
                            <td>${studentId}</td>
                            <td>${studentName}</td>
                            <td>${degreeCode}</td>
                            <td>
                                <button type="button" 
                                        class="btn btn-primary btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#assignModal"
                                        data-student-id="${studentId}"
                                        data-degree-code="${degreeCode}">
                                    Assign to Section
                                </button>
                            </td>
                        </tr>
                    `;

                    // Handle first unassigned student
                    if (displayedCount === 0) {
                        // Hide no students message
                        $('#noUnassignedMessage').fadeOut(300, function() {
                            // Show table container
                            const tableContainer = $(`
                                <div class="table-responsive mt-3" id="unassignedTableContainer" style="display: none;">
                                    <table class="table table-hover align-middle mb-0" id="unassignedStudentsTable" style="min-width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>Student ID</th>
                                                <th>Name</th>
                                                <th>Degree</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>${newRow}</tbody>
                                    </table>
                                </div>
                                <div class='alert alert-info mx-3 my-3' id="displayedCountAlert" style="width: calc(100% - 2rem);">
                                    Students displayed: <span id="displayedCount">1</span>
                                </div>
                            `);
                            
                            $('#unassignedStudentsCard .card-body').append(tableContainer);
                            tableContainer.fadeIn(300);
                            tableContainer.find('tr').fadeIn(300);
                        });
                    } else {
                        // Add to existing table
                        const $newRow = $(newRow);
                        unassignedTable.find('tbody').append($newRow);
                        $newRow.fadeIn(300);
                        
                        // Update count
                        displayedCountSpan.text(displayedCount + 1);
                    }
                }
            } else {
                showAlert('danger', data.message || 'Failed to remove student');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Server error occurred');
        });
    }
});
</script>
<script src="/Project/admin/sections/js/section-operations.js"></script>
