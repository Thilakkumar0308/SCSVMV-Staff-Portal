<!-- ===========================
     ADD STUDENT MODAL
=========================== -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form id="addStudentForm" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Add Student</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label>Register Number</label>
              <input type="text" name="student_id" class="form-control" required
                oninvalid="this.setCustomValidity('Register Number is required')"
                oninput="this.setCustomValidity('')">
            </div>

            <div class="col-md-6">
              <label>First Name</label>
              <input type="text" name="first_name" class="form-control" required maxlength="40" pattern="[A-Za-z\s]{1,40}"
                oninvalid="this.setCustomValidity('Enter a valid first name (letters only, max 40)')"
                oninput="this.setCustomValidity('')">
            </div>

            <div class="col-md-6">
              <label>Last Name</label>
              <input type="text" name="last_name" class="form-control" required maxlength="40" pattern="[A-Za-z\s]{1,40}"
                oninvalid="this.setCustomValidity('Enter a valid last name (letters only, max 40)')"
                oninput="this.setCustomValidity('')">
            </div>

            <div class="col-md-6">
              <label>Email</label>
              <input type="email" name="email" class="form-control" required
                oninvalid="this.setCustomValidity('Enter a valid email address')"
                oninput="this.setCustomValidity('')">
            </div>

            <div class="col-md-6">
              <label>Phone</label>
              <input type="tel" name="phone" class="form-control" required maxlength="15" pattern="[0-9]{1,15}"
                oninvalid="this.setCustomValidity('Enter a valid phone number (digits only, max 15)')"
                oninput="this.setCustomValidity('')">
            </div>

            <div class="col-md-6">
              <label>Date of Birth</label>
              <input type="date" name="date_of_birth" class="form-control" id="add_dob" required
                oninvalid="this.setCustomValidity('Please select a valid Date of Birth (age 18–45 only)')"
                oninput="validateDOB(this)">
            </div>

            <div class="col-md-6">
              <label>Gender</label>
              <select name="gender" class="form-select" required
                oninvalid="this.setCustomValidity('Please select a gender')"
                oninput="this.setCustomValidity('')">
                <option value="">Select</option>
                <option>Male</option>
                <option>Female</option>
                <option>Other</option>
              </select>
            </div>

            <div class="col-md-6">
              <label>Class</label>
              <select name="class_id" class="form-select" required
                oninvalid="this.setCustomValidity('Please select a class')"
                oninput="this.setCustomValidity('')">
                <option value="">Select</option>
                <?php foreach ($classes as $c): ?>
                  <option value="<?php echo $c['id']; ?>">
                    <?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label>Admission Date</label>
              <input type="date" name="admission_date" class="form-control" id="add_admission" required
                oninvalid="this.setCustomValidity('Please select a valid admission date (1999–today)')"
                oninput="validateAdmission(this)">
            </div>

            <div class="col-md-6">
              <label>Parent Name</label>
              <input type="text" name="parent_name" class="form-control" required maxlength="40" pattern="[A-Za-z\s]{1,40}"
                oninvalid="this.setCustomValidity('Enter a valid parent name (letters only, max 40)')"
                oninput="this.setCustomValidity('')">
            </div>

            <div class="col-md-6">
              <label>Parent Phone</label>
              <input type="tel" name="parent_phone" class="form-control" required maxlength="15" pattern="[0-9]{1,15}"
                oninvalid="this.setCustomValidity('Enter a valid parent phone (digits only, max 15)')"
                oninput="this.setCustomValidity('')">
            </div>

            <div class="col-md-12">
              <label>Address</label>
              <textarea name="address" class="form-control" required
                oninvalid="this.setCustomValidity('Address is required')"
                oninput="this.setCustomValidity('')"></textarea>
            </div>

            <div class="col-md-6">
              <label>Profile Picture (optional)</label>
              <input type="file" name="profile_picture" class="form-control" id="add_profile_picture" accept="image/*">
              <div id="add_profile_preview" class="mt-2 text-center text-muted">No Photo</div>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Add Student</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- ===========================
     EDIT STUDENT MODAL
