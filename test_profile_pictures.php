<?php
/**
 * Test Profile Picture Functionality
 * This page demonstrates the profile picture upload and display features
 */

require_once 'includes/header.php';
require_once 'includes/upload_handler.php';

$message = '';
$message_type = '';

// Handle test upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['test_upload'])) {
    $uploader = new ProfilePictureUploader();
    $result = $uploader->uploadProfilePicture($_FILES['test_upload'], 'test');
    
    if ($result) {
        $message = 'Test upload successful! File: ' . $result;
        $message_type = 'success';
    } else {
        $message = 'Test upload failed: ' . $uploader->getErrorString();
        $message_type = 'danger';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-1 mb-2 border-bottom">
                <h1 class="h2">Profile Picture Test</h1>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header">
                    <h5>Test Upload</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="test_upload" class="form-label">Test Profile Picture Upload</label>
                            <div class="profile-picture-upload">
                                <div class="upload-area" onclick="document.getElementById('test_upload').click()">
                                    <div class="upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <div class="upload-text">Click to upload or drag and drop</div>
                                    <div class="upload-hint">PNG, JPG, GIF, WebP, SVG up to 2MB</div>
                                </div>
                                <input type="file" id="test_upload" name="test_upload" accept="image/*" style="display: none;">
                                <div class="profile-picture-preview" style="display: none; margin-top: 15px;">
                                    <div class="profile-picture-placeholder">Preview</div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Test Upload</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header">
                    <h5>Default Avatar</h5>
                </div>
                <div class="card-body text-center">
                    <img src="<?php echo getProfilePictureUrl(''); ?>" alt="Default Avatar" class="profile-picture-large">
                    <p class="mt-3">Default profile picture displayed when no image is uploaded</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h5>Profile Picture Sizes</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <img src="<?php echo getProfilePictureUrl(''); ?>" alt="Small" class="profile-picture-small">
                            <p class="mt-2">Small (40px)</p>
                        </div>
                        <div class="col-md-3">
                            <img src="<?php echo getProfilePictureUrl(''); ?>" alt="Medium" class="profile-picture">
                            <p class="mt-2">Medium (50px)</p>
                        </div>
                        <div class="col-md-3">
                            <img src="<?php echo getProfilePictureUrl(''); ?>" alt="Large" class="profile-picture-medium">
                            <p class="mt-2">Large (80px)</p>
                        </div>
                        <div class="col-md-3">
                            <img src="<?php echo getProfilePictureUrl(''); ?>" alt="Extra Large" class="profile-picture-large">
                            <p class="mt-2">Extra Large (120px)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h5>Features Implemented</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">✅ Secure file upload with validation</li>
                        <li class="list-group-item">✅ File type validation (JPEG, PNG, GIF, WebP, SVG)</li>
                        <li class="list-group-item">✅ File size validation (2MB limit)</li>
                        <li class="list-group-item">✅ Image resizing and optimization</li>
                        <li class="list-group-item">✅ Default avatar for users without photos</li>
                        <li class="list-group-item">✅ Multiple profile picture sizes</li>
                        <li class="list-group-item">✅ Drag and drop upload interface</li>
                        <li class="list-group-item">✅ Preview functionality</li>
                        <li class="list-group-item">✅ Database integration</li>
                        <li class="list-group-item">✅ Secure file storage with .htaccess protection</li>
                        <li class="list-group-item">✅ Automatic cleanup on student deletion</li>
                        <li class="list-group-item">✅ Consistent styling with global theme</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
