<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/db.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Student Management System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
body { font-family: 'Poppins', Arial, sans-serif; margin:0; background:#0a0a0a; color:#fff; overflow-x:hidden; }
#rain-canvas { position:fixed; top:0; left:0; width:100%; height:100%; z-index:-1; }
.top-bar { background:#0d6efd; color:#fff; font-size:14px; }
.top-bar a { color:#fff; text-decoration:none; }
.navbar { background-color: rgba(255,255,255,0.05); backdrop-filter: blur(5px); box-shadow:0 2px 5px rgba(0,0,0,0.3); }
.navbar-logo h5, .navbar-logo small { margin:0; color:#fff; }
.navbar-logo small { color:#ccc; }
.hamburger { font-size:24px; cursor:pointer; color:#fff; }
.sidebar { height:100vh; width:250px; position:fixed; top:0; left:-250px; background:#222; color:#fff; transition:0.3s; z-index:1050; padding-top:70px; }
.sidebar a { display:block; color:#fff; padding:14px 20px; text-decoration:none; font-weight:500; transition:0.3s; }
.sidebar a:hover { background:linear-gradient(90deg,#0d6efd,#6610f2); color:#fff; }
.sidebar .close-btn { position:absolute; top:10px; right:20px; font-size:30px; cursor:pointer; }
#main-content { transition:margin-left 0.3s; padding:20px; }
.btn-outline-light { border:1px solid #fff; color:#fff; }
.btn-outline-light:hover { background-color:#0d6efd; border-color:#0d6efd; color:#fff; }
@media (max-width:768px) { #main-content { margin-left:0 !important; } .sidebar { width:100%; left:-100%; } }
</style>
</head>
<body>

<canvas id="rain-canvas"></canvas>

<div class="top-bar py-2">
  <div class="container d-flex justify-content-between">
    <div><i class="fas fa-phone-alt me-2"></i> +91 9629 00 11 44</div>
    <div><i class="fas fa-envelope me-2"></i> info@kanchiuniv.ac.in</div>
  </div>
</div>

<nav class="navbar navbar-light sticky-top d-flex justify-content-between px-3 py-2">
    <div class="d-flex align-items-center">
        <span class="hamburger me-3" onclick="openSidebar()"><i class="fas fa-bars"></i></span>
        <a class="navbar-logo d-flex align-items-center gap-2" href="dashboard.php">
            <img src="assets/img/logo.png" class="univ-logo" alt="Logo" style="height:60px;">
            <div>
                <h5 class="mb-0 fw-bold">Sri Chandrasekharendra Saraswathi Viswa Mahavidyalaya</h5>
                <small>Student Management System</small>
            </div>
        </a>
    </div>

    <form class="d-flex search-bar" method="POST" action="search.php">
        <div class="input-group">
            <input class="form-control" type="text" name="search_term" placeholder="Search by registration number..." required>
            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
        </div>
    </form>

    <!-- Profile Dropdown -->
   <!-- Profile Dropdown -->
<div class="dropdown">
  <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
  </button>
  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-id-card me-2"></i>View Profile</a></li>
    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
  </ul>
</div>


</nav>

<div id="mySidebar" class="sidebar">
    <span class="close-btn" onclick="closeSidebar()">&times;</span>
    <a href="dashboard.php">Dashboard</a>
    <a href="students.php">Students</a>
    <a href="leave.php">Leave Requests</a>
    <a href="onduty.php">On-Duty</a>
    <a href="disciplinary.php">Disciplinary</a>
    <a href="marks.php">Marks</a>
    <a href="search.php">Search</a>
    <?php if (has_role('Admin') || has_role('DeptAdmin') || has_role('HOD')): ?>
        <a href="class_management.php">Classes</a>
        <a href="attendance.php">Attendance</a>
        <a href="timetable.php">Timetable</a>
    <?php endif; ?>
    <a href="reports.php">Reports</a>
    <?php if (has_role('HOD')): ?><a href="hod.php">HOD Dashboard</a><?php endif; ?>
    <?php if (has_role('DeptAdmin') || has_role('Admin')): ?><a href="department_management.php">Departments</a><?php endif; ?>
    <?php if (has_role('Admin')): ?>
        <a href="users.php">Users</a>
        <a href="settings.php">Settings</a>
    <?php endif; ?>
</div>

<div id="main-content"></div>

<script>
// Sidebar toggle
function openSidebar() { document.getElementById("mySidebar").style.left = "0"; document.getElementById("main-content").style.marginLeft = "250px"; }
function closeSidebar() { document.getElementById("mySidebar").style.left = "-250px"; document.getElementById("main-content").style.marginLeft = "0"; }

// Rain + Thunder
const canvas=document.getElementById('rain-canvas'); const ctx=canvas.getContext('2d'); let width=canvas.width=window.innerWidth; let height=canvas.height=window.innerHeight;
const drops=[]; for(let i=0;i<300;i++){ drops.push({x:Math.random()*width, y:Math.random()*height, length:10+Math.random()*20, speed:2+Math.random()*4, opacity:0.2+Math.random()*0.5}); }
let flashOpacity=0;
function animate(){ ctx.clearRect(0,0,width,height); if(Math.random()<0.002) flashOpacity=0.6; if(flashOpacity>0){ctx.fillStyle=`rgba(255,255,255,${flashOpacity})`; ctx.fillRect(0,0,width,height); flashOpacity-=0.02;} for(const drop of drops){drop.y+=drop.speed;if(drop.y>height){drop.y=-drop.length;drop.x=Math.random()*width;}ctx.strokeStyle=`rgba(173,216,230,${drop.opacity})`;ctx.beginPath();ctx.moveTo(drop.x,drop.y);ctx.lineTo(drop.x,drop.y+drop.length);ctx.stroke();} requestAnimationFrame(animate);}
animate();
window.addEventListener('resize',()=>{ width=canvas.width=window.innerWidth; height=canvas.height=window.innerHeight; });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
