<!-- Add Student Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="addStudentForm" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Student</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label>Register Number</label>
              <input type="number" name="student_id" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label>First Name</label>
              <input type="text" name="first_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label>Last Name</label>
              <input type="text" name="last_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label>Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label>Phone</label>
              <input type="text" name="phone" class="form-control">
            </div>
            <div class="col-md-6">
              <label>Date of Birth</label>
              <input type="date" name="date_of_birth" class="form-control">
            </div>
            <div class="col-md-6">
              <label>Gender</label>
              <select name="gender" class="form-control">
                <option value="">Select</option>
                <option>Male</option>
                <option>Female</option>
                <option>Other</option>
              </select>
            </div>
            <div class="col-md-6">
              <label>Class</label>
              <select name="class_id" class="form-control">
                <option value="">Select</option>
                <?php foreach($classes as $c): ?>
                  <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label>Admission Date</label>
              <input type="date" name="admission_date" class="form-control">
            </div>
            <div class="col-md-6">
              <label>Parent Name</label>
              <input type="text" name="parent_name" class="form-control">
            </div>
            <div class="col-md-6">
              <label>Parent Phone</label>
              <input type="text" name="parent_phone" class="form-control">
            </div>
            <div class="col-md-12">
              <label>Address</label>
              <textarea name="address" class="form-control"></textarea>
            </div>
            <div class="col-md-6">
              <label>Profile Picture</label>
              <input type="file" name="profile_picture" class="form-control" id="add_profile_picture">
              <div id="add_profile_preview" class="mt-2 text-center text-muted">No Photo</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Add Student</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="editStudentForm" enctype="multipart/form-data">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Student</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label>Register Number</label>
              <input type="text" name="student_id" class="form-control" id="edit_student_id" required>
            </div>
            <div class="col-md-6">
              <label>First Name</label>
              <input type="text" name="first_name" class="form-control" id="edit_first_name" required>
            </div>
            <div class="col-md-6">
              <label>Last Name</label>
              <input type="text" name="last_name" class="form-control" id="edit_last_name" required>
            </div>
            <div class="col-md-6">
              <label>Email</label>
              <input type="email" name="email" class="form-control" id="edit_email" required>
            </div>
            <div class="col-md-6">
              <label>Phone</label>
              <input type="text" name="phone" class="form-control" id="edit_phone">
            </div>
            <div class="col-md-6">
              <label>Date of Birth</label>
              <input type="date" name="date_of_birth" class="form-control" id="edit_date_of_birth">
            </div>
            <div class="col-md-6">
              <label>Gender</label>
              <select name="gender" class="form-control" id="edit_gender">
                <option value="">Select</option>
                <option>Male</option>
                <option>Female</option>
                <option>Other</option>
              </select>
            </div>
            <div class="col-md-6">
              <label>Class</label>
              <select name="class_id" class="form-control" id="edit_class_id">
                <option value="">Select</option>
                <?php foreach($classes as $c): ?>
                  <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label>Admission Date</label>
              <input type="date" name="admission_date" class="form-control" id="edit_admission_date">
            </div>
            <div class="col-md-6">
              <label>Parent Name</label>
              <input type="text" name="parent_name" class="form-control" id="edit_parent_name">
            </div>
            <div class="col-md-6">
              <label>Parent Phone</label>
              <input type="text" name="parent_phone" class="form-control" id="edit_parent_phone">
            </div>
            <div class="col-md-12">
              <label>Address</label>
              <textarea name="address" class="form-control" id="edit_address"></textarea>
            </div>
            <div class="col-md-6">
              <label>Status</label>
              <select name="status" class="form-control" id="edit_status">
                <option>Active</option>
                <option>Inactive</option>
                <option>Graduated</option>
              </select>
            </div>
            <div class="col-md-6">
              <label>Profile Picture</label>
              <input type="file" name="profile_picture" class="form-control" id="edit_profile_picture">
              <div id="edit_profile_preview" class="mt-2 text-center text-muted">No Photo</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Update Student</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Custom Styles for Validation -->
<style>
  .text-danger {
    font-size: 0.8rem;
    margin-top: 2px;
  }
  .is-invalid {
    border-color: #dc3545 !important;
    box-shadow: none !important;
  }
  .form-control:focus {
    box-shadow: none;
    border-color: #86b7fe;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

  // Restrict inputs for phone numbers to numbers only (live restriction)
  const phoneFields = document.querySelectorAll('input[name="phone"], input[name="parent_phone"]');
  phoneFields.forEach(input => {
    input.setAttribute('maxlength', '10'); // Max 10 digits
    input.addEventListener('input', function () {
      this.value = this.value.replace(/[^0-9]/g, '');
    });
  });

  // Helper for showing errors
  const showError = (input, message) => {
    input.classList.add('is-invalid');
    let error = input.parentElement.querySelector('.text-danger');
    if (!error) {
      error = document.createElement('small');
      error.className = 'text-danger';
      input.parentElement.appendChild(error);
    }
    error.innerText = message;
  };

  const clearErrors = (form) => {
    form.querySelectorAll('.text-danger').forEach(err => err.remove());
    form.querySelectorAll('.is-invalid').forEach(field => field.classList.remove('is-invalid'));
  };

  // Main validation function
  const validateForm = (form) => {
    clearErrors(form);
    let isValid = true;

    // Required fields check
    form.querySelectorAll('[required]').forEach(input => {
      if (!input.value.trim()) {
        isValid = false;
        showError(input, `${input.previousElementSibling.innerText} is required`);
      }
    });

    // Email validation
    const emailInput = form.querySelector('input[type="email"]');
    if (emailInput && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
      isValid = false;
      showError(emailInput, 'Invalid email address');
    }

    // Phone number validation (10 digits)
    form.querySelectorAll('input[name="phone"], input[name="parent_phone"]').forEach(phoneInput => {
      if (phoneInput.value && phoneInput.value.length !== 10) {
        isValid = false;
        showError(phoneInput, 'Phone number must be 10 digits');
      }
    });

    // Date of Birth future validation
    const dobInput = form.querySelector('input[name="date_of_birth"]');
    if (dobInput && dobInput.value && new Date(dobInput.value) > new Date()) {
      isValid = false;
      showError(dobInput, 'DOB cannot be in the future');
    }

    // File upload validation (image types)
    const fileInput = form.querySelector('input[type="file"]');
    if (fileInput && fileInput.files.length > 0) {
      const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
      if (!allowedTypes.includes(fileInput.files[0].type)) {
        isValid = false;
        showError(fileInput, 'Only JPG, JPEG, or PNG formats allowed');
      }
    }

    return isValid;
  };

  // Bind to forms
  document.getElementById('addStudentForm').addEventListener('submit', function (e) {
    if (!validateForm(this)) e.preventDefault();
  });
  document.getElementById('editStudentForm').addEventListener('submit', function (e) {
    if (!validateForm(this)) e.preventDefault();
  });

  // Optional: Clear validation on modal close
  document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('hidden.bs.modal', function () {
      const form = modal.querySelector('form');
      if (form) clearErrors(form);
    });
  });
});
</script>