=========================== -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form id="editStudentForm" enctype="multipart/form-data">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Edit Student</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label>Register Number</label>
              <input type="text" name="student_id" id="edit_student_id" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label>First Name</label>
              <input type="text" name="first_name" id="edit_first_name" class="form-control" required maxlength="40" pattern="[A-Za-z\s]{1,40}">
            </div>
            <div class="col-md-6">
              <label>Last Name</label>
              <input type="text" name="last_name" id="edit_last_name" class="form-control" required maxlength="40" pattern="[A-Za-z\s]{1,40}">
            </div>
            <div class="col-md-6">
              <label>Email</label>
              <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label>Phone</label>
              <input type="tel" name="phone" id="edit_phone" class="form-control" required maxlength="15" pattern="[0-9]{1,15}">
            </div>
            <div class="col-md-6">
              <label>Date of Birth</label>
              <input type="date" name="date_of_birth" id="edit_date_of_birth" class="form-control" required
                oninvalid="this.setCustomValidity('Please select a valid Date of Birth (age 18–45 only)')"
                oninput="validateDOB(this)">
            </div>
            <div class="col-md-6">
              <label>Gender</label>
              <select name="gender" id="edit_gender" class="form-select" required>
                <option value="">Select</option>
                <option>Male</option>
                <option>Female</option>
                <option>Other</option>
              </select>
            </div>
            <div class="col-md-6">
              <label>Class</label>
              <select name="class_id" id="edit_class_id" class="form-select" required>
                <option value="">Select</option>
                <?php foreach ($classes as $c): ?>
                  <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label>Admission Date</label>
              <input type="date" name="admission_date" id="edit_admission_date" class="form-control" required
                oninvalid="this.setCustomValidity('Please select a valid admission date (1999–today)')"
                oninput="validateAdmission(this)">
            </div>
            <div class="col-md-6">
              <label>Parent Name</label>
              <input type="text" name="parent_name" id="edit_parent_name" class="form-control" required maxlength="40" pattern="[A-Za-z\s]{1,40}">
            </div>
            <div class="col-md-6">
              <label>Parent Phone</label>
              <input type="tel" name="parent_phone" id="edit_parent_phone" class="form-control" required maxlength="15" pattern="[0-9]{1,15}">
            </div>
            <div class="col-md-12">
              <label>Address</label>
              <textarea name="address" id="edit_address" class="form-control" required></textarea>
            </div>
            <div class="col-md-6">
              <label>Status</label>
              <select name="status" id="edit_status" class="form-select">
                <option>Active</option>
                <option>Inactive</option>
                <option>Graduated</option>
              </select>
            </div>
            <div class="col-md-6">
              <label>Profile Picture</label>
              <input type="file" name="profile_picture" id="edit_profile_picture" class="form-control" accept="image/*">
              <div id="edit_profile_preview" class="mt-2 text-center text-muted">No Photo</div>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Update Student</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function validateDOB(input) {
  const dob = new Date(input.value);
  if (!dob) return input.setCustomValidity('');

  const today = new Date();
  const age = today.getFullYear() - dob.getFullYear();
  const monthDiff = today.getMonth() - dob.getMonth();
  const dayDiff = today.getDate() - dob.getDate();
  const exactAge = monthDiff > 0 || (monthDiff === 0 && dayDiff >= 0) ? age : age - 1;

  if (exactAge < 18 || exactAge > 45) {
    input.setCustomValidity('Age must be between 18 and 45 years');
  } else {
    input.setCustomValidity('');
  }
}

function validateAdmission(input) {
  const admission = new Date(input.value);
  const minDate = new Date('1999-01-01');
  const today = new Date();

  if (admission < minDate || admission > today) {
    input.setCustomValidity('Admission date must be between 1999 and today');
  } else {
    input.setCustomValidity('');
  }
}
</script>
