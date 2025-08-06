<?php
// Remove redundant session_start(); session is already started in admin/dashboard.php
// session_start();

// Update session check to be consistent with admin/dashboard.php
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: /Project/admin/login.php");
    exit;
}

require_once __DIR__ . '/../../includes/db.php';

// Update form actions to use absolute paths
$base_url = '/Project/admin/students/processes';

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = $search ? "WHERE s.s_fname LIKE '%$search%' OR s.s_lname LIKE '%$search%' OR s.s_id LIKE '%$search%'" : '';

// Update the SQL query to include s_password and s_department
$sql = "SELECT s.s_id, s.s_fname, s.s_lname, s.s_mname, s.s_suffix, s.s_gender, s.s_bdate, s.s_age, 
        s.s_cnum, s.s_email, s.s_password, s.s_status, sd.degree_code 
        FROM students s
        LEFT JOIN students_degrees sd ON s.s_id = sd.s_id
        $search_condition 
        ORDER BY s.s_lname ASC";
$result = $conn->query($sql);
?>
<div class="container-fluid p-0">
    <div id="messageContainer" class="position-fixed start-50 translate-middle-x" style="z-index: 1060; top: 20px;"></div>
    <div id="notificationContainer" class="position-fixed start-50 translate-middle-x" style="z-index: 1060; top: 20px;"></div>
    <div id="alertContainer" class="position-fixed start-50 translate-middle-x" style="z-index: 1060; top: 20px;"></div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Manage Students</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="?page=dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active">Students</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
            <i class="bi bi-plus-lg me-2"></i>Add New Student
        </button>
    </div>

    <!-- Search and Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Students List</h5>
            <form id="searchForm" class="d-flex align-items-center gap-2" style="width: 50%;">
                <input type="hidden" name="page" value="students">
                <div class="flex-grow-1">
                    <input type="text" class="form-control" name="search" placeholder="Search by name or ID..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="white-space: nowrap;">Search</button>
            </form>
        </div>
        <div class="card-body p-0"> <!-- Remove card body padding -->
            <div class="table-responsive" style="overflow-x: hidden;">
                <table class="table table-hover align-middle mb-0" style="min-width: 100%;">
                    <thead>
                        <tr>
                            <th class="px-2" style="min-width: 60px">ID</th>
                            <th class="px-2" style="min-width: 120px">Last Name</th>
                            <th class="px-2" style="min-width: 120px">First Name</th>
                            <th class="px-2 text-center" style="min-width: 50px">M.I.</th>
                            <th class="px-2 text-center" style="min-width: 50px">Suffix</th>
                            <th class="px-2 text-center" style="min-width: 80px">Gender</th>
                            <th class="px-2" style="min-width: 100px">Birthdate</th>
                            <th class="px-2 text-center" style="min-width: 50px">Age</th>
                            <th class="px-2 text-center" style="min-width: 120px">Contact</th>
                            <th class="px-2 text-center" style="min-width: 200px">Email</th>
                            <th class="px-2 text-center" style="min-width: 150px">Password</th> <!-- Adjusted width -->
                            <th class="px-2 text-center" style="min-width: 80px">Status</th>
                            <th class="px-2 text-center" style="min-width: 100px">Degree</th>
                            <th class="px-2 text-center" style="min-width: 100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr data-student-id="<?php echo htmlspecialchars($row['s_id']); ?>">
                                    <td><?php echo htmlspecialchars($row['s_id']); ?></td>
                                    <td class="student-lname"><?php echo htmlspecialchars($row['s_lname']); ?></td>
                                    <td class="student-fname"><?php echo htmlspecialchars($row['s_fname']); ?></td>
                                    <td class="text-center student-mname"><?php echo $row['s_mname'] ? htmlspecialchars($row['s_mname'][0]) . '.' : ''; ?></td>
                                    <td class="text-center student-suffix"><?php echo htmlspecialchars($row['s_suffix'] ?? ''); ?></td>
                                    <td class="text-center student-gender"><?php echo htmlspecialchars($row['s_gender']); ?></td>
                                    <td class="student-bdate"><?php echo date('Y-m-d', strtotime($row['s_bdate'])); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['s_age']); ?></td>
                                    <td class="student-cnum text-center"><?php echo htmlspecialchars($row['s_cnum']); ?></td>
                                    <td class="text-truncate student-email"><?php echo htmlspecialchars($row['s_email']); ?></td>
                                    <td class="td-password">
                                        <div class="password-wrapper">
                                            <span class="dots">••••••••</span>
                                            <span class="real-password" style="display: none;"><?php echo htmlspecialchars($row['s_password']); ?></span>
                                            <button type="button" class="eye-button">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="text-center student-status">
                                        <span class="badge bg-<?php echo $row['s_status'] == 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($row['s_status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center student-degree"><?php echo htmlspecialchars($row['degree_code']); ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-primary px-2 btn-edit-student" data-bs-toggle="modal" data-bs-target="#editStudentModal" data-student-id="<?php echo $row['s_id']; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-danger px-2 btn-delete-student" data-student-id="<?php echo $row['s_id']; ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="13" class="text-center">No students found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addStudentForm" method="POST" novalidate>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control name-input" name="s_fname" 
                                   pattern="[A-Za-z\-\s]+" required>
                            <div class="invalid-feedback">Please enter a valid first name</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control name-input" name="s_lname" 
                                   pattern="[A-Za-z\-\s]+" required>
                            <div class="invalid-feedback">Please enter a valid last name</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control name-input" name="s_mname" 
                                   pattern="[A-Za-z\-\s]*">
                            <div class="invalid-feedback">Please enter a valid middle name</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Suffix</label>
                            <input type="text" class="form-control name-input" name="s_suffix" 
                                   pattern="[A-Za-z\-\s\.]*">
                            <div class="invalid-feedback">Please enter a valid suffix</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="s_gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Birthdate</label>
                            <input type="date" class="form-control" name="s_bdate" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" name="s_cnum" required
                                   pattern="^09[0-9]{9}$" maxlength="11"
                                   placeholder="09XXXXXXXXX"
                                   title="Please enter a valid 11-digit phone number starting with 09">
                            <div class="invalid-feedback">Please enter a valid 11-digit phone number starting with 09</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="s_email" required
                                   pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                            <div class="invalid-feedback">Please enter a valid email address</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="s_password" required>
                            <div class="invalid-feedback">Password is required</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Degree Program</label>
                            <select class="form-select" name="degree_id" required>
                                <option value="">Select Degree Program</option>
                                <?php
                                $degrees_query = "SELECT degree_id, degree_code, degree_name FROM degrees ORDER BY degree_name";
                                $degrees_result = $conn->query($degrees_query);
                                while ($degree = $degrees_result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($degree['degree_id']) . "'>" . 
                                         htmlspecialchars($degree['degree_code'] . " - " . $degree['degree_name']) . "</option>";
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">Please select a degree program</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="s_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="addStudentForm" class="btn btn-primary">Add Student</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editStudentForm">
                    <input type="hidden" name="s_id" id="edit_s_id">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="s_fname" id="edit_s_fname" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="s_lname" id="edit_s_lname" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="s_mname" id="edit_s_mname">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Suffix</label>
                            <input type="text" class="form-control" name="s_suffix" id="edit_s_suffix">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="s_gender" id="edit_s_gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Birthdate</label>
                            <input type="date" class="form-control" name="s_bdate" id="edit_s_bdate" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" name="s_cnum" id="edit_s_cnum" pattern="[0-9]{11}" maxlength="11" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="s_email" id="edit_s_email" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="s_status" id="edit_s_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Degree Program</label>
                            <select class="form-select" name="degree_id" id="edit_degree_id" required>
                                <option value="">Select Degree Program</option>
                                <?php
                                $degrees_query = "SELECT degree_id, degree_code, degree_name FROM degrees ORDER BY degree_name";
                                $degrees_result = $conn->query($degrees_query);
                                while ($degree = $degrees_result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($degree['degree_id']) . "'>" . 
                                         htmlspecialchars($degree['degree_code'] . " - " . $degree['degree_name']) . "</option>";
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">Please select a degree program</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="editStudentForm" class="btn btn-primary">Update Student</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Global table styles */
.table td, .table th {
    height: 54px !important;
    padding: 0 16px !important;
    vertical-align: middle !important;
    line-height: 1.2 !important;
}

/* Password cell specific styles */
.td-password {
    position: relative;
    padding: 0 !important;
    text-align: center;
}

.password-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 24px;
    height: 100%;
}

.dots, .real-password {
    display: inline-block;
    text-align: center;
    width: auto;
    margin: 0 auto;
}

.eye-button {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
    padding: 0;
    border: none;
    background: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Clean up hover states */
.table-hover tbody tr:hover td {
    background-color: rgba(61, 82, 160, 0.05) !important;
}

/* Add these styles */
.alert {
    min-width: 300px;
    max-width: 600px;
    border: none;
    border-left: 4px solid;
    text-align: center;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.alert-success {
    background-color: #d1e7dd;
    border-left-color: #198754;
    color: #0f5132;
}

@keyframes slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.alert.fade.show {
    animation: slideIn 0.3s ease-out;
}

/* Success message styles */
.success-message {
    background-color: #d1e7dd;
    border-left: 4px solid #198754;
    color: #0f5132;
    padding: 12px 20px;
    border-radius: 4px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    font-weight: 500;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        transform: translateY(-20px) translateX(-50%);
        opacity: 0;
    }
    to {
        transform: translateY(0) translateX(-50%);
        opacity: 1;
    }
}

/* Success message styles */
.message-notification {
    background-color: #d1e7dd;
    color: #0f5132;
    padding: 12px 24px;
    border-radius: 4px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    opacity: 0;
    transform: translateY(-20px) translateX(-50%);
    transition: all 0.3s ease;
}

.message-notification.show {
    opacity: 1;
    transform: translateY(0) translateX(-50%);
}

.message-notification i {
    font-size: 1.2em;
}

/* Alert Modal Styles */
#alertModal .modal-content {
    border-width: 2px;
}

#alertModal .modal-body i {
    display: block;
    margin: 0 auto;
}

