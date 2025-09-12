<?php
session_start();
require_once 'config/db.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, role, full_name FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                redirect('dashboard.php');
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SCSVMV Student Management System - Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<link rel="icon" href="assets/img/logo.png">
<style>
/* Body & Background */
.login-body {
    min-height: 100vh;
    position: relative;
    overflow: hidden;
    --px: 0px;
    --py: 0px;
}

.login-body::before {
    content: '';
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: url('assets/img/DSC_1497.') no-repeat center center;
    background-size: cover;
    z-index: -1;
    transform: translate(var(--px), var(--py));
    transition: transform 0.1s ease-out;
}

/* Card Styling */
.card {
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

/* Button Glow */
.btn-primary {
    transition: all 0.3s ease-in-out;
    box-shadow: 0 4px 10px rgba(118,75,162,0.3);
}
.btn-primary:hover {
    background-color: #5a3e99;
    box-shadow: 0 0 20px rgba(118,75,162,0.8), 0 0 40px rgba(118,75,162,0.6);
    transform: translateY(-2px);
}

/* University Logo */
.univ-logo {
    display: block;
    margin: 10px auto;
}
</style>
</head>
<body class="login-body">
<canvas id="rain-canvas"></canvas>

<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <img src="assets/img/logo.png" class="univ-logo" height="100" width="100" alt="">
                        <h3 class="fw-bold">SCSVMV <br> Student Management System</h3>
                        <p class="text-muted">Please sign in to your account</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username"
                                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button id="login-btn" type="submit" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            PASSWORD DOB: <strong>01012001</strong>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="assets/js/script.js"></script>

<!-- Rain Animation -->
<script>
const canvas = document.getElementById('rain-canvas');
const ctx = canvas.getContext('2d');
let width = canvas.width = window.innerWidth;
let height = canvas.height = window.innerHeight;

const card = document.querySelector('.card');

// Rain drops
const drops = [];
for (let i = 0; i < 200; i++) {
    drops.push({ x: Math.random() * width, y: Math.random() * height, length: 10 + Math.random()*20, speed: 2 + Math.random()*4, opacity: 0.2 + Math.random()*0.5 });
}

let mouseX = width/2;
const thunders = [];

// Rain drift with mouse
document.addEventListener('mousemove', e => { mouseX = e.clientX; });

// Thunder on click outside card
document.addEventListener('click', e => {
    if(!card.contains(e.target)){
        const thunder = { x: e.clientX, y: e.clientY, life: 20 + Math.random()*20, segments: 10 + Math.floor(Math.random()*5) };
        thunders.push(thunder);
        
        // If card near lightning, add flash
        const rect = card.getBoundingClientRect();
        if(Math.abs(e.clientX - (rect.left+rect.width/2)) < 300 && Math.abs(e.clientY - (rect.top+rect.height/2)) < 300){
            card.classList.add('flash');
            setTimeout(()=>card.classList.remove('flash'), 100);
        }
    }
});

// Draw lightning
function drawThunder(t){
    ctx.strokeStyle = `rgba(255,255,255,${0.8 + Math.random()*0.2})`;
    ctx.lineWidth = 2 + Math.random()*2;
    ctx.beginPath();
    let x = t.x, y = t.y;
    ctx.moveTo(x,y);
    for(let i=0;i<t.segments;i++){
        const offsetX = (Math.random()-0.5)*60;
        const offsetY = Math.random()*60 + 20;
        ctx.lineTo(x+offsetX, y+offsetY);
        x += offsetX; y += offsetY;
    }
    ctx.stroke();
}

// Animation loop
function animate(){
    ctx.clearRect(0,0,width,height);
    
    // Rain
    for(const drop of drops){
        drop.x += (mouseX - width/2)*0.002;
        drop.y += drop.speed;
        if(drop.y>height){ drop.y = -drop.length; drop.x = Math.random()*width; }
        ctx.strokeStyle = `rgba(255,255,255,${drop.opacity})`;
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(drop.x, drop.y);
        ctx.lineTo(drop.x, drop.y+drop.length);
        ctx.stroke();
    }

    // Thunder
    for(let i=thunders.length-1;i>=0;i--){
        drawThunder(thunders[i]);
        thunders[i].life--;
        if(thunders[i].life<=0) thunders.splice(i,1);
    }

    requestAnimationFrame(animate);
}
animate();

// Password toggle
document.getElementById('togglePassword').addEventListener('click', function(){
    const pwd = document.getElementById('password');
    const icon = this.querySelector('i');
    if(pwd.type==='password'){ pwd.type='text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
    else { pwd.type='password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
});

// Resize canvas
window.addEventListener('resize', ()=>{ width = canvas.width = window.innerWidth; height = canvas.height = window.innerHeight; });
</script>

<style>
/* Body & Background */
.login-body {
    min-height: 100vh;
    position: relative;
    overflow: hidden;
    --px: 0px;
    --py: 0px;
    font-family: 'Segoe UI', sans-serif;
}

/* Card Styling */
.card {
    border-radius: 15px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.4);
    transition: box-shadow 0.3s ease, transform 0.3s ease;
    background: rgba(0,0,0,0.65);
    color: #fff;
}

/* Card hover glow */
.card:hover {
    box-shadow: 0 20px 50px rgba(118,75,162,0.8);
    transform: translateY(-3px);
}

/* Button Glow */
.btn-primary {
    background: linear-gradient(135deg,#764baa,#9a5edd);
    border: none;
    color: #fff;
    transition: all 0.3s ease-in-out;
    box-shadow: 0 4px 15px rgba(118,75,162,0.3);
}
.btn-primary:hover {
    background: linear-gradient(135deg,#9a5edd,#764baa);
    box-shadow: 0 0 25px rgba(118,75,162,0.9);
    transform: translateY(-2px);
}

/* Input focus glow */
.input-group .form-control:focus {
    border-color: #764baa;
    box-shadow: 0 0 8px rgba(118,75,162,0.7);
}

/* University Logo */
.univ-logo {
    display: block;
    margin: 10px auto;
}

/* Password toggle button */
#togglePassword {
    cursor: pointer;
    transition: transform 0.2s ease;
}
#togglePassword:hover {
    transform: scale(1.2);
}

/* Card flash effect during thunder */
.card.flash {
    box-shadow: 0 0 40px 15px rgba(255,255,255,0.7);
    transition: box-shadow 0.1s ease-in-out;
}
</style>




<canvas id="rain-canvas"></canvas>

<script>
// Wait until DOM fully loaded
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('rain-canvas');
    const ctx = canvas.getContext('2d');
    let width = canvas.width = window.innerWidth;
    let height = canvas.height = window.innerHeight;

    const card = document.querySelector('.card');

    // Create rain drops
    const drops = [];
    for (let i = 0; i < 200; i++) {
        drops.push({
            x: Math.random() * width,
            y: Math.random() * height,
            length: 10 + Math.random() * 20,
            speed: 2 + Math.random() * 4,
            opacity: 0.2 + Math.random() * 0.5
        });
    }

    const thunders = [];
    let mouseX = width / 2;

    document.addEventListener('mousemove', e => { mouseX = e.clientX; });

    // Thunder on click outside card
    document.addEventListener('click', e => {
        if (!card.contains(e.target)) {
            const thunder = {
                x: e.clientX,
                y: e.clientY,
                life: 20 + Math.random() * 20,
                segments: 10 + Math.floor(Math.random() * 5)
            };
            thunders.push(thunder);

            const rect = card.getBoundingClientRect();
            if (Math.abs(e.clientX - (rect.left + rect.width / 2)) < 300 &&
                Math.abs(e.clientY - (rect.top + rect.height / 2)) < 300) {
                card.classList.add('flash');
                setTimeout(() => card.classList.remove('flash'), 100);
            }
        }
    });

    function drawThunder(t) {
        ctx.strokeStyle = `rgba(255,255,255,${0.8 + Math.random() * 0.2})`;
        ctx.lineWidth = 2 + Math.random() * 2;
        ctx.beginPath();
        let x = t.x, y = t.y;
        ctx.moveTo(x, y);
        for (let i = 0; i < t.segments; i++) {
            const offsetX = (Math.random() - 0.5) * 60;
            const offsetY = Math.random() * 60 + 20;
            ctx.lineTo(x + offsetX, y + offsetY);
            x += offsetX; y += offsetY;
        }
        ctx.stroke();
    }

    function animate() {
        ctx.clearRect(0, 0, width, height);

        for (const drop of drops) {
            drop.x += (mouseX - width / 2) * 0.002;
            drop.y += drop.speed;
            if (drop.y > height) { drop.y = -drop.length; drop.x = Math.random() * width; }
            ctx.strokeStyle = `rgba(255,255,255,${drop.opacity})`;
            ctx.beginPath();
            ctx.moveTo(drop.x, drop.y);
            ctx.lineTo(drop.x, drop.y + drop.length);
            ctx.stroke();
        }

        for (let i = thunders.length - 1; i >= 0; i--) {
            drawThunder(thunders[i]);
            thunders[i].life--;
            if (thunders[i].life <= 0) thunders.splice(i, 1);
        }

        requestAnimationFrame(animate);
    }

    animate();

    // Resize canvas on window resize
    window.addEventListener('resize', () => {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
    });
});
</script>

</body>
</html>
