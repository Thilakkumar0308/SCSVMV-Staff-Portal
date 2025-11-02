<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php'; // sanitize_input()

$error = '';
$success = '';
$passwordUpdated = false;
$token = isset($_GET['token']) ? sanitize_input($_GET['token']) : '';

// Accept token from POST as well (form submit)
if (empty($token) && isset($_POST['token'])) {
    $token = sanitize_input($_POST['token']);
}

if (empty($token) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error = 'Invalid or missing token.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['token']) ? sanitize_input($_POST['token']) : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Server-side validation
    if (empty($token) || empty($new_password) || empty($confirm_password)) {
        $error = 'Password missing';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $stmt = $conn->prepare("
            SELECT pr.id AS pr_id, pr.user_id, u.password AS old_password
            FROM password_resets pr
            INNER JOIN users u ON u.id = pr.user_id
            WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0
            LIMIT 1
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            $pr_id = (int)$row['pr_id'];
            $user_id = (int)$row['user_id'];
            $old_hashed = $row['old_password'];

            // If new password equals old
            if (password_verify($new_password, $old_hashed)) {
                // Ensure session cleared so clicking login won't auto-login
                session_unset();
                session_destroy();
                $error = 'This is your old password. <a href="index.php" class="btn btn-sm btn-primary" id="goLogin">Go to Login</a>';
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);

                $conn->begin_transaction();
                try {
                    $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upd->bind_param("si", $hashed, $user_id);
                    if (!$upd->execute()) throw new Exception('Failed to update password.');

                    $mark = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
                    $mark->bind_param("i", $pr_id);
                    if (!$mark->execute()) throw new Exception('Failed to mark token.');

                    $conn->commit();

                    // Clear session to be safe
                    session_unset();
                    session_destroy();

                    $passwordUpdated = true;
                    $success = 'Password updated successfully! <a href="index.php" class="btn btn-sm btn-primary" id="goLogin">Go to Login</a>';
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = $e->getMessage() ?: 'Failed to update password.';
                }
            }
        } else {
            $error = 'This reset link is invalid or has expired.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Reset Password - Student Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <link href="assets/css/global.css" rel="stylesheet">
  <link rel="icon" href="assets/img/logo.png">
</head>
<body class="login-body">
  <div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
        <div class="card shadow-lg border-0">
          <div class="card-body p-4 p-sm-5">
            <div class="text-center mb-4">
              <img src="assets/img/logo.png" alt="logo" class="univ-logo" height="80" width="80">
              <h3 class="fw-bold mt-2">Set New Password</h3>
              <p class="text-muted">Choose a strong password</p>
            </div>

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
              <div class="alert alert-success text-center">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
              </div>
            <?php endif; ?>

            <?php if (!$passwordUpdated): ?>
              <form id="resetForm" method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="mb-3">
                  <label for="new_password" class="form-label">New Password</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="new_password" name="new_password" aria-describedby="newHelp" required>
                    <button type="button" class="btn btn-outline-secondary" id="toggleNew" aria-label="Toggle new password">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                  <div id="newHelp" class="form-text">Minimum 6 characters. Use letters and numbers.</div>
                  <div class="text-danger small d-none mt-1" id="newMsg">Password missing</div>
                </div>

                <div class="mb-3">
                  <label for="confirm_password" class="form-label">Confirm Password</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <button type="button" class="btn btn-outline-secondary" id="toggleConfirm" aria-label="Toggle confirm password">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                  <div class="text-danger small d-none mt-1" id="confirmMsg">Please confirm your password</div>
                </div>

                <div id="formMessage" class="mb-3"></div>

                <button type="submit" class="btn btn-primary w-100 py-2" id="submitBtn">
                  <i class="fas fa-key me-2"></i>Update Password
                </button>
              </form>
            <?php endif; ?>

            <div class="text-center mt-4">
              <small class="text-muted">Back to <a href="index.php">login</a></small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Toggle visibility and client-side validation
    (function(){
      const form = document.getElementById('resetForm');
      if (!form) {
        // If no form (i.e. passwordUpdated), still attach login link handler if present
        const goLogin = document.getElementById('goLogin');
        if (goLogin) {
          goLogin.addEventListener('click', function(e){
            e.preventDefault();
            window.location.href = 'index.php';
          });
        }
        return;
      }

      const newPass = document.getElementById('new_password');
      const confirmPass = document.getElementById('confirm_password');
      const newMsg = document.getElementById('newMsg');
      const confirmMsg = document.getElementById('confirmMsg');
      const formMessage = document.getElementById('formMessage');
      const submitBtn = document.getElementById('submitBtn');

      document.getElementById('toggleNew').addEventListener('click', function() {
        const type = newPass.type === 'password' ? 'text' : 'password';
        newPass.type = type;
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
      });

      document.getElementById('toggleConfirm').addEventListener('click', function() {
        const type = confirmPass.type === 'password' ? 'text' : 'password';
        confirmPass.type = type;
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
      });

      form.addEventListener('submit', function(e) {
        // reset messages
        newMsg.classList.add('d-none');
        confirmMsg.classList.add('d-none');
        formMessage.className = '';
        formMessage.innerHTML = '';

        let hasError = false;

        // Password missing or too short
        if (!newPass.value || newPass.value.trim() === '') {
          newMsg.textContent = 'Password missing';
          newMsg.classList.remove('d-none');
          hasError = true;
        } else if (newPass.value.length < 6) {
          newMsg.textContent = 'Password must be at least 6 characters';
          newMsg.classList.remove('d-none');
          hasError = true;
        }

        // Confirm missing / mismatch
        if (!confirmPass.value || confirmPass.value.trim() === '') {
          confirmMsg.textContent = 'Please confirm your password';
          confirmMsg.classList.remove('d-none');
          hasError = true;
        } else if (newPass.value !== confirmPass.value) {
          confirmMsg.textContent = 'Passwords do not match';
          confirmMsg.classList.remove('d-none');
          hasError = true;
        }

        if (hasError) {
          e.preventDefault();
          formMessage.className = 'alert alert-danger';
          formMessage.textContent = 'Please fix the errors above and try again.';
          return false;
        }

        // disable submit to prevent double submit
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
        return true;
      });

      // If any login buttons exist in alerts, force them to go to index.php
      document.addEventListener('click', function(e){
        const target = e.target.closest && e.target.closest('#goLogin');
        if (target) {
          e.preventDefault();
          window.location.href = 'index.php';
        }
      });
    })();
  </script>
</body>
</html>
