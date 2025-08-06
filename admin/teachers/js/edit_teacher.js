$(document).ready(function() {
    const $editForm = $('#editTeacherForm');
    if (!$editForm.length) return;

    // Remove ALL existing handlers
    $(document).off('click', '.btn-edit-teacher');
    $(document).off('submit', '#editTeacherForm');
    $editForm.off('submit');
    $('#editTeacherModal').off('hidden.bs.modal');

    // Handle edit button click
    $(document).on('click', '.btn-edit-teacher', function(e) {
        e.preventDefault();
        const teacherId = $(this).data('teacher-id');
        loadTeacherData(teacherId);
    });

    function loadTeacherData(id) {
        $.ajax({
            url: '/Project/admin/teachers/processes/get_teacher.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    populateForm(response.data);
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Failed to load teacher data');
            }
        });
    }

    function populateForm(data) {
        $('#edit_t_id').val(data.t_id);
        $('#edit_t_fname').val(data.t_fname);
        $('#edit_t_lname').val(data.t_lname);
        $('#edit_t_mname').val(data.t_mname);
        $('#edit_t_suffix').val(data.t_suffix);
        $('#edit_t_gender').val(data.t_gender);
        $('#edit_t_bdate').val(data.t_bdate);
        $('#edit_t_cnum').val(data.t_cnum);
        $('#edit_t_email').val(data.t_email);
        $('#edit_t_department').val(data.t_department);
        $('#edit_t_status').val(data.t_status);
    }

    // Handle form submission
    $editForm.on('submit', function(e) {
        e.preventDefault();
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        const $submitBtn = $(this).find('button[type="submit"]');
        const formData = new FormData(this);
        
        $submitBtn.prop('disabled', true);

        $.ajax({
            url: '/Project/admin/teachers/processes/update_teacher.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#editTeacherModal').modal('hide');
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                    showSuccessMessage('Teacher updated successfully');
                    updateTableRow(response.data);
                } else {
                    showErrorMessage(response.message || 'Failed to update teacher');
                }
            },
            error: function(xhr) {
                showErrorMessage('Server error occurred while updating teacher');
            },
            complete: function() {
                $submitBtn.prop('disabled', false);
            }
        });
    });

    function updateTableRow(data) {
        const $row = $(`tr[data-teacher-id="${data.t_id}"]`);
        if ($row.length) {
            $row.find('.teacher-lname').text(data.t_lname);
            $row.find('.teacher-fname').text(data.t_fname);
            $row.find('.teacher-mname').text(data.t_mname ? data.t_mname[0] + '.' : '');
            $row.find('.teacher-suffix').text(data.t_suffix || '');
            $row.find('.teacher-gender').text(data.t_gender);
            $row.find('.teacher-bdate').text(formatDateForDisplay(data.t_bdate));
            $row.find('.teacher-cnum').text(data.t_cnum);
            $row.find('.teacher-email').text(data.t_email);
            $row.find('.teacher-department').text(data.t_department);
            const status = data.t_status;
            $row.find('.teacher-status').html(`<span class="badge bg-${status === 'active' ? 'success' : 'danger'}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`);
        } else {
            refreshTable();
        }
    }

    // Remove the password toggle functionality section since it's now handled globally
    
    // Helper functions same as in add_teacher.js
    function showAlert(type, message) {
        // Clear existing alerts first
        $('#alertContainer, #messageContainer').empty();
        
        const icon = type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill';
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="bi bi-${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        const $alert = $(alertHtml).appendTo('#alertContainer');
        
        // Remove alert after delay or when clicking edit button
        const alertTimeout = setTimeout(() => {
            $alert.fadeOut('slow', function() { $(this).remove(); });
        }, 3000);

        // Clear alert immediately when clicking edit button
        $(document).on('click', '.btn-edit-teacher', function() {
            clearTimeout(alertTimeout);
            $alert.remove();
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
        });
    }
});