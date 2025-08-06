$(document).ready(function() {
    // Delete section handler
    $(document).on('click', '.delete-section-btn', function() {
        const sectionId = $(this).data('section-id');
        const $sectionCard = $(this).closest('.col-12.col-lg-4');
        
        if (confirm('Are you sure you want to delete this section? This action cannot be undone.')) {
            $.ajax({
                url: '/Project/admin/sections/processes/delete_section.php',
                type: 'POST',
                data: JSON.stringify({ section_id: sectionId }),
                contentType: 'application/json',
                success: function(response) {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success) {
                        $sectionCard.fadeOut(300, function() {
                            $(this).remove();
                        });
                        showAlert('success', 'Section deleted successfully');
                    } else {
                        showAlert('danger', data.message || 'Failed to delete section');
                    }
                },
                error: function() {
                    showAlert('danger', 'Server error occurred while deleting section');
                }
            });
        }
    });

    // Assign/Edit Advisor Modal Handler
    $('#assignAdvisorModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const sectionId = button.data('section-id');
        const sectionCode = button.data('section-code');
        const currentAdvisorId = button.data('advisor-id');
        
        // Store section info in the form
        $('#advisorSectionId').val(sectionId);
        $('#advisorSectionCode').val(sectionCode);
        
        // Fetch available teachers
        $.ajax({
            url: '/Project/admin/sections/processes/get_available_teachers.php',
            type: 'GET',
            success: function(response) {
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success) {
                    const $select = $('#teacherSelect');
                    $select.empty().append('<option value="">Choose an advisor...</option>');
                    
                    data.teachers.forEach(teacher => {
                        const selected = teacher.t_id === currentAdvisorId ? 'selected' : '';
                        $select.append(`
                            <option value="${teacher.t_id}" ${selected}>
                                ${teacher.t_lname}, ${teacher.t_fname} ${teacher.t_mname ? teacher.t_mname.charAt(0) + '.' : ''}
                            </option>
                        `);
                    });
                } else {
                    showAlert('danger', data.message || 'Failed to load available teachers');
                }
            },
            error: function() {
                showAlert('danger', 'Server error occurred while loading teachers');
            }
        });
    });

    // Assign Advisor Form Submit Handler
    $('#assignAdvisorForm').on('submit', function(e) {
        e.preventDefault();
        const sectionId = $('#advisorSectionId').val();
        const advisorId = $('#teacherSelect').val();

        if (!sectionId || !advisorId) {
            showAlert('danger', 'Please select an advisor');
            return;
        }

        const data = {
            section_id: sectionId,
            advisor_id: advisorId
        };

        $.ajax({
            url: '/Project/admin/sections/processes/assign_advisor.php',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            success: function(response) {
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success && data.advisor_name) {
                    // Update the advisor display in the section card
                    const $advisorContainer = $(`.advisor-container[data-section-id="${sectionId}"]`);
                    const advisorHtml = `
                        <div class="advisor-name">
                            <span class="text-primary">${data.advisor_name}</span>
                            <div class="btn-group btn-group-sm ms-2">
                                <button type="button" 
                                        class="btn btn-primary px-2 edit-advisor-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#assignAdvisorModal"
                                        data-section-id="${sectionId}"
                                        data-advisor-id="${data.t_id}"
                                        data-mode="edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" 
                                        class="btn btn-danger delete-advisor-btn"
                                        data-section-id="${sectionId}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    $advisorContainer.html(advisorHtml);
                    
                    $('#assignAdvisorModal').modal('hide');
                    showAlert('success', 'Advisor assigned successfully');
                } else {
                    showAlert('danger', data.message || 'Failed to assign advisor');
                }
            },
            error: function() {
                showAlert('danger', 'Server error occurred while assigning advisor');
            }
        });
    });

    // Unassign advisor handler
    $(document).on('click', '.delete-advisor-btn', function() {
        const sectionId = $(this).data('section-id');
        const $advisorContainer = $(this).closest('.advisor-container');
        
        if (confirm('Are you sure you want to remove this advisor from the section?')) {
            $.ajax({
                url: '/Project/admin/sections/processes/unassign_advisor.php',
                type: 'POST',
                data: JSON.stringify({ section_id: sectionId }),
                contentType: 'application/json',
                success: function(response) {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success) {
                        $advisorContainer.html(`
                            <div class="advisor-name">
                                <span class="text-muted me-2">No advisor assigned</span>
                                <button type="button" 
                                        class="btn btn-sm btn-success assign-advisor-btn"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#assignAdvisorModal"
                                        data-section-id="${sectionId}"
                                        data-mode="add">
                                    <i class="bi bi-person-plus"></i>
                                </button>
                            </div>
                        `);
                        showAlert('success', 'Advisor unassigned successfully');
                    } else {
                        showAlert('danger', data.message || 'Failed to unassign advisor');
                    }
                },
                error: function() {
                    showAlert('danger', 'Server error occurred while unassigning advisor');
                }
            });
        }
    });

    // Helper function to show alerts
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        alertDiv.style.zIndex = '1050';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 3000);
    }
});