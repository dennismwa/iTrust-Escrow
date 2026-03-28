<?php
$pageTitle="Dashboard";require_once __DIR__."/../../includes/init.php";Auth::requireAuth();checkMaintenance();
$user=Auth::user();$em=new Escrow();$stats=$em->getUserStats($user["id"]);$monthly=$em->getMonthlyStats(6);
$recent=$em->list(["user_id"=>$user["id"]],1,5);$db=Database::getInstance();
$wallet=$db->fetch("SELECT * FROM wallets WHERE user_id=? LIMIT 1",[$user["id"]]);
require_once APP_ROOT."/templates/header.php";
?>
<div class="mb-8">
    <h1 class="text-2xl font-bold text-white">Welcome back, <?= htmlspecialchars($user["first_name"]) ?></h1>
    <p class="text-sm text-gray-500 mt-1">Here's your escrow overview</p>
</div>

<!-- Stat Cards - All Clickable -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
<?php
$cards=[
    ["Wallet",formatMoney($wallet["balance"]??0),"text-emerald-400","from-emerald-500/10 to-emerald-500/5","border-emerald-500/10",APP_URL."/pages/wallet/index.php"],
    ["In Escrow",formatMoney($wallet["escrow_balance"]??0),"text-blue-400","from-blue-500/10 to-blue-500/5","border-blue-500/10",APP_URL."/pages/escrow/index.php?status=funded"],
    ["Total",$stats["total_escrows"],"text-indigo-400","from-indigo-500/10 to-indigo-500/5","border-indigo-500/10",APP_URL."/pages/escrow/index.php"],
    ["Active",$stats["active_escrows"],"text-amber-400","from-amber-500/10 to-amber-500/5","border-amber-500/10",APP_URL."/pages/escrow/index.php?status=funded"],
    ["Completed",$stats["completed_escrows"],"text-lime-400","from-lime-500/10 to-lime-500/5","border-lime-500/10",APP_URL."/pages/escrow/index.php?status=completed"],
    ["Trust",number_format($user["trust_score"],0)."%","text-purple-400","from-purple-500/10 to-purple-500/5","border-purple-500/10",APP_URL."/pages/profile/index.php"]
];
foreach($cards as $c):?>
<a href="<?= $c[5] ?>" class="group glass-card rounded-2xl p-4 border <?= $c[4] ?> bg-gradient-to-br <?= $c[3] ?> hover:scale-[1.02] hover:shadow-lg hover:shadow-black/20 transition-all duration-200 cursor-pointer">
    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2"><?= $c[0] ?></p>
    <p class="text-lg sm:text-xl font-extrabold truncate <?= $c[2] ?> group-hover:brightness-125 transition-all"><?= $c[1] ?></p>
</a>
<?php endforeach;?>
</div>

<!-- Quick Actions + Account Status -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
<div class="lg:col-span-2 glass-card rounded-2xl p-6 border border-white/[0.06]">
    <h3 class="font-bold text-white mb-4">Quick Actions</h3>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <a href="<?= APP_URL ?>/pages/escrow/create.php" class="group flex items-center gap-3 p-4 rounded-xl bg-gradient-to-br from-accent/10 to-accent/5 border border-accent/10 hover:border-accent/30 hover:shadow-lg hover:shadow-accent/5 transition-all duration-200">
            <div class="w-11 h-11 rounded-xl bg-accent/15 flex items-center justify-center group-hover:bg-accent/25 transition-colors">
                <svg class="w-5 h-5 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-white">Create Escrow</p>
                <p class="text-[11px] text-gray-500">New transaction</p>
            </div>
        </a>
        <a href="<?= APP_URL ?>/pages/wallet/index.php" class="group flex items-center gap-3 p-4 rounded-xl bg-gradient-to-br from-emerald-500/10 to-emerald-500/5 border border-emerald-500/10 hover:border-emerald-500/30 hover:shadow-lg hover:shadow-emerald-500/5 transition-all duration-200">
            <div class="w-11 h-11 rounded-xl bg-emerald-500/15 flex items-center justify-center group-hover:bg-emerald-500/25 transition-colors">
                <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-white">Deposit Funds</p>
                <p class="text-[11px] text-gray-500">Add to wallet</p>
            </div>
        </a>
        <a href="<?= APP_URL ?>/pages/kyc/index.php" class="group flex items-center gap-3 p-4 rounded-xl bg-gradient-to-br from-purple-500/10 to-purple-500/5 border border-purple-500/10 hover:border-purple-500/30 hover:shadow-lg hover:shadow-purple-500/5 transition-all duration-200">
            <div class="w-11 h-11 rounded-xl bg-purple-500/15 flex items-center justify-center group-hover:bg-purple-500/25 transition-colors">
                <svg class="w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-white">Verify KYC</p>
                <p class="text-[11px] text-gray-500">Complete verification</p>
            </div>
        </a>
    </div>
