<?php
require_once 'includes/header.php';

// Fetch logged-in user details
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, full_name, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<div class="container mt-4">
    <h3 class="mb-4"><i class="fas fa-id-card me-2"></i>Your Profile</h3>
    <div class="card shadow-sm p-4 text-dark">
        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