#alertModal .modal-header {
    padding: 1rem 1rem 0;
}

#alertModal .btn-close:focus {
    box-shadow: none;
}

#alertModal p {
    color: #666;
}

/* Fix button hover effects */
.btn-group .btn {
    transition: background-color 0.2s ease, color 0.2s ease;
    transform: none !important;
}

.btn-group .btn:hover {
    transform: none !important;
}

.btn-group .btn:active {
    transform: none !important;
}

/* Update edit button hover styles */
.btn-edit-student:hover, .btn-edit-teacher:hover {
    background: linear-gradient(145deg, #2E4190 0%, #6180C8 100%) !important;
    color: white !important;
    border: none;
}

</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Bootstrap modals
    var addModal = new bootstrap.Modal(document.getElementById('addStudentModal'));
    var editModal = new bootstrap.Modal(document.getElementById('editStudentModal'));

    // Adapt JavaScript code for students
    function ucwordsWithHyphen(str) {
        return str.toLowerCase().split(/[\s-]+/).map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    }

    // Add input handlers for name fields
    $('#addStudentForm input[name="s_fname"], #addStudentForm input[name="s_lname"], #addStudentForm input[name="s_mname"], #addStudentForm input[name="s_suffix"]').on('input', function() {
        this.value = ucwordsWithHyphen(this.value);
    });

});
</script>
<!-- Update script paths to use absolute paths -->
<script src="/Project/admin/students/js/add_student.js"></script>
<script src="/Project/admin/students/js/edit_student.js"></script>
<script src="/Project/admin/students/js/delete_student.js"></script>
</body>
</html>
