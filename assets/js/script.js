// Student Management System JavaScript

$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Confirm delete actions
    $('.btn-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
        }
    });

    // Form validation
    $('form').on('submit', function(e) {
        var form = this;
        if (form.checkValidity() === false) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });

    // Phone number formatting
    $('input[type="tel"]').on('input', function() {
        var value = this.value.replace(/\D/g, '');
        if (value.length >= 10) {
            value = value.substring(0, 10);
        }
        this.value = value;
    });

    // Student ID formatting
    $('#student_id, #edit_student_id').on('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    });

    // Email validation
    $('input[type="email"]').on('blur', function() {
        var email = this.value;
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            this.setCustomValidity('Please enter a valid email address');
        } else {
            this.setCustomValidity('');
        }
    });

    // Date validation
    $('input[type="date"]').on('change', function() {
        var date = new Date(this.value);
        var today = new Date();
        
        if (this.id.includes('birth') && date >= today) {
            this.setCustomValidity('Date of birth cannot be today or in the future');
        } else if (this.id.includes('admission') && date > today) {
            this.setCustomValidity('Admission date cannot be in the future');
        } else {
            this.setCustomValidity('');
        }
    });

    // Search functionality
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#studentsTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Export functionality
    $('#exportBtn').on('click', function() {
        var table = $('#studentsTable').DataTable();
        table.button('.buttons-csv').trigger();
    });

    // Print functionality
    $('#printBtn').on('click', function() {
        window.print();
    });

    // Refresh data
    $('#refreshBtn').on('click', function() {
        location.reload();
    });
});

// Global functions
function showAlert(message, type = 'info') {
    var alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $('.container-fluid').prepend(alertHtml);
    
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
}

function formatDate(dateString) {
    if (!dateString) return '';
    var date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatDateTime(dateString) {
    if (!dateString) return '';
    var date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function calculateAge(birthDate) {
    if (!birthDate) return '';
    var today = new Date();
    var birth = new Date(birthDate);
    var age = today.getFullYear() - birth.getFullYear();
    var monthDiff = today.getMonth() - birth.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    
    return age;
}

function validateForm(formId) {
    var form = document.getElementById(formId);
    var isValid = true;
    
    // Clear previous validation
    $(form).find('.is-invalid').removeClass('is-invalid');
    $(form).find('.invalid-feedback').remove();
    
    // Required field validation
    $(form).find('[required]').each(function() {
        if (!$(this).val()) {
            $(this).addClass('is-invalid');
            $(this).after('<div class="invalid-feedback">This field is required.</div>');
            isValid = false;
        }
    });
    
    // Email validation
    $(form).find('input[type="email"]').each(function() {
        var email = $(this).val();
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            $(this).addClass('is-invalid');
            $(this).after('<div class="invalid-feedback">Please enter a valid email address.</div>');
            isValid = false;
        }
    });
    
    // Phone validation
    $(form).find('input[type="tel"]').each(function() {
        var phone = $(this).val();
        var phoneRegex = /^\d{10}$/;
        if (phone && !phoneRegex.test(phone)) {
            $(this).addClass('is-invalid');
            $(this).after('<div class="invalid-feedback">Please enter a valid 10-digit phone number.</div>');
            isValid = false;
        }
    });
    
    return isValid;
}

function resetForm(formId) {
    var form = document.getElementById(formId);
    form.reset();
    $(form).find('.is-invalid').removeClass('is-invalid');
    $(form).find('.invalid-feedback').remove();
    $(form).removeClass('was-validated');
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// DataTable customization
function initializeDataTable(tableId, options = {}) {
    var defaultOptions = {
        "pageLength": 25,
        "responsive": true,
        "order": [[ 0, "desc" ]],
        "language": {
            "lengthMenu": "Show _MENU_ entries",
            "zeroRecords": "No matching records found",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "search": "Search:",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        },
        "dom": 'Bfrtip',
        "buttons": [
            {
                extend: 'csv',
                text: 'Export CSV',
                className: 'btn btn-sm btn-outline-primary'
            },
            {
                extend: 'print',
                text: 'Print',
                className: 'btn btn-sm btn-outline-secondary'
            }
        ]
    };
    
    var finalOptions = $.extend({}, defaultOptions, options);
    return $(tableId).DataTable(finalOptions);
}

// Chart functions
function createChart(canvasId, type, data, options = {}) {
    var ctx = document.getElementById(canvasId).getContext('2d');
    var defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            }
        }
    };
    
    var finalOptions = $.extend({}, defaultOptions, options);
    return new Chart(ctx, {
        type: type,
        data: data,
        options: finalOptions
    });
}

// Utility functions
function debounce(func, wait, immediate) {
    var timeout;
    return function() {
        var context = this, args = arguments;
        var later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

function throttle(func, limit) {
    var inThrottle;
    return function() {
        var args = arguments;
        var context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// AJAX helper functions
function ajaxRequest(url, method = 'GET', data = null, successCallback = null, errorCallback = null) {
    $.ajax({
        url: url,
        method: method,
        data: data,
        dataType: 'json',
        success: function(response) {
            if (successCallback) {
                successCallback(response);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            if (errorCallback) {
                errorCallback(xhr, status, error);
            } else {
                showAlert('An error occurred. Please try again.', 'danger');
            }
        }
    });
}

// Local storage helpers
function setLocalStorage(key, value) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (e) {
        console.error('Error saving to localStorage:', e);
    }
}

function getLocalStorage(key, defaultValue = null) {
    try {
        var item = localStorage.getItem(key);
        return item ? JSON.parse(item) : defaultValue;
    } catch (e) {
        console.error('Error reading from localStorage:', e);
        return defaultValue;
    }
}

function removeLocalStorage(key) {
    try {
        localStorage.removeItem(key);
    } catch (e) {
        console.error('Error removing from localStorage:', e);
    }
}

// ---------------------- Existing code ----------------------
// (Keep all your existing functions and $(document).ready(...) as is)

// ---------------------- Rain Effect ----------------------
(function() {
    const canvas = document.createElement('canvas');
    canvas.id = 'rain-canvas';
    document.body.appendChild(canvas);
    const ctx = canvas.getContext('2d');

    let width = canvas.width = window.innerWidth;
    let height = canvas.height = window.innerHeight;

    const drops = [];
    for(let i=0; i<300; i++){
        drops.push({
            x: Math.random()*width,
            y: Math.random()*height,
            length: 10 + Math.random()*20,
            speed: 2 + Math.random()*4,
            opacity: 0.2 + Math.random()*0.5
        });
    }

    let mouseX = width/2;
    document.addEventListener('mousemove', e => { mouseX = e.clientX; });

    function animateRain() {
        ctx.clearRect(0, 0, width, height);
        for(const drop of drops){
            drop.x += (mouseX - width/2) * 0.002;
            drop.y += drop.speed;
            if(drop.y > height){
                drop.y = -drop.length;
                drop.x = Math.random()*width;
            }
            ctx.strokeStyle = `rgba(255,255,255,${drop.opacity})`;
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(drop.x, drop.y);
            ctx.lineTo(drop.x, drop.y + drop.length);
            ctx.stroke();
        }
        requestAnimationFrame(animateRain);
    }
    animateRain();

    window.addEventListener('resize', () => {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
    });
})();
