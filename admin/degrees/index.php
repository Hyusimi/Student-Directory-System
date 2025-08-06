<?php
require_once __DIR__ . '/../../includes/db.php';
?>
<link rel="stylesheet" href="../css/common.css">
<style>
/* Main content area fix */
body {
    overflow-x: hidden;
    background-color: #f0f2f5;
    margin: 0;
    padding: 0;
}

main {
    min-height: 100vh;
    margin: 0;
    padding: 1rem;
    background-color: #f0f2f5;
}

/* Container and row adjustments */
.container-fluid {
    padding: 0 15px;
    margin-right: auto;
    margin-left: auto;
}

.row {
    margin-right: -15px;
    margin-left: -15px;
    display: flex;
    flex-wrap: wrap;
}

.col {
    padding: 10px;
}

/* Degree specific styles */
.degree-card {
    transition: transform 0.2s;
    margin-bottom: 1rem;
    width: 100%;
    background-color: #ffffff;
}

.degree-card:hover {
    transform: translateY(-2px);
}

.sections-list {
    max-height: 200px;
    overflow-y: auto;
    margin-top: 1rem;
    width: 100%;
}

.sections-list::-webkit-scrollbar {
    width: 6px;
}

.stat-box {
    background: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.2s;
    width: 100%;
}

.stat-box:hover {
    background: #e9ecef;
}

.stat-box i {
    font-size: 1.5rem;
    margin-right: 0.5rem;
}

.degree-description {
    max-height: 100px;
    overflow-y: auto;
    margin-bottom: 1rem;
    width: 100%;
}

/* Card body adjustments */
.card-body {
    padding: 1rem;
    overflow-x: hidden;
}

/* Button adjustments */
.btn-group {
    margin: 0;
    padding: 0;
}

