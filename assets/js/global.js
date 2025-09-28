/* ===============================
   Global JavaScript - Student Management System
   =============================== */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeGlobalFeatures();
    initializeDepartmentManagement();
    initializeStudentManagement();
});

/* -------------------------------
   Global Initialization
---------------------------------*/
function initializeGlobalFeatures() {
    initializePasswordToggle();
    initializeTooltips();
    initializeAlerts();
    initializeFormValidation();
    initializeDataTables();
    initializeProfilePictureUpload();
}

/* -------------------------------
   Student Management Initialization
---------------------------------*/
function initializeStudentManagement() {
    initializeStudentsDataTable();
    initializeStudentModals();
    initializeStudentEventHandlers();
}

function initializeStudentsDataTable() {
    const studentsTable = document.getElementById('studentsTable');
    if (studentsTable && typeof $ !== 'undefined' && $.fn.DataTable) {
        $(studentsTable).DataTable({
            pageLength: 25,
            responsive: true,
            order: [[0, "desc"]],
            language: {
                lengthMenu: "Show _MENU_ entries",
                zeroRecords: "No students found",
                info: "Showing _START_ to _END_ of _TOTAL_ students",
                infoEmpty: "Showing 0 to 0 of 0 students",
                infoFiltered: "(filtered from _MAX_ total students)",
                search: "Search students:",
                paginate: { first: "First", last: "Last", next: "Next", previous: "Previous" }
            }
        });
    }
}

function initializeStudentModals() {
    const addStudentForm = document.querySelector('.add-student-form');
    if (addStudentForm) addStudentForm.addEventListener('submit', e => { if (!validateStudentForm(addStudentForm)) e.preventDefault(); });

    const editStudentForm = document.querySelector('.edit-student-form');
    if (editStudentForm) editStudentForm.addEventListener('submit', e => { if (!validateStudentForm(editStudentForm)) e.preventDefault(); });

    const addProfilePicture = document.getElementById('profile_picture');
    if (addProfilePicture) addProfilePicture.addEventListener('change', function() { previewStudentProfilePicture(this, 'add_profile_preview'); });

    const editProfilePicture = document.getElementById('edit_profile_picture');
    if (editProfilePicture) editProfilePicture.addEventListener('change', function() { previewStudentProfilePicture(this, 'edit_profile_preview'); });
}

function initializeStudentEventHandlers() {
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-edit-student')) {
            const student = JSON.parse(e.target.closest('.btn-edit-student').getAttribute('data-student'));
            editStudent(student);
        }
        if (e.target.closest('.btn-delete-student')) {
            const button = e.target.closest('.btn-delete-student');
            deleteStudent(button.getAttribute('data-id'), button.getAttribute('data-name') || 'this student');
        }
    });
}

/* -------------------------------
   Student-specific Functions
---------------------------------*/
function editStudent(student) {
    const fields = ['id','student_id','first_name','last_name','email','phone','date_of_birth','gender','address','class_id','parent_name','parent_phone','admission_date','status'];
    fields.forEach(f => {
        const el = document.getElementById(`edit_${f}`);
        if(el) el.value = student[f] || (f==='status'?'Active':'');
    });

    const preview = document.getElementById('edit_profile_preview');
    if (preview) preview.innerHTML = student.profile_picture ? 
        `<img src="${buildProfilePictureUrl(student.profile_picture)}" style="width:100px;height:100px;object-fit:cover;border-radius:50%;" alt="Current Photo">` :
        '<div class="profile-picture-placeholder text-muted">No Photo</div>';

    const fileInput = document.getElementById('edit_profile_picture');
    if(fileInput) fileInput.value = '';

    const modal = new bootstrap.Modal(document.getElementById('editStudentModal'));
    modal.show();
}

function deleteStudent(studentId, studentName = 'this student') {
    if(confirm(`Are you sure you want to delete ${studentName}? This action cannot be undone.`)) {
        document.getElementById('delete_id').value = studentId;
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }
}

function buildProfilePictureUrl(filename) {
    if(!filename || filename==='null' || filename==='undefined') return 'uploads/profile_pictures/default.svg';
    const lower = String(filename).toLowerCase();
    if(lower.startsWith('http://') || lower.startsWith('https://') || lower.startsWith('/') || lower.includes('uploads/')) return filename;
    return 'uploads/profile_pictures/' + filename;
}

/* -------------------------------
   Department Management Initialization
---------------------------------*/
function initializeDepartmentManagement() {
    initializeDepartmentsDataTable();
    initializeDepartmentModals();
}

function initializeDepartmentsDataTable() {
    const departmentsTable = document.getElementById('departmentsTable');
    if(departmentsTable && typeof $ !== 'undefined' && $.fn.DataTable) {
        $(departmentsTable).DataTable({
            pageLength: 25,
            order: [[0, "asc"]],
            columnDefs: [{ orderable: false, targets: 7 }],
            language: {
                lengthMenu: "Show _MENU_ entries",
                zeroRecords: "No departments found",
                info: "Showing _START_ to _END_ of _TOTAL_ departments",
                infoEmpty: "Showing 0 to 0 of 0 departments",
                infoFiltered: "(filtered from _MAX_ total departments)",
                search: "Search departments:",
                paginate: { first:"First", last:"Last", next:"Next", previous:"Previous" }
            }
        });
    }
}

function initializeDepartmentModals() {
    const addDeptForm = document.querySelector('.add-department-form');
    if(addDeptForm) addDeptForm.addEventListener('submit', e => { if(!validateDepartmentForm(addDeptForm)) e.preventDefault(); });
}

function validateDepartmentForm(form) {
    const deptName = form.querySelector('#department_name');
    if(!deptName.value.trim()) { showAlert('Department name is required','danger'); deptName.focus(); return false; }
    return true;
}

