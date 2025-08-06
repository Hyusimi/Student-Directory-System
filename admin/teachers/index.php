<?php
// ...existing code adapted from students/index.php...

// Update session check to be consistent with admin/dashboard.php
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: /Project/admin/login.php");
    exit;
}

require_once __DIR__ . '/../../includes/db.php';

// Update form actions to use absolute paths
$base_url = '/Project/admin/teachers/processes';

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = $search ? "WHERE t_fname LIKE '%$search%' OR t_lname LIKE '%$search%' OR t_id LIKE '%$search%'" : '';

// Update the SQL query to select teacher data
$sql = "SELECT t_id, t_fname, t_lname, t_mname, t_suffix, t_gender, t_bdate, t_age, t_cnum, t_email, t_password, t_department, t_status 
        FROM teachers 
        $search_condition 
        ORDER BY t_lname ASC";
$result = $conn->query($sql);
?>

<div class="container-fluid p-0">
    <div id="messageContainer" class="position-fixed start-50 translate-middle-x" style="z-index: 1060; top: 20px;"></div>
    <div id="notificationContainer" class="position-fixed start-50 translate-middle-x" style="z-index: 1060; top: 20px;"></div>
    <div id="alertContainer" class="position-fixed start-50 translate-middle-x" style="z-index: 1060; top: 20px;"></div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Manage Teachers</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="?page=dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active">Teachers</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
            <i class="bi bi-plus-lg me-2"></i>Add New Teacher
        </button>
    </div>

    <!-- Search and Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Teachers List</h5>
            <form id="searchForm" class="d-flex align-items-center gap-2" style="width: 50%;">
                <input type="hidden" name="page" value="teachers">
                <div class="flex-grow-1">
                    <input type="text" class="form-control" name="search" placeholder="Search by name or ID..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="white-space: nowrap;">Search</button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="overflow-x: hidden;">
                <table class="table table-hover align-middle mb-0" style="min-width: 100%;">
                    <thead>
                        <tr>
                            <th class="px-2" style="min-width: 50px">ID</th> <!-- Reduced from 60px -->
                            <th class="px-2" style="min-width: 100px">Last Name</th> <!-- Reduced from 120px -->
                            <th class="px-2" style="min-width: 100px">First Name</th> <!-- Reduced from 120px -->
                            <th class="px-2" style="min-width: 30px">M.I.</th> <!-- Reduced from 40px -->
                            <th class="px-2" style="min-width: 30px">Suffix</th> <!-- Reduced from 40px -->
                            <th class="px-2" style="min-width: 60px">Gender</th> <!-- Reduced from 70px -->
                            <th class="px-2 text-center" style="min-width: 80px">Birthdate</th>
                            <th class="px-2 text-center" style="min-width: 30px">Age</th>
                            <th class="px-2 text-center" style="min-width: 100px">Contact</th>
                            <th class="px-2 text-center" style="min-width: 120px">Email</th>
                            <th class="px-2 text-center" style="min-width: 80px">Password</th>
                            <th class="px-2 text-center" style="min-width: 90px">Department</th> <!-- Ensure text-center class is added -->
                            <th class="px-2 text-center" style="min-width: 60px">Status</th> <!-- Reduced from 70px -->
                            <th class="px-2 text-center" style="min-width: 80px">Actions</th> <!-- Reduced from 90px -->
                        </tr>
                    </thead>
                    <tbody id="teachersTableBody">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr data-teacher-id="<?php echo htmlspecialchars($row['t_id']); ?>">
                                    <td><?php echo htmlspecialchars($row['t_id']); ?></td>
                                    <td class="teacher-lname"><?php echo htmlspecialchars($row['t_lname']); ?></td>
                                    <td class="teacher-fname"><?php echo htmlspecialchars($row['t_fname']); ?></td>
                                    <td class="teacher-mname"><?php echo $row['t_mname'] ? htmlspecialchars($row['t_mname'][0]) . '.' : ''; ?></td>
                                    <td class="teacher-suffix"><?php echo htmlspecialchars($row['t_suffix'] ?? ''); ?></td>
                                    <td class="teacher-gender"><?php echo htmlspecialchars($row['t_gender']); ?></td>
                                    <td class="text-center"><?php echo date('Y-m-d', strtotime($row['t_bdate'])); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['t_age']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['t_cnum']); ?></td>
                                    <td class="text-center text-truncate"><?php echo htmlspecialchars($row['t_email']); ?></td>
                                    <td class="td-password">
                                        <div class="password-wrapper">
                                            <span class="dots">••••••••</span>
                                            <span class="real-password" style="display: none;"><?php echo htmlspecialchars($row['t_password']); ?></span>
                                            <button type="button" class="eye-button" style="border: none; background: none;">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="teacher-department"><?php echo htmlspecialchars($row['t_department']); ?></td>
                                    <td class="text-center teacher-status">
                                        <span class="badge bg-<?php echo $row['t_status'] == 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($row['t_status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-primary px-2 btn-edit-teacher" data-bs-toggle="modal" data-bs-target="#editTeacherModal" data-teacher-id="<?php echo $row['t_id']; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-danger px-2 btn-delete-teacher" data-teacher-id="<?php echo $row['t_id']; ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="14" class="text-center">No teachers found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTeacherModalLabel">Add New Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addTeacherForm" method="POST" novalidate>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control name-input" name="t_fname" 
                                   pattern="[A-Za-z\-\s]+" required 
                                   minlength="2" maxlength="50"
                                   title="Please enter a valid name (letters, spaces, and hyphens only)"
                                   oninput="this.value = this.value.replace(/[^A-Za-z\s-]/g, '')">
                            <div class="invalid-feedback">Please enter a valid first name</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control name-input" name="t_lname" 
                                   pattern="[A-Za-z\-\s]+" required 
                                   minlength="2" maxlength="50"
                                   title="Please enter a valid name (letters, spaces, and hyphens only)"
                                   oninput="this.value = this.value.replace(/[^A-Za-z\s-]/g, '')">
                            <div class="invalid-feedback">Please enter a valid last name</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control name-input" name="t_mname" 
                                   pattern="[A-Za-z\-\s]*" 
                                   maxlength="50"
                                   title="Letters, spaces, and hyphens only"
                                   oninput="this.value = this.value.replace(/[^A-Za-z\s-]/g, '')">
                            <div class="invalid-feedback">Please enter a valid middle name</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Suffix</label>
                            <input type="text" class="form-control name-input" name="t_suffix" 
                                   pattern="[A-Za-z\-\s\.]*" 
                                   maxlength="10"
                                   title="Letters, spaces, and dots only"
                                   oninput="this.value = this.value.replace(/[^A-Za-z\s\.]/g, '')">
                            <div class="invalid-feedback">Please enter a valid suffix</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="t_gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="invalid-feedback">Please select a gender</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Birthdate</label>
                            <input type="date" class="form-control" name="t_bdate" required>
                            <div class="invalid-feedback">Please select a birthdate</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" name="t_cnum" required
                                   pattern="^09[0-9]{9}$" maxlength="11"
                                   placeholder="09XXXXXXXXX"
                                   title="Please enter a valid 11-digit phone number starting with 09">
                            <div class="invalid-feedback">Please enter a valid 11-digit phone number starting with 09</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="t_email" required
                                   pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                            <div class="invalid-feedback">Please enter a valid email address</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="t_password" required>
                            <div class="invalid-feedback">Password is required</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="t_department" required>
                            <div class="invalid-feedback">Please enter a department</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="t_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="addTeacherForm" class="btn btn-primary">Add Teacher</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Teacher Modal -->
