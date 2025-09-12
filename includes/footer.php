</div> <!-- End Main Content -->

<!-- Footer -->
<footer>
    &copy; <?php echo date('Y'); ?> Student Management System. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar toggle
function openSidebar() {
    document.getElementById("mySidebar").style.left = "0";
    document.getElementById("main-content").style.marginLeft = "250px";
}
function closeSidebar() {
    document.getElementById("mySidebar").style.left = "-250px";
    document.getElementById("main-content").style.marginLeft = "0";
}

// Rain effect
const canvas = document.getElementById('rain-canvas');
const ctx = canvas.getContext('2d');
let width = canvas.width = window.innerWidth;
let height = canvas.height = window.innerHeight;
const drops = [];
for(let i=0;i<300;i++) drops.push({
    x:Math.random()*width,
    y:Math.random()*height,
    length:10+Math.random()*20,
    speed:2+Math.random()*4,
    opacity:0.2+Math.random()*0.5
});
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
</body>
</html>
