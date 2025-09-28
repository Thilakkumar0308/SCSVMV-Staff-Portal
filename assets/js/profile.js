/* ========================================
   Profile JS - SCSVMV SMS
   ======================================== */

document.addEventListener('DOMContentLoaded', function() {
    initializeProfileDropdown();
    initializeProfilePicturePreview();
});

// -------------------------------
// Profile Dropdown Initialization
// -------------------------------
function initializeProfileDropdown() {
    const dropdownButton = document.getElementById('userDropdown');
    if (dropdownButton) {
        // Initialize Bootstrap dropdown only once
        if (!dropdownButton._dropdown) {
            dropdownButton._dropdown = new bootstrap.Dropdown(dropdownButton);
        }

        // Optional: Toggle dropdown manually on click (Bootstrap usually handles this automatically)
        dropdownButton.addEventListener('click', function(event) {
            event.preventDefault();
            dropdownButton._dropdown.toggle();
        });
    }
}

// -------------------------------
// Profile Picture Preview
// -------------------------------
function initializeProfilePicturePreview() {
    const profileInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    profileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            if (!validateProfilePicture(file)) {
                input.value = '';
                return;
            }

            const previewContainer = input.closest('.profile-picture-upload')?.querySelector('.profile-picture-preview');
            if (!previewContainer) return;

            const reader = new FileReader();
            reader.onload = function(event) {
                previewContainer.innerHTML = `<img src="${event.target.result}" alt="Preview" style="width:100px;height:100px;object-fit:cover;border-radius:50%;">`;
            };
            reader.readAsDataURL(file);
        });
    });
}

// -------------------------------
// Profile Picture Validation
// -------------------------------
function validateProfilePicture(file) {
    const maxSize = 2 * 1024 * 1024; // 2MB
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

    if (file.size > maxSize) {
        showAlert('File size must be less than 2MB', 'danger');
        return false;
    }

    if (!allowedTypes.includes(file.type)) {
        showAlert('Only JPEG, PNG, GIF, and WebP images are allowed', 'danger');
        return false;
    }

    return true;
}

// -------------------------------
// Reset Profile Picture
// -------------------------------
function resetProfilePictureUpload(input) {
    input.value = '';
    const preview = input.closest('.profile-picture-upload')?.querySelector('.profile-picture-preview');
    if (preview) {
        preview.innerHTML = '<div class="text-muted">No image selected</div>';
    }
}

// -------------------------------
// Alert Utility
// -------------------------------
function showAlert(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    const container = document.querySelector('.container-fluid') || document.body;
    container.insertAdjacentHTML('afterbegin', alertHtml);

    setTimeout(() => {
        const alertElement = container.querySelector('.alert');
        if (alertElement) {
            const alertInstance = bootstrap.Alert.getOrCreateInstance(alertElement);
            alertInstance.close();
        }
    }, 5000);
}

// -------------------------------
// Export globally
// -------------------------------
window.validateProfilePicture = validateProfilePicture;
window.resetProfilePictureUpload = resetProfilePictureUpload;
window.showAlert = showAlert;
