<?php
require_once 'includes/header.php';

// Get user details (including last login)
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, full_name, email, role, created_at, last_login FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Format Last Login
if (!isset($user['last_login']) || empty($user['last_login'])) {
    $lastLoginStr = 'Never';
} else {
    try {
        $dt = new DateTime($user['last_login'], new DateTimeZone('UTC')); // assuming UTC in DB
        $dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $lastLoginStr = $dt->format('d M Y, h:i A'); // e.g., 05 Oct 2025, 10:30 AM
    } catch (Exception $e) {
        $lastLoginStr = 'Never';
    }
}
?>

<!-- External Profile CSS -->
<link rel="stylesheet" href="assets/css/profile.css">

<div class="container profile-container py-4">
    <div class="profile-card shadow-sm">
        <!-- Header -->
        <div class="profile-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <div class="profile-info ms-3">
                    <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($user['role']); ?></p>
                    <small>Member since: <?php echo date("M Y", strtotime($user['created_at'])); ?></small>
                </div>
            </div>
            <button class="btn-edit" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                <i class="fas fa-user-edit me-2"></i>Edit Profile
            </button>
        </div>

        <!-- Info Section -->
        <div class="row g-4 mt-3">
            <div class="col-md-6">
                <div class="info-card">
                    <i class="fas fa-user-circle"></i>
                    <strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-card">
                    <i class="fas fa-envelope"></i>
                    <strong>Email:</strong>
                    <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" class="text-decoration-none text-light">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-card">
                    <i class="fas fa-id-badge"></i>
                    <strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-card">
                    <i class="fas fa-clock"></i>
                    <strong>Last Login:</strong> <?php echo $lastLoginStr; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="editProfileModalLabel">
                    <i class="fas fa-user-edit me-2"></i>Edit Profile
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="update_profile.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="full_name" class="form-control" 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
