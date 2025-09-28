<?php
session_start();
require_once 'config/db.php';

$error = '';
$success = '';
$token = isset($_GET['token']) ? sanitize_input($_GET['token']) : '';

if (empty($token)) {
	$error = 'Invalid or missing token';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = isset($_POST['token']) ? sanitize_input($_POST['token']) : '';
	$new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
	$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

	if (empty($token) || empty($new_password) || empty($confirm_password)) {
		$error = 'Please fill in all fields';
	} elseif ($new_password !== $confirm_password) {
		$error = 'Passwords do not match';
	} elseif (strlen($new_password) < 6) {
		$error = 'Password must be at least 6 characters';
	} else {
		$stmt = $conn->prepare("SELECT pr.id, pr.user_id FROM password_resets pr WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0");
		$stmt->bind_param("s", $token);
		$stmt->execute();
		$res = $stmt->get_result();

		if ($row = $res->fetch_assoc()) {
			$hashed = password_hash($new_password, PASSWORD_DEFAULT);
			$upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
			$upd->bind_param("si", $hashed, $row['user_id']);
			if ($upd->execute()) {
				$mark = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
				$mark->bind_param("i", $row['id']);
				$mark->execute();
				$success = 'Password updated successfully. You can now sign in.';
			} else {
				$error = 'Failed to update password. Please try again.';
			}
		} else {
			$error = 'This reset link is invalid or has expired.';
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Set New Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<link href="assets/css/global.css" rel="stylesheet">
<link rel="icon" href="assets/img/logo.png">
</head>
<body class="login-body">

<div class="container">
	<div class="row justify-content-center min-vh-100 align-items-center">
		<div class="col-md-6 col-lg-4">
			<div class="card shadow-lg border-0">
				<div class="card-body p-5">
					<div class="text-center mb-4">
						<img src="assets/img/logo.png" class="univ-logo" height="100" width="100" alt="">
						<h3 class="fw-bold">Set new password</h3>
						<p class="text-muted">Choose a strong password</p>
					</div>

					<?php if (!empty($error)): ?>
						<div class="alert alert-danger" role="alert">
							<i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
						</div>
					<?php endif; ?>

					<?php if (!empty($success)): ?>
						<div class="alert alert-success" role="alert">
							<i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
						</div>
					<?php endif; ?>

					<form method="POST" action="">
						<input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
						<div class="mb-3">
							<label for="new_password" class="form-label">New Password</label>
							<div class="input-group">
								<span class="input-group-text"><i class="fas fa-lock"></i></span>
								<input type="password" class="form-control" id="new_password" name="new_password" required>
							</div>
						</div>

						<div class="mb-4">
							<label for="confirm_password" class="form-label">Confirm Password</label>
							<div class="input-group">
								<span class="input-group-text"><i class="fas fa-lock"></i></span>
								<input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
							</div>
						</div>

						<button type="submit" class="btn btn-primary w-100 py-2">
							<i class="fas fa-key me-2"></i>Update Password
						</button>
					</form>

					<div class="text-center mt-4">
						<small class="text-muted">
							Back to <a href="index.php">login</a>
						</small>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
<script src="assets/js/global.js"></script>

</body>
</html>



