<?php
class ProfilePictureUploader {
    private $uploadDir = 'uploads/profile_pictures/';
    private $maxFileSize = 2 * 1024 * 1024; // 2MB
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    private $errorString = '';

    public function uploadProfilePicture($file, $studentId = null) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errorString = 'File upload error';
            return false;
        }

        if ($file['size'] > $this->maxFileSize) {
            $this->errorString = 'File size exceeds 2MB';
            return false;
        }

        if (!in_array($file['type'], $this->allowedTypes)) {
            $this->errorString = 'Invalid file type';
            return false;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = ($studentId ?? time()) . '_' . uniqid() . '.' . $ext;
        $destination = $this->uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->errorString = 'Failed to move uploaded file';
            return false;
        }

        return $filename;
    }

    public function deleteProfilePicture($filename) {
        $path = $this->uploadDir . $filename;
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function getErrorString() {
        return $this->errorString;
    }
}

/**
 * Return full URL for profile picture, or default if empty
 */
function getProfilePictureUrl($filename) {
    if (!$filename || $filename == "null" || $filename == "undefined") {
        return 'uploads/profile_pictures/default.svg';
    }
    return 'uploads/profile_pictures/' . $filename;
}
?>
