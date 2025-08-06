$(document).ready(function() {
    const $addForm = $('#addTeacherForm');
    if (!$addForm.length) return;

    // Name field validation
    $('.name-input').on('input', function() {
        let value = $(this).val();
        value = value.replace(/[^A-Za-z\s-]/g, '');
        value = value.toLowerCase().split(/[\s-]+/).map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
        $(this).val(value);
    });

    // Phone number validation
    $('input[name="t_cnum"]').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length > 11) {
            value = value.substring(0, 11);
        }
        $(this).val(value);
        
        if (value.length === 11 && value.startsWith('09')) {
            $(this).removeClass('is-invalid');
        } else {
            $(this).addClass('is-invalid');
        }
    });

    // Form submission
    $addForm.on('submit', function(e) {
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
            url: '/Project/admin/teachers/processes/add_teacher.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addTeacherModal').modal('hide');
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                    $addForm[0].reset();
                    showSuccessMessage('Teacher added successfully');
                    
                    // Add the new row to the table
                    const newRow = createTeacherRow(response.data);
                    if ($('#teachersTableBody tr td').text().includes('No teachers found')) {
                        $('#teachersTableBody').html(newRow);
                    } else {
                        $('#teachersTableBody').prepend(newRow);
                    }
                } else {
                    showErrorMessage(response.message || 'Failed to add teacher');
                }
            },
            error: function(xhr) {
                showErrorMessage('Server error occurred while adding teacher');
            },
            complete: function() {
                $submitBtn.prop('disabled', false);
            }
        });
    });

    // Global password toggle handler
    $(document).on('click', '.eye-button', function(e) {
        e.preventDefault();
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

    function createTeacherRow(teacher) {
        return `
            <tr data-teacher-id="${teacher.t_id}">
                <td>${teacher.t_id}</td>
                <td class="teacher-lname">${teacher.t_lname}</td>
                <td class="teacher-fname">${teacher.t_fname}</td>
                <td class="teacher-mname">${teacher.t_mname ? teacher.t_mname[0] + '.' : ''}</td>
                <td class="teacher-suffix">${teacher.t_suffix || ''}</td>
                <td class="teacher-gender">${teacher.t_gender}</td>
                <td class="teacher-bdate">${formatDate(teacher.t_bdate)}</td>
                <td class="text-center">${calculateAge(teacher.t_bdate)}</td>
                <td class="teacher-cnum">${teacher.t_cnum}</td>
                <td class="text-truncate teacher-email">${teacher.t_email}</td>
                <td class="td-password">
                    <div class="password-wrapper">
                        <span class="dots">••••••••</span>
                        <span class="real-password" style="display: none;">${teacher.t_password || ''}</span>
                        <button type="button" class="eye-button" style="border: none; background: none;">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </td>
                <td class="teacher-department">${teacher.t_department}</td>
                <td class="text-center teacher-status">
                    <span class="badge bg-${teacher.t_status === 'active' ? 'success' : 'danger'}">
                        ${capitalizeFirst(teacher.t_status)}
                    </span>
                </td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-primary px-2 btn-edit-teacher" data-bs-toggle="modal" data-bs-target="#editTeacherModal" data-teacher-id="${teacher.t_id}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-danger px-2 btn-delete-teacher" data-teacher-id="${teacher.t_id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
    }

    // Helper functions
    function showAlert(type, message) {
        const icon = type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill';
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="bi bi-${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('#alertContainer').html(alertHtml);
        setTimeout(() => {
            $('.alert').fadeOut('slow', function() { $(this).remove(); });
        }, 3000);
    }

    function showNotification(type, message) {
        const notificationHtml = `
            <span class="notification notification-${type}">
                ${message}
            </span>`;
        $('#notificationContainer').html(notificationHtml);
        setTimeout(() => $('.notification').fadeOut(), 5000);
    }

    function formatDate(dateString) {
        const [year, month, day] = dateString.split('-');
        return `${month}/${day}/${year}`;
    }

    function calculateAge(birthDate) {
        const birth = new Date(birthDate);
        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        return age;
    }

    function capitalizeFirst(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
});