.btn {
    white-space: nowrap;
}
</style>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Degrees Management</title>
    <style>
        .degree-card {
            transition: transform 0.2s;
        }
        .degree-card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            border-radius: 10px;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: scale(1.02);
        }
        .sections-list {
            max-height: 200px;
            overflow-y: auto;
        }
        .sections-list::-webkit-scrollbar {
            width: 5px;
        }
        .sections-list::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .sections-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 5px;
        }
        .stat-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <!-- Main content -->
    <div class="container-fluid px-4">
        <h2>Degrees Management</h2>
        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addDegreeModal">
            <i class="bi bi-plus-lg me-2"></i>Add Degree
        </button>

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-0">
            <?php 
            // Fetch degrees with their sections and student counts
            $query = "SELECT 
                        d.degree_code,
                        d.degree_name,
                        d.description,
                        ds.section_id,
                        ds.section_code,
                        COUNT(DISTINCT ss.s_id) as student_count
                      FROM degrees d
                      LEFT JOIN degrees_sections ds ON d.degree_code = ds.degree_code
                      LEFT JOIN students_sections ss ON ds.section_id = ss.section_id
                      GROUP BY d.degree_code, ds.section_id
                      ORDER BY d.degree_code, ds.section_code";

            $result = $conn->query($query);

            // Organize data by degree
            $degrees = [];
            while ($row = $result->fetch_assoc()) {
                $degree_code = $row['degree_code'];
                if (!isset($degrees[$degree_code])) {
                    $degrees[$degree_code] = [
                        'name' => $row['degree_name'],
                        'description' => $row['description'],
                        'sections' => [],
                        'total_students' => 0
                    ];
                }
                if ($row['section_id']) {
                    $degrees[$degree_code]['sections'][] = [
                        'section_code' => $row['section_code'],
                        'student_count' => $row['student_count']
                    ];
                    $degrees[$degree_code]['total_students'] += $row['student_count'];
                }
            }
            ?>
            <?php foreach ($degrees as $degree_code => $degree) : ?>
            <div class="col">
                <div class="card h-100 shadow-sm degree-card" data-degree-code="<?php echo htmlspecialchars($degree_code); ?>" data-degree-name="<?php echo htmlspecialchars($degree['name']); ?>" data-description="<?php echo htmlspecialchars($degree['description']); ?>">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php echo htmlspecialchars($degree_code); ?></h5>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-primary px-2" data-bs-toggle="modal" data-bs-target="#editDegreeModal" 
                                    data-degree-code="<?php echo htmlspecialchars($degree_code); ?>"
                                    data-degree-name="<?php echo htmlspecialchars($degree['name']); ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-danger px-2" onclick="deleteDegree('<?php echo htmlspecialchars($degree_code); ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($degree['name']); ?></h6>
                        <p class="card-text small text-muted mb-4 degree-description"><?php echo htmlspecialchars($degree['description']); ?></p>
                        
                        <div class="row g-3 mb-4">
                            <!-- Total Sections Card -->
                            <div class="col-6">
                                <div class="card bg-light stat-card">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="card-title mb-0">Sections</h6>
                                                <h3 class="mb-0"><?php echo count($degree['sections']); ?></h3>
                                            </div>
                                            <i class="bi bi-collection stat-icon text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Total Students Card -->
                            <div class="col-6">
                                <div class="card bg-light stat-card">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="card-title mb-0">Students</h6>
                                                <h3 class="mb-0"><?php echo $degree['total_students']; ?></h3>
                                            </div>
                                            <i class="bi bi-people stat-icon text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sections List -->
                        <div class="sections-list">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Section</th>
                                        <th class="text-end">Students</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($degree['sections'] as $section) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($section['section_code']); ?></td>
                                        <td class="text-end"><?php echo $section['student_count']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add Degree Modal -->
    <div class="modal fade" id="addDegreeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Degree</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addDegreeForm" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="degree_code" class="form-label">Degree Code</label>
                            <input type="text" class="form-control" id="degree_code" name="degree_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="degree_name" class="form-label">Degree Name</label>
                            <input type="text" class="form-control" id="degree_name" name="degree_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="degree_description" class="form-label">Description</label>
                            <textarea class="form-control" id="degree_description" name="description" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="addDegreeForm" class="btn btn-primary">Add Degree</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Degree Modal -->
    <div class="modal fade" id="editDegreeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Degree</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editDegreeForm" class="needs-validation" novalidate>
                        <input type="hidden" id="edit_original_code" name="original_code">
                        <div class="mb-3">
                            <label for="edit_degree_code" class="form-label">Degree Code</label>
                            <input type="text" class="form-control" id="edit_degree_code" name="degree_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_degree_name" class="form-label">Degree Name</label>
                            <input type="text" class="form-control" id="edit_degree_name" name="degree_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="editDegreeForm" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteDegreeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Degree</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this degree? This action cannot be undone.</p>
                    <input type="hidden" id="delete_degree_code">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Add Degree
            $('#addDegreeForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'add');

                $.ajax({
                    url: '/Project/admin/ajax/degrees_ajax.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#addDegreeModal').modal('hide');
                            location.reload();
                        } else {
                            alert(response.error || 'Failed to add degree');
                        }
                    },
                    error: function(xhr) {
                        console.error('AJAX Error:', xhr);
                        let errorMessage = 'Failed to add degree';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage = response.error || errorMessage;
                        } catch (e) {
                            errorMessage = xhr.responseText || errorMessage;
                        }
                        alert(errorMessage);
                    }
                });
            });

            // Edit Degree
            $('.edit-degree').on('click', function() {
                const card = $(this).closest('.card');
                $('#edit_original_code').val(card.data('degree-code'));
                $('#edit_degree_code').val(card.data('degree-code'));
                $('#edit_degree_name').val(card.data('degree-name'));
                $('#edit_description').val(card.data('description'));
                $('#editDegreeModal').modal('show');
            });

            $('#editDegreeForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'edit');

                $.ajax({
                    url: '/Project/admin/ajax/degrees_ajax.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#editDegreeModal').modal('hide');
                            location.reload();
                        } else {
                            alert(response.error);
                        }
                    },
                    error: function(xhr) {
                        alert('Error: ' + xhr.responseJSON?.error || 'Something went wrong');
                    }
                });
            });

            // Delete Degree
            $('.delete-degree').on('click', function() {
                const degreeCode = $(this).closest('.card').data('degree-code');
                $('#delete_degree_code').val(degreeCode);
                $('#deleteDegreeModal').modal('show');
            });

            $('#confirmDelete').on('click', function() {
                const degreeCode = $('#delete_degree_code').val();
                
                $.ajax({
                    url: '/Project/admin/ajax/degrees_ajax.php',
                    type: 'POST',
                    data: {
                        action: 'delete',
                        degree_code: degreeCode
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#deleteDegreeModal').modal('hide');
                            location.reload();
                        } else {
                            alert(response.error);
                        }
                    },
                    error: function(xhr) {
                        alert('Error: ' + xhr.responseJSON?.error || 'Something went wrong');
                    }
                });
            });

            // Reset forms when modals are closed
            $('.modal').on('hidden.bs.modal', function() {
                $(this).find('form').trigger('reset');
            });
        });
    </script>
</body>
</html>