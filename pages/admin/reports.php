<?php
$pageTitle = 'Reports';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireRole(['admin', 'superadmin']);
$db = Database::getInstance();
$escrowModel = new Escrow();
$stats = $escrowModel->getAdminStats();
$monthlyData = $escrowModel->getMonthlyStats(12);
require_once APP_ROOT . '/templates/header.php';
?>
<div class="mb-6"><h1 class="text-2xl font-bold text-white">Reports & Analytics</h1></div>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="glass-card rounded-2xl p-5 border border-white/[0.06]"><p class="text-xs text-gray-400 uppercase font-semibold">Total Volume</p><p class="text-2xl font-bold mt-1"><?= formatMoney($stats['total_volume']) ?></p></div>
    <div class="glass-card rounded-2xl p-5 border border-white/[0.06]"><p class="text-xs text-gray-400 uppercase font-semibold">Revenue</p><p class="text-2xl font-bold mt-1"><?= formatMoney($stats['platform_revenue']) ?></p></div>
    <div class="glass-card rounded-2xl p-5 border border-white/[0.06]"><p class="text-xs text-gray-400 uppercase font-semibold">Completed</p><p class="text-2xl font-bold mt-1"><?= number_format($stats['completed_escrows']) ?></p></div>
    <div class="glass-card rounded-2xl p-5 border border-white/[0.06]"><p class="text-xs text-gray-400 uppercase font-semibold">Users</p><p class="text-2xl font-bold mt-1"><?= number_format($stats['total_users']) ?></p></div>
</div>
<div class="glass-card rounded-2xl border border-white/[0.06] p-6 mb-6"><h3 class="font-semibold mb-4">12-Month Overview</h3><canvas id="yearChart" height="300"></canvas></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const md = <?= json_encode($monthlyData) ?>;
const lb = md.map(d=>{const[y,m]=d.month.split('-');return new Date(y,m-1).toLocaleDateString('en',{month:'short',year:'2-digit'})});
new Chart(document.getElementById('yearChart'),{type:'bar',data:{labels:lb,datasets:[{label:'Volume',data:md.map(d=>d.volume),backgroundColor:'rgba(13,148,136,0.2)',borderColor:'rgb(13,148,136)',borderWidth:2,borderRadius:6,yAxisID:'y'},{label:'Revenue',data:md.map(d=>d.revenue),type:'line',borderColor:'rgb(217,119,6)',backgroundColor:'rgba(217,119,6,0.05)',borderWidth:2,fill:true,tension:0.4,pointRadius:3,yAxisID:'y1'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:true,grid:{color:'rgba(0,0,0,0.04)'},ticks:{callback:v=>v>=1e6?(v/1e6)+'M':v>=1e3?(v/1e3)+'K':v}},y1:{position:'right',beginAtZero:true,grid:{display:false},ticks:{callback:v=>v>=1e6?(v/1e6)+'M':v>=1e3?(v/1e3)+'K':v}},x:{grid:{display:false}}}}});
</script>
<?php require_once APP_ROOT . '/templates/footer.php'; ?>