<div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTeacherModalLabel">Edit Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editTeacherForm" method="POST" novalidate>
                    <input type="hidden" name="t_id" id="edit_t_id">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control name-input" name="t_fname" id="edit_t_fname" required>
                            <div class="invalid-feedback">Please enter a valid first name</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control name-input" name="t_lname" id="edit_t_lname" required>
                            <div class="invalid-feedback">Please enter a valid last name</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control name-input" name="t_mname" id="edit_t_mname">
                            <div class="invalid-feedback">Please enter a valid middle name</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Suffix</label>
                            <input type="text" class="form-control name-input" name="t_suffix" id="edit_t_suffix">
                            <div class="invalid-feedback">Please enter a valid suffix</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="t_gender" id="edit_t_gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="invalid-feedback">Please select a gender</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Birthdate</label>
                            <input type="date" class="form-control" name="t_bdate" id="edit_t_bdate" required>
                            <div class="invalid-feedback">Please select a birthdate</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" name="t_cnum" id="edit_t_cnum" 
                                   pattern="^09[0-9]{9}$" maxlength="11" required>
                            <div class="invalid-feedback">Please enter a valid 11-digit phone number starting with 09</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="t_email" id="edit_t_email" required>
                            <div class="invalid-feedback">Please enter a valid email address</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="t_department" id="edit_t_department" required>
                            <div class="invalid-feedback">Please enter a department</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="t_status" id="edit_t_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="editTeacherForm" class="btn btn-primary">Update Teacher</button>
            </div>
        </div>
    </div>
</div>

<!-- Include necessary scripts and styles -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Bootstrap modals
    var addModal = new bootstrap.Modal(document.getElementById('addTeacherModal'));
    var editModal = new bootstrap.Modal(document.getElementById('editTeacherModal'));

    // Adapt JavaScript code for teachers
    function ucwordsWithHyphen(str) {
        return str.toLowerCase().split(/[\s-]+/).map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    }

    // Add input handlers for name fields
    $('#addTeacherForm input[name="t_fname"], #addTeacherForm input[name="t_lname"], #addTeacherForm input[name="t_mname"], #addTeacherForm input[name="t_suffix"]').on('input', function() {
        this.value = ucwordsWithHyphen(this.value);
    });

    // Make alert functions globally available
    window.showSuccessMessage = function(message) {
        var messageContainer = $('#messageContainer');
        messageContainer.html('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
            '<i class="bi bi-check-circle-fill me-2"></i>' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
            '</div>');
        setTimeout(function() {
            $('.alert').fadeOut('slow', function() { $(this).remove(); });
        }, 5000);
    }

    window.showErrorMessage = function(message) {
        var messageContainer = $('#messageContainer');
        messageContainer.html('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
            '<i class="bi bi-exclamation-circle-fill me-2"></i>' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
            '</div>');
        setTimeout(function() {
            $('.alert').fadeOut('slow', function() { $(this).remove(); });
        }, 5000);
    }

    // Ensure the "Update Teacher" button maintains its background color on click
    $('#editTeacherForm').on('submit', function() {
        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.css('background-color', $submitBtn.css('background-color'));
    });
});
</script>
<script src="/Project/admin/teachers/js/add_teacher.js"></script>
<script src="/Project/admin/teachers/js/edit_teacher.js"></script>
<script src="/Project/admin/teachers/js/delete_teacher.js"></script>

<style>
/* Ensure the "Update Teacher" button maintains its background color on click */
.btn-primary:active {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
}
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
    right: 5px;
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
</style>