</div>
<div class="glass-card rounded-2xl p-6 border border-white/[0.06]">
    <h3 class="font-bold text-white mb-4">Account Status</h3>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <span class="text-sm text-gray-400">KYC Status</span>
            <?= statusBadge($user['kyc_status'] ?? 'none') ?>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-sm text-gray-400">Trust Score</span>
            <div class="flex items-center gap-2">
                <div class="w-20 h-1.5 bg-surface-200 rounded-full overflow-hidden">
                    <div class="h-full bg-accent rounded-full" style="width: <?= min(100, $user['trust_score']) ?>%"></div>
                </div>
                <span class="text-sm font-bold text-accent"><?= number_format($user['trust_score'],0) ?>%</span>
            </div>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-sm text-gray-400">Disputes</span>
            <span class="text-sm font-semibold <?= $stats['open_disputes'] > 0 ? 'text-red-400' : 'text-gray-300' ?>"><?= $stats['open_disputes'] ?> open</span>
        </div>
        <a href="<?= APP_URL ?>/pages/reports/index.php" class="flex items-center justify-center gap-2 mt-2 px-4 py-2.5 rounded-xl bg-white/[0.03] hover:bg-white/[0.06] border border-white/[0.06] text-sm text-gray-400 hover:text-white transition-all">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            View Full Reports
        </a>
    </div>
</div>
</div>

<!-- Recent Escrows -->
<div class="glass-card rounded-2xl overflow-hidden border border-white/[0.06] mb-8">
<div class="flex items-center justify-between px-6 py-4 border-b border-white/[0.06]"><h3 class="font-bold text-white">Recent Escrows</h3><a href="<?= APP_URL ?>/pages/escrow/index.php" class="text-sm font-medium text-accent hover:text-accent-dark transition-colors">View all →</a></div>
<div class="overflow-x-auto">
<table class="w-full"><thead><tr class="border-b border-white/[0.04]"><th class="text-left px-6 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">ID</th><th class="text-left px-6 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden sm:table-cell">Title</th><th class="text-left px-6 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">Amount</th><th class="text-left px-6 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</th><th class="text-right px-6 py-3"></th></tr></thead>
<tbody class="divide-y divide-white/[0.04]">
<?php if(empty($recent["data"])):?><tr><td colspan="5" class="px-6 py-12 text-center">
    <div class="flex flex-col items-center gap-3">
        <div class="w-14 h-14 rounded-2xl bg-surface-200 flex items-center justify-center"><svg class="w-7 h-7 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div>
        <p class="text-sm text-gray-600">No escrows yet</p>
        <a href="<?= APP_URL ?>/pages/escrow/create.php" class="text-sm font-medium text-accent">Create your first escrow →</a>
    </div>
</td></tr>
<?php else:foreach($recent["data"] as $e):?>
<tr class="hover:bg-white/[0.02] cursor-pointer transition-colors" onclick="window.location='<?= APP_URL ?>/pages/escrow/view.php?id=<?= $e["id"] ?>'">
    <td class="px-6 py-3.5"><span class="text-sm font-mono text-accent"><?= $e["escrow_id"] ?></span></td>
    <td class="px-6 py-3.5 hidden sm:table-cell"><span class="text-sm text-gray-300 truncate block max-w-[200px]"><?= htmlspecialchars($e["title"]) ?></span></td>
    <td class="px-6 py-3.5"><span class="text-sm font-bold text-white"><?= formatMoney($e["amount"],$e["currency"]) ?></span></td>
    <td class="px-6 py-3.5"><?= statusBadge($e["status"]) ?></td>
    <td class="px-6 py-3.5 text-right"><svg class="w-4 h-4 text-gray-600 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></td>
</tr>
<?php endforeach;endif;?></tbody></table>
</div>
</div>

<!-- Volume Chart - After Recent Escrows -->
<div class="glass-card rounded-2xl p-6 border border-white/[0.06] flex flex-col min-h-0">
    <div class="flex items-center justify-between mb-4 shrink-0">
        <h3 class="font-bold text-white">Transaction Volume</h3>
        <span class="text-xs text-gray-600 bg-surface-200 px-2.5 py-1 rounded-full">Last 6 months</span>
    </div>
    <div class="relative w-full h-52 sm:h-56 md:h-60 max-h-[280px]"><canvas id="vc"></canvas></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>const md=<?= json_encode($monthly) ?>;const lb=md.map(d=>{const[y,m]=d.month.split("-");return new Date(y,m-1).toLocaleDateString("en",{month:"short"})});new Chart(document.getElementById("vc"),{type:"bar",data:{labels:lb.length?lb:["No data"],datasets:[{data:md.map(d=>d.volume),backgroundColor:"rgba(200,245,69,0.12)",borderColor:"rgba(200,245,69,0.5)",borderWidth:1.5,borderRadius:8,borderSkipped:false}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{backgroundColor:'#131825',titleColor:'#C8F545',bodyColor:'#e2e8f0',borderColor:'rgba(255,255,255,0.06)',borderWidth:1,cornerRadius:12,padding:12}},scales:{y:{beginAtZero:true,grid:{color:"rgba(255,255,255,0.03)"},ticks:{color:"#555",font:{size:11},callback:v=>v>=1e6?(v/1e6)+"M":v>=1e3?(v/1e3)+"K":v}},x:{grid:{display:false},ticks:{color:"#555",font:{size:11}}}}}});</script>
<?php require_once APP_ROOT."/templates/footer.php";?>
