// students.js
document.addEventListener('DOMContentLoaded', function() {

    // -------------------------------
    // Initialize DataTable
    // -------------------------------
    if (window.jQuery && $.fn.DataTable) {
        $('#studentsTable').DataTable();
    }

    // -------------------------------
    // Show alert messages
    // -------------------------------
    function showAlert(message, type = 'success') {
        const alertContainer = document.createElement('div');
        alertContainer.className = `alert alert-${type} alert-dismissible fade show`;
        alertContainer.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        document.querySelector('.container').prepend(alertContainer);
        setTimeout(() => alertContainer.remove(), 5000);
    }

    // -------------------------------
    // Populate Edit Modal
    // -------------------------------
    function populateEditModal(student) {
        document.getElementById('edit_id').value = student.id || '';
        document.getElementById('edit_student_id').value = student.student_id || '';
        document.getElementById('edit_first_name').value = student.first_name || '';
        document.getElementById('edit_last_name').value = student.last_name || '';
        document.getElementById('edit_email').value = student.email || '';
        document.getElementById('edit_phone').value = student.phone || '';
        document.getElementById('edit_date_of_birth').value = student.date_of_birth || '';
        document.getElementById('edit_gender').value = student.gender || '';
        document.getElementById('edit_address').value = student.address || '';
        document.getElementById('edit_class_id').value = student.class_id || '';
        document.getElementById('edit_parent_name').value = student.parent_name || '';
        document.getElementById('edit_parent_phone').value = student.parent_phone || '';
        document.getElementById('edit_admission_date').value = student.admission_date || '';
        document.getElementById('edit_status').value = student.status || 'Active';

        const preview = document.getElementById('edit_profile_preview');
        if (preview) {
            preview.innerHTML = student.profile_picture
                ? `<img src="${student.profile_picture}" style="width:100px;height:100px;object-fit:cover;border-radius:50%;">`
                : '<div class="text-muted">No Photo</div>';
        }

        const modalEl = document.getElementById('editModal');
        if (modalEl) new bootstrap.Modal(modalEl).show();
    }

    // -------------------------------
    // Handle Edit/Delete buttons
    // -------------------------------
    document.addEventListener('click', function(e) {
        // Edit
        if (e.target.closest('.btn-edit')) {
            const btn = e.target.closest('.btn-edit');
            const student = JSON.parse(btn.getAttribute('data-student') || '{}');
            populateEditModal(student);
        }

        // Delete
        if (e.target.closest('.btn-delete')) {
            const btn = e.target.closest('.btn-delete');
            const id = btn.getAttribute('data-id');
            if (id && confirm('Are you sure you want to delete this student?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.text())
                .then(res => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(res, 'text/html');
                    const alertEl = doc.querySelector('.alert');
                    if (alertEl) showAlert(alertEl.innerHTML, alertEl.classList.contains('alert-danger') ? 'danger' : 'success');
                    setTimeout(() => location.reload(), 1000);
                })
                .catch(err => showAlert('Error deleting student', 'danger'));
            }
        }
    });

    // -------------------------------
    // AJAX Add Student
    // -------------------------------
    const addForm = document.getElementById('addStudentForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(addForm);
            formData.append('action', 'add');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(res => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(res, 'text/html');
                const alertEl = doc.querySelector('.alert');
                if (alertEl) showAlert(alertEl.innerHTML, alertEl.classList.contains('alert-danger') ? 'danger' : 'success');
                addForm.reset();
                new bootstrap.Modal(document.getElementById('addModal')).hide();
                setTimeout(() => location.reload(), 1000);
            })
            .catch(err => showAlert('Error adding student', 'danger'));
        });
    }

    // -------------------------------
    // AJAX Edit Student
    // -------------------------------
    const editForm = document.getElementById('editStudentForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(editForm);
            formData.append('action', 'edit');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(res => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(res, 'text/html');
                const alertEl = doc.querySelector('.alert');
                if (alertEl) showAlert(alertEl.innerHTML, alertEl.classList.contains('alert-danger') ? 'danger' : 'success');
                new bootstrap.Modal(document.getElementById('editModal')).hide();
                setTimeout(() => location.reload(), 1000);
            })
            .catch(err => showAlert('Error updating student', 'danger'));
        });
    }

    // -------------------------------
    // Profile picture preview
    // -------------------------------
    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        if (!preview || !input.files[0]) return;

        const reader = new FileReader();
        reader.onload = e => preview.innerHTML = `<img src="${e.target.result}" style="width:100px;height:100px;object-fit:cover;border-radius:50%;">`;
        reader.readAsDataURL(input.files[0]);
    }

    const addPicInput = document.getElementById('add_profile_picture');
    if (addPicInput) addPicInput.addEventListener('change', () => previewImage(addPicInput, 'add_profile_preview'));

    const editPicInput = document.getElementById('edit_profile_picture');
    if (editPicInput) editPicInput.addEventListener('change', () => previewImage(editPicInput, 'edit_profile_preview'));

    // -------------------------------
    // Redirect to student_info.php on row/card click
    // -------------------------------
    function addRedirects() {
        document.querySelectorAll('.student-row, .student-card').forEach(item => {
            item.style.cursor = 'pointer';
            item.addEventListener('click', function(e) {
                if (e.target.closest('.btn-edit') || e.target.closest('.btn-delete')) return;

                const studentId = this.getAttribute('data-student-id');
                if(studentId) window.location.href = 'student_info.php?student_id=' + encodeURIComponent(studentId);
            });
        });
    }

    // Call after DOM loaded
    addRedirects();

});
