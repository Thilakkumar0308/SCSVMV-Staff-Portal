// ------------------------------
// ON-DUTY MANAGEMENT JS
// ------------------------------

document.addEventListener('DOMContentLoaded', function() {

    // --------------------------
    // Initialize On-Duty DataTable
    // --------------------------
    const ondutyTable = document.getElementById('ondutyTable');
    if(ondutyTable && typeof $ !== 'undefined' && $.fn.DataTable){
        $(ondutyTable).DataTable({
            pageLength: 25,
            order: [[1,'asc']], // Sort by Student Name
            language: { emptyTable: "No records found" },
            columnDefs: [{ orderable: false, targets: 8 }] // Actions column
        });
    }

    // --------------------------
    // Approve / Reject / Delete Actions
    // --------------------------
    window.approveOnduty = function(id){
        const approveIdInput = document.getElementById('approve_id');
        if(approveIdInput){
            approveIdInput.value = id;
            const modal = new bootstrap.Modal(document.getElementById('approveModal'));
            modal.show();
        }
    };

    window.rejectOnduty = function(id){
        const rejectIdInput = document.getElementById('reject_id');
        if(rejectIdInput){
            rejectIdInput.value = id;
            const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
            modal.show();
        }
    };

    window.deleteOnduty = function(id){
        const deleteIdInput = document.getElementById('delete_id');
        if(deleteIdInput){
            deleteIdInput.value = id;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
    };

    // --------------------------
    // Student Dropdown (Old-Style)
    // --------------------------
    // Native <select> supports typing first letter or register number
    const studentSelect = document.getElementById('student_id');
    if(studentSelect){
        studentSelect.addEventListener('input', function(){
            // Optional: convert to uppercase for consistent search
            this.value = this.value.toUpperCase();
        });
    }

    // --------------------------
    // Event Date Validation
    // --------------------------
    const eventDateInput = document.querySelector('input[name="event_date"]');
    if(eventDateInput){
        eventDateInput.addEventListener('change', function(){
            const selectedDate = new Date(this.value);
            if(isNaN(selectedDate)){
                window.showAlert('Invalid date selected','danger');
                this.value = '';
            }
        });
    }

});
