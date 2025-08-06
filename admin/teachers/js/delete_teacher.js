$(document).ready(function() {
    // Delete teacher handler
    $(document).on('click', '.btn-delete-teacher', function() {
        var teacherId = $(this).data('teacher-id');
        if (confirm('Are you sure you want to delete this teacher?')) {
            $.ajax({
                url: '/Project/admin/teachers/processes/delete_teacher.php',
                type: 'POST',
                data: { t_id: teacherId },
                dataType: 'json'
            })
            .done(function(response) {
                if (response.success) {
                    showSuccessMessage('Teacher deleted successfully');
                    $(`tr[data-teacher-id="${teacherId}"]`).remove();
                } else {
                    showErrorMessage(response.message || 'Failed to delete teacher');
                }
            })
            .fail(function(xhr) {
                console.error('Delete failed:', xhr.responseText);
                showErrorMessage('Failed to delete teacher');
            });
        }
    });
});
