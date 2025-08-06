$(document).ready(function() {
    const $editForm = $('#editStudentForm');
    if (!$editForm.length) return;

    // Remove ALL existing handlers
    $(document).off('click', '.btn-edit-student');
    $(document).off('submit', '#editStudentForm');
    $editForm.off('submit');
    $('#editStudentModal').off('hidden.bs.modal');

    // Handle edit button click
    $(document).on('click', '.btn-edit-student', function(e) {
        e.preventDefault();
        const studentId = $(this).data('student-id');
        loadStudentData(studentId);
    });

    function loadStudentData(id) {
        $.ajax({
            url: `/Project/admin/students/processes/get_student.php?id=${id}`,
            type: 'GET',
            dataType: 'json'
        })
        .done(function(response) {
            if (response.status === 'success') {
                populateForm(response.data);
                $('#editStudentModal').modal('show');
            } else {
                showAlert('error', response.message || 'Failed to fetch student data');
            }
        })
        .fail(function() {
            showAlert('error', 'Failed to fetch student data');
        });
    }

    function populateForm(data) {
        Object.keys(data).forEach(key => {
            $editForm.find(`[name="${key}"]`).val(data[key]);
        });
    }

    // Single form submission handler
    $editForm.on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!this.checkValidity()) {
            $(this).addClass('was-validated');
            return false;
        }

        const $submitBtn = $(this).find('button[type="submit"]');
        const formData = new FormData(this);
        
        $submitBtn.prop('disabled', true);

        $.ajax({
            url: '/Project/admin/students/processes/edit_student.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success || response.status === 'success') {
                updateTableRow(response.data);
                $('#editStudentModal').modal('hide');
                showAlert('success', 'Student updated successfully!');
                resetForm($editForm);
            } else {
                showAlert('error', response.message || 'Update failed');
            }
        })
        .fail(function(jqXHR) {
            console.error('Update failed:', jqXHR.responseText);
            showAlert('error', 'Failed to update student');
        })
        .always(function() {
            $submitBtn.prop('disabled', false);
        });

        return false;
    });

    // Reset form when modal is hidden
    $('#editStudentModal').on('hidden.bs.modal', function() {
        resetForm($editForm);
        $editForm.removeClass('was-validated');
    });

    function updateTableRow(data) {
        const $row = $(`tr[data-student-id="${data.s_id}"]`);
        if ($row.length) {
            $row.find('.student-lname').text(data.s_lname);
            $row.find('.student-fname').text(data.s_fname);
            $row.find('.student-mname').text(data.s_mname ? data.s_mname[0] + '.' : '');
            $row.find('.student-suffix').text(data.s_suffix || '');
            $row.find('.student-gender').text(data.s_gender);
            $row.find('.student-bdate').text(formatDateForDisplay(data.s_bdate));
            $row.find('.student-cnum').text(data.s_cnum || '');
            $row.find('.student-email').text(data.s_email || '');
            $row.find('.student-degree').text(data.degree_code || '');
            
            // Update status badge properly
            const $statusCell = $row.find('.student-status');
            const $statusBadge = $statusCell.find('.badge');
            $statusBadge.removeClass('bg-success bg-danger')
                .addClass(data.s_status === 'active' ? 'bg-success' : 'bg-danger')
                .text(data.s_status.charAt(0).toUpperCase() + data.s_status.slice(1));
        } else {
            refreshTable();
        }
    }

    function showAlert(type, message) {
        // Clear any existing alerts first
        $('#alertContainer').empty();
        
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

        // Clear alert when opening modal
        $(document).on('click', '.btn-edit-student', function() {
            clearTimeout(alertTimeout);
            $alert.remove();
        });
    }

    function refreshTable() {
        // Implementation same as in add_student.js
    }

    function resetForm($form) {
        $form[0].reset();
        $form.removeClass('was-validated');
    }

    // Password toggle functionality
    $(document).on('click', '.eye-button', function() {
        const $wrapper = $(this).closest('.password-wrapper');
        const $dots = $wrapper.find('.dots');
        const $password = $wrapper.find('.real-password');
        const $icon = $(this).find('i');

        if ($dots.is(':visible')) {
            $dots.hide();
            $password.show();
            $icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            $dots.show();
            $password.hide();
            $icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    // Helper function to format date for display
    function formatDateForDisplay(dateStr) {
        const [year, month, day] = dateStr.split('-');
        return `${month}/${day}/${year}`;
    }
});