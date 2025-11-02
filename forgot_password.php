<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php'; // sanitize_input()
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; // Composer autoload for PHPMailer

$error = '';
$success = '';

// ✅ Ensure password_resets table exists
$conn->query("CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

function generate_secure_token($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

function send_reset_email($toEmail, $fullName, $resetLink) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'scsvmvstaffportal@gmail.com'; // Your Gmail
        $mail->Password   = 'xjyo byvm sdwq yqmj'; // <-- App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('scsvmvstaffportal@gmail.com', 'SCSVMV Staff Portal');
        $mail->addAddress($toEmail, $fullName);
        $mail->isHTML(false);
        $mail->Subject = 'Password Reset Request';
        $mail->Body    = "Hello $fullName,\r\n\r\nWe received a request to reset your password. Click the link below to set a new password. This link will expire in 30 minutes.\r\n\r\n$resetLink\r\n\r\nIf you did not request this, please ignore this email.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail Error: ' . $mail->ErrorInfo);
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';

    if (empty($email)) {
        $error = 'Please enter your registered email address';
    } else {
        // ✅ Check in users first
        $stmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // ✅ If not found, check in students table
        if (!$user) {
            $stmt = $conn->prepare("SELECT id, CONCAT(first_name, ' ', last_name) AS full_name, email FROM students WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        }

        // ✅ If found in either table
        if ($user) {
            $token = generate_secure_token(64);
            $expires_at = date('Y-m-d H:i:s', time() + 1800); // 30 min

            $ins = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param("iss", $user['id'], $token, $expires_at);
            $ins->execute();

            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                       . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $resetLink = $baseUrl . '/reset_password.php?token=' . urlencode($token);

            if (send_reset_email($user['email'], $user['full_name'], $resetLink)) {
                $success = 'If that email exists in our system, a reset link has been sent.';
            } else {
                $error = 'Failed to send reset email. Please try again later.';
            }
        } else {
            // Prevent user enumeration
            $success = 'If that email exists in our system, a reset link has been sent.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - Student Management System</title>
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
                        <h3 class="fw-bold">Forgot your password?</h3>
                        <p class="text-muted">Enter your email to receive a reset link</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="email" class="form-label">Registered Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            Remembered your password? <a href="index.php">Back to login</a>
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
