<?php
session_start();
require_once 'config/db.php';
require_login();

$page_title = "Classes";
require_once 'includes/header.php';

// Fetch classes
$res = $conn->query("SELECT * FROM classes ORDER BY id ASC"); // changed ORDER BY to id
$classes = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $classes[] = $row;
    }
}
?>

<div class="page-wrapper d-flex flex-column min-vh-100">
    <canvas id="rain-canvas"></canvas>

    <div class="container-fluid py-4 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-light">🏫 Classes</h2>
            <a href="dashboard.php" class="btn btn-gradient btn-sm"><i class="fas fa-arrow-left me-2"></i> Back</a>
        </div>

        <div class="card glass-card">
            <div class="card-header">
                <span>All Classes</span>
            </div>
            <div class="card-body">
                <?php if (empty($classes)): ?>
                    <p class="text-muted">No classes found.</p>
                <?php else: ?>
                    <table class="table table-dark table-sm align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Class Name</th>
                                <th>Section</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($classes as $c): ?>
                            <tr>
                                <td><?php echo $c['id']; ?></td>
                                <td><?php echo isset($c['class_name']) ? $c['class_name'] : 'N/A'; ?></td>
                                <td><?php echo isset($c['section']) ? $c['section'] : 'N/A'; ?></td>
                                <td><?php echo isset($c['created_at']) ? date('M d, Y', strtotime($c['created_at'])) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg,#0f0f1a,#1a1a2e);
    color: #fff;
    margin: 0;
    overflow-x: hidden;
}
.page-wrapper { display: flex; flex-direction: column; min-height: 100vh; }
.flex-grow-1 { flex: 1 0 auto; }
.card { border-radius: 15px; border: none; }
.glass-card { background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); color: #fff; box-shadow:0 8px 25px rgba(0,0,0,0.5);}
.card-header { font-weight:bold; font-size:16px; border-bottom:1px solid rgba(255,255,255,0.1); }
.btn-gradient { background: linear-gradient(135deg,#764baa,#9a5edd); border:none; color:#fff; padding:4px 12px; border-radius:20px; transition:all 0.3s;}
.btn-gradient:hover { background: linear-gradient(135deg,#9a5edd,#764baa); box-shadow: 0 0 15px rgba(118,75,162,0.7);}
#rain-canvas { position: fixed; top:0; left:0; z-index:-1; width:100%; height:100%; }
</style>

<script>
// Rain Effect
const canvas = document.getElementById('rain-canvas');
const ctx = canvas.getContext('2d');
let width = canvas.width = window.innerWidth;
let height = canvas.height = window.innerHeight;
const drops = [];
for(let i=0;i<300;i++) drops.push({x:Math.random()*width,y:Math.random()*height,length:10+Math.random()*20,speed:2+Math.random()*4,opacity:0.2+Math.random()*0.5});
let mouseX = width/2;
document.addEventListener('mousemove',e=>{mouseX=e.clientX;});
function animate(){
    ctx.clearRect(0,0,width,height);
    for(const drop of drops){
        drop.x += (mouseX-width/2)*0.002;
        drop.y += drop.speed;
        if(drop.y>height){ drop.y=-drop.length; drop.x=Math.random()*width; }
        ctx.strokeStyle=`rgba(255,255,255,${drop.opacity})`;
        ctx.lineWidth=1;
        ctx.beginPath(); ctx.moveTo(drop.x,drop.y); ctx.lineTo(drop.x,drop.y+drop.length); ctx.stroke();
    }
    requestAnimationFrame(animate);
}
animate();
window.addEventListener('resize',()=>{ width=canvas.width=window.innerWidth; height=canvas.height=window.innerHeight; });
</script>

<?php require_once 'includes/footer.php'; ?>