/* -------------------------------
   Password Toggle
---------------------------------*/
function initializePasswordToggle() {
    const togglePassword = document.getElementById('togglePassword');
    if(togglePassword) togglePassword.addEventListener('click', function() {
        const pwd = document.getElementById('password');
        const icon = this.querySelector('i');
        if(pwd.type==='password'){ pwd.type='text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
        else { pwd.type='password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
    });
}

/* -------------------------------
   Tooltips
---------------------------------*/
function initializeTooltips() {
    if(typeof bootstrap!=='undefined' && bootstrap.Tooltip) {
        [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(el => new bootstrap.Tooltip(el));
    }
}

/* -------------------------------
   Alert Management
---------------------------------*/
function initializeAlerts() {
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(()=>{ if(alert.classList.contains('show')) new bootstrap.Alert(alert).close(); },5000);
    });
}

function showAlert(message,type='info') {
    const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
    const container = document.querySelector('.container-fluid');
    if(container){ container.insertAdjacentHTML('afterbegin',alertHtml); initializeAlerts(); }
}

/* -------------------------------
   Form Validation
---------------------------------*/
function initializeFormValidation() {
    document.querySelectorAll('.btn-delete').forEach(btn => btn.addEventListener('click', e => {
        if(!confirm('Are you sure you want to delete this item? This action cannot be undone.')) e.preventDefault();
    }));

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', e => { if(!form.checkValidity()){ e.preventDefault(); e.stopPropagation(); } form.classList.add('was-validated'); });
    });

    document.querySelectorAll('input[type="tel"]').forEach(input => input.addEventListener('input', ()=>{ input.value = input.value.replace(/\D/g,'').substring(0,10); }));
    document.querySelectorAll('#student_id,#edit_student_id').forEach(input => input.addEventListener('input', ()=>{ input.value = input.value.toUpperCase().replace(/[^A-Z0-9]/g,''); }));

    document.querySelectorAll('input[type="email"]').forEach(input => input.addEventListener('blur', ()=> {
        const email = input.value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        input.setCustomValidity(email && !emailRegex.test(email)?'Please enter a valid email address':'');
    }));
}

/* -------------------------------
   DataTables Initialization
---------------------------------*/
function initializeDataTables() {
    document.querySelectorAll('table[id$="Table"]:not(#departmentsTable):not(#studentsTable)').forEach(table => {
        if(typeof $!=='undefined' && $.fn.DataTable){
            $(table).DataTable({
                pageLength:25,
                responsive:true,
                language:{
                    lengthMenu:"Show _MENU_ entries",
                    zeroRecords:"No matching records found",
                    info:"Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty:"Showing 0 to 0 of 0 entries",
                    infoFiltered:"(filtered from _MAX_ total entries)",
                    search:"Search:",
                    paginate:{first:"First",last:"Last",next:"Next",previous:"Previous"}
                }
            });
        }
    });
}

/* -------------------------------
   Profile Picture Upload
---------------------------------*/
function initializeProfilePictureUpload() {
    document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input=>{
        input.addEventListener('change', e=>{
            const file = e.target.files[0];
            if(file && validateProfilePicture(file)) previewProfilePicture(file,input);
        });
    });
}

function validateProfilePicture(file){
    const maxSize = 2*1024*1024;
    const allowedTypes = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
    if(file.size>maxSize){ showAlert('File size must be less than 2MB','danger'); return false; }
    if(!allowedTypes.includes(file.type)){ showAlert('Only JPEG, PNG, GIF, and WebP images are allowed','danger'); return false; }
    return true;
}

function previewProfilePicture(file,input){
    const reader = new FileReader();
    reader.onload = e=>{
        const preview = input.closest('.profile-picture-upload')?.querySelector('.profile-picture-preview');
        if(preview) preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width:100px;height:100px;object-fit:cover;border-radius:50%;">`;
    };
    reader.readAsDataURL(file);
}

function resetProfilePictureUpload(input){
    input.value='';
    const preview = input.closest('.profile-picture-upload')?.querySelector('.profile-picture-preview');
    if(preview) preview.innerHTML='<div class="text-muted">No image selected</div>';
}

/* -------------------------------
   Utility Functions
---------------------------------*/
function formatDate(dateString){ if(!dateString) return ''; return new Date(dateString).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}); }
function formatDateTime(dateString){ if(!dateString) return ''; return new Date(dateString).toLocaleString('en-US',{year:'numeric',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}); }
function calculateAge(birthDate){ if(!birthDate) return ''; const today=new Date(); const birth=new Date(birthDate); let age=today.getFullYear()-birth.getFullYear(); const monthDiff=today.getMonth()-birth.getMonth(); if(monthDiff<0||(monthDiff===0 && today.getDate()<birth.getDate())) age--; return age; }
function confirmAction(message,callback){ if(confirm(message)) callback(); }

/* -------------------------------
   Performance Utilities
---------------------------------*/
function debounce(func,wait,immediate){
    let timeout;
    return function(){
        const context=this,args=arguments;
        const later=()=>{ timeout=null; if(!immediate) func.apply(context,args); };
        const callNow=immediate && !timeout;
        clearTimeout(timeout);
        timeout=setTimeout(later,wait);
        if(callNow) func.apply(context,args);
    };
}

/* -------------------------------
   Export functions for global use
---------------------------------*/
window.showAlert = showAlert;
window.formatDate = formatDate;
window.formatDateTime = formatDateTime;
window.calculateAge = calculateAge;
window.confirmAction = confirmAction;
window.editStudent = editStudent;
window.deleteStudent = deleteStudent;
window.validateProfilePicture = validateProfilePicture;
window.resetProfilePictureUpload = resetProfilePictureUpload;
window.buildProfilePictureUrl = buildProfilePictureUrl;
