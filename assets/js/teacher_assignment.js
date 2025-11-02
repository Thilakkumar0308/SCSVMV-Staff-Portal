// Teacher Class Assignment JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class_id');
    const subjectSelect = document.getElementById('subject_id');
    
    if (classSelect && subjectSelect) {
        // Filter subjects based on selected class
        classSelect.addEventListener('change', function() {
            const selectedClassId = this.value;
            const options = subjectSelect.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block';
                    return;
                }
                
                const subjectClassId = option.getAttribute('data-class-id');
                if (subjectClassId === selectedClassId || selectedClassId === '') {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                    if (option.selected) {
                        option.selected = false;
                        subjectSelect.value = '';
                    }
                }
            });
        });
        
        // Trigger change event on page load if class is already selected
        if (classSelect.value) {
            classSelect.dispatchEvent(new Event('change'));
        }
    }
    
    // Confirm assignment removal
    const removeForms = document.querySelectorAll('form[action="remove"]');
    removeForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to remove this assignment?')) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
