<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireRole(['admin', 'superadmin']);

$escrowModel = new Escrow();
$stats = $escrowModel->getAdminStats();
$monthlyData = $escrowModel->getMonthlyStats(6);
$db = Database::getInstance();

$recentEscrows = $db->fetchAll("SELECT e.*, CONCAT(b.first_name,' ',b.last_name) as buyer_name, CONCAT(s.first_name,' ',s.last_name) as seller_name FROM escrows e LEFT JOIN users b ON b.id=e.buyer_id LEFT JOIN users s ON s.id=e.seller_id ORDER BY e.created_at DESC LIMIT 7");
$recentUsers = $db->fetchAll("SELECT * FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 5");
$openDisputes = $db->fetchAll("SELECT d.*, e.escrow_id as esc_ref, e.amount, e.currency FROM disputes d JOIN escrows e ON e.id=d.escrow_id WHERE d.status IN ('open','under_review') ORDER BY d.created_at DESC LIMIT 5");
$pendingWithdrawals = $db->fetchAll("SELECT w.*, CONCAT(u.first_name,' ',u.last_name) as user_name FROM withdrawals w JOIN users u ON u.id=w.user_id WHERE w.status='pending' ORDER BY w.created_at DESC LIMIT 5");
$pendingKyc = $stats['pending_kyc'] ?? 0;
$pendingDeposits = $db->fetchColumn("SELECT COUNT(*) FROM transactions WHERE type='deposit' AND status='pending'") ?: 0;
$todayVolume = $db->fetchColumn("SELECT COALESCE(SUM(amount),0) FROM escrows WHERE DATE(created_at) = CURDATE()") ?: 0;
$todayUsers = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()") ?: 0;
$completionRate = $stats['total_escrows'] > 0 ? round(($stats['completed_escrows'] / $stats['total_escrows']) * 100) : 0;

require_once APP_ROOT . '/templates/header.php';
?>

<!-- Welcome -->
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-extrabold text-white">Dashboard</h1>
        <p class="text-sm text-gray-500 mt-0.5"><?= date('l, M j, Y') ?></p>
    </div>
    <div class="flex items-center gap-2 text-xs">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/15">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> System Online
        </span>
    </div>
</div>

<!-- Urgent Alerts -->
<?php
$alerts = [];
if ($pendingDeposits > 0) $alerts[] = ['Pending Deposits', $pendingDeposits, APP_URL.'/pages/admin/payments.php', 'amber'];
if (count($pendingWithdrawals) > 0) $alerts[] = ['Pending Withdrawals', count($pendingWithdrawals), APP_URL.'/pages/admin/payments.php', 'blue'];
if ($stats['open_disputes'] > 0) $alerts[] = ['Open Disputes', $stats['open_disputes'], APP_URL.'/pages/admin/disputes.php', 'red'];
if ($pendingKyc > 0) $alerts[] = ['KYC Reviews', $pendingKyc, APP_URL.'/pages/admin/kyc.php', 'purple'];
if (!empty($alerts)):
?>
<div class="flex flex-wrap gap-2 mb-6">
    <?php foreach ($alerts as $a): ?>
    <a href="<?= $a[2] ?>" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[11px] font-semibold bg-<?= $a[3] ?>-500/10 text-<?= $a[3] ?>-400 border border-<?= $a[3] ?>-500/15 hover:bg-<?= $a[3] ?>-500/20 transition-all">
        <span class="w-1.5 h-1.5 rounded-full bg-<?= $a[3] ?>-400 animate-pulse"></span>
        <?= $a[1] ?> <?= $a[0] ?>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Primary Stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <?php
    $cards = [
        ['Users', number_format($stats['total_users']), 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197', '#3b82f6', APP_URL.'/pages/admin/users.php'],
        ['Escrows', number_format($stats['total_escrows']), 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', '#6366f1', APP_URL.'/pages/admin/escrows.php'],
        ['Volume', formatMoney($stats['total_volume']), 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1', '#10b981', '#'],
        ['Revenue', formatMoney($stats['platform_revenue']), 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', '#C8F545', APP_URL.'/pages/admin/reports.php'],
    ];
    foreach ($cards as $c):
    ?>
    <a href="<?= $c[4] ?>" class="group relative rounded-2xl p-4 sm:p-5 border border-white/[0.06] overflow-hidden transition-all hover:border-white/[0.12]" style="background:linear-gradient(135deg, <?= $c[3] ?>08 0%, transparent 60%)">
        <div class="flex items-center justify-between mb-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:<?= $c[3] ?>15">
                <svg class="w-[18px] h-[18px]" style="color:<?= $c[3] ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $c[2] ?>"/></svg>
            </div>
            <svg class="w-4 h-4 text-gray-700 group-hover:text-gray-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </div>
        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500"><?= $c[0] ?></p>
        <p class="text-lg sm:text-xl font-extrabold text-white mt-0.5 truncate"><?= $c[1] ?></p>
    </a>
    <?php endforeach; ?>
</div>

<!-- Secondary Stats -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <?php
    $mini = [
        ['Active', number_format($stats['active_escrows']), '#f59e0b'],
        ['Today Vol.', formatMoney($todayVolume), '#06b6d4'],
        ['Completion', $completionRate.'%', '#10b981'],
        ['New Today', $todayUsers.' users', '#8b5cf6'],
    ];
    foreach ($mini as $m):
    ?>
    <div class="rounded-xl p-3 sm:p-4 border border-white/[0.06]" style="background:rgba(19,24,37,.5)">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-600"><?= $m[0] ?></p>
        <p class="text-sm sm:text-base font-bold text-white mt-0.5 truncate"><?= $m[1] ?></p>
        <div class="mt-2 h-1 rounded-full bg-white/[0.04] overflow-hidden">
            <div class="h-full rounded-full" style="background:<?= $m[2] ?>;width:<?= min(100, max(8, rand(30,90))) ?>%"></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4 mb-6">
    <div class="rounded-2xl border border-white/[0.06] p-4 sm:p-5" style="background:rgba(19,24,37,.5)">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-bold text-white">Transaction Volume</h3>
                <p class="text-[10px] text-gray-600 mt-0.5">Monthly completed escrow value</p>
            </div>
            <span class="text-[10px] text-gray-600 bg-white/[0.04] px-2 py-0.5 rounded-full">6mo</span>
        </div>
        <div class="relative w-full" style="height:clamp(160px,25vw,220px)"><canvas id="volChart"></canvas></div>
    </div>
    <div class="rounded-2xl border border-white/[0.06] p-4 sm:p-5" style="background:rgba(19,24,37,.5)">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-bold text-white">Platform Revenue</h3>
                <p class="text-[10px] text-gray-600 mt-0.5">Fee income from escrows</p>
            </div>
            <span class="text-[10px] text-gray-600 bg-white/[0.04] px-2 py-0.5 rounded-full">6mo</span>
        </div>
        <div class="relative w-full" style="height:clamp(160px,25vw,220px)"><canvas id="revChart"></canvas></div>
    </div>
</div>

<!-- Activity Feeds -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4 mb-6">
    <!-- Recent Escrows -->
    <div class="rounded-2xl border border-white/[0.06] overflow-hidden" style="background:rgba(19,24,37,.5)">
        <div class="flex items-center justify-between px-4 sm:px-5 py-3 border-b border-white/[0.06]">
            <h3 class="text-sm font-bold text-white">Recent Escrows</h3>
            <a href="<?= APP_URL ?>/pages/admin/escrows.php" class="text-[11px] font-semibold text-accent">View all →</a>
        </div>
        <div class="divide-y divide-white/[0.04] max-h-[380px] overflow-y-auto">
            <?php if (empty($recentEscrows)): ?>
                <p class="px-5 py-8 text-xs text-gray-600 text-center">No escrows yet</p>
            <?php else: foreach ($recentEscrows as $esc): ?>
            <a href="<?= APP_URL ?>/pages/escrow/view.php?id=<?= $esc['id'] ?>" class="flex items-center gap-3 px-4 sm:px-5 py-3 hover:bg-white/[0.02] transition-colors">
                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($esc['title']) ?></p>
                    <p class="text-[10px] text-gray-600"><?= htmlspecialchars($esc['escrow_id']) ?> · <?= timeAgo($esc['created_at']) ?></p>
                </div>
                <div class="text-right shrink-0 ml-2">
                    <p class="text-xs font-bold text-white"><?= formatMoney($esc['amount'], $esc['currency']) ?></p>
                    <?= statusBadge($esc['status']) ?>
                </div>
            </a>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Open Disputes -->
    <div class="rounded-2xl border border-white/[0.06] overflow-hidden" style="background:rgba(19,24,37,.5)">
        <div class="flex items-center justify-between px-4 sm:px-5 py-3 border-b border-white/[0.06]">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                Disputes
                <?php if ($stats['open_disputes'] > 0): ?><span class="w-2 h-2 rounded-full bg-red-400 animate-pulse"></span><?php endif; ?>
            </h3>
            <a href="<?= APP_URL ?>/pages/admin/disputes.php" class="text-[11px] font-semibold text-accent">View all →</a>
        </div>
        <div class="divide-y divide-white/[0.04] max-h-[380px] overflow-y-auto">
            <?php if (empty($openDisputes)): ?>
                <div class="px-5 py-8 text-center">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center mx-auto mb-2"><svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    <p class="text-xs text-gray-600">No open disputes</p>
                </div>
            <?php else: foreach ($openDisputes as $d): ?>
            <a href="<?= APP_URL ?>/pages/admin/disputes.php" class="flex items-center gap-3 px-4 sm:px-5 py-3 hover:bg-white/[0.02] transition-colors">
                <div class="w-8 h-8 rounded-lg bg-red-500/10 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-red-400"><?= htmlspecialchars($d['dispute_id']) ?></p>
                    <p class="text-[10px] text-gray-600"><?= ucwords(str_replace('_',' ',$d['reason'])) ?> · <?= timeAgo($d['created_at']) ?></p>
                </div>
                <div class="text-right shrink-0 ml-2">
                    <p class="text-xs font-bold text-white"><?= formatMoney($d['amount'], $d['currency']) ?></p>
                    <?= statusBadge($d['status']) ?>
                </div>
            </a>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- Users + Withdrawals -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4 mb-6">
    <!-- New Users -->
    <div class="rounded-2xl border border-white/[0.06] overflow-hidden" style="background:rgba(19,24,37,.5)">
        <div class="flex items-center justify-between px-4 sm:px-5 py-3 border-b border-white/[0.06]">
            <h3 class="text-sm font-bold text-white">New Users</h3>
            <a href="<?= APP_URL ?>/pages/admin/users.php" class="text-[11px] font-semibold text-accent">View all →</a>
        </div>
        <div class="divide-y divide-white/[0.04]">
            <?php if (empty($recentUsers)): ?>
                <p class="px-5 py-8 text-xs text-gray-600 text-center">No recent users</p>
            <?php else: foreach ($recentUsers as $u): ?>
            <div class="flex items-center gap-3 px-4 sm:px-5 py-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0" style="background:<?= '#'.substr(md5($u['email']),0,6) ?>22;color:#<?= substr(md5($u['email']),0,6) ?>">
                    <?= strtoupper(substr($u['first_name'],0,1).substr($u['last_name'],0,1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></p>
                    <p class="text-[10px] text-gray-600 truncate"><?= htmlspecialchars($u['email']) ?></p>
                </div>
                <div class="text-right shrink-0">
                    <?= statusBadge($u['status']) ?>
                    <p class="text-[9px] text-gray-600 mt-0.5"><?= timeAgo($u['created_at']) ?></p>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Pending Withdrawals -->
    <div class="rounded-2xl border border-white/[0.06] overflow-hidden" style="background:rgba(19,24,37,.5)">
        <div class="flex items-center justify-between px-4 sm:px-5 py-3 border-b border-white/[0.06]">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                Withdrawals
                <?php if (count($pendingWithdrawals) > 0): ?>
                <span class="text-[9px] px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400 font-bold"><?= count($pendingWithdrawals) ?></span>
                <?php endif; ?>
            </h3>
            <a href="<?= APP_URL ?>/pages/admin/payments.php" class="text-[11px] font-semibold text-accent">View all →</a>
        </div>
        <div class="divide-y divide-white/[0.04]">
            <?php if (empty($pendingWithdrawals)): ?>
                <p class="px-5 py-8 text-xs text-gray-600 text-center">No pending withdrawals</p>
            <?php else: foreach ($pendingWithdrawals as $w): ?>
            <div class="flex items-center justify-between px-4 sm:px-5 py-3">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($w['user_name']) ?></p>
                        <p class="text-[10px] text-gray-600"><?= ucfirst($w['method']) ?> · <?= timeAgo($w['created_at']) ?></p>
                    </div>
                </div>
                <p class="text-sm font-bold text-white shrink-0 ml-3"><?= formatMoney($w['amount'], $w['currency']) ?></p>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="rounded-2xl border border-white/[0.06] p-4 sm:p-5" style="background:rgba(19,24,37,.5)">
    <h3 class="text-sm font-bold text-white mb-3">Quick Actions</h3>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
        <?php
        $actions = [
            ['Users', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197', '#3b82f6', APP_URL.'/pages/admin/users.php'],
            ['Payments', 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', '#10b981', APP_URL.'/pages/admin/payments.php'],
            ['Gateways', 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', '#8b5cf6', APP_URL.'/pages/admin/gateways.php'],
            ['Settings', 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', '#6b7280', APP_URL.'/pages/admin/settings.php'],
        ];
        foreach ($actions as $a):
        ?>
        <a href="<?= $a[3] ?>" class="flex items-center gap-2.5 p-3 rounded-xl bg-white/[0.02] hover:bg-white/[0.05] border border-white/[0.04] hover:border-white/[0.08] transition-all">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background:<?= $a[2] ?>12">
                <svg class="w-4 h-4" style="color:<?= $a[2] ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $a[1] ?>"/></svg>
            </div>
            <span class="text-xs font-medium text-gray-300"><?= $a[0] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const md = <?= json_encode($monthlyData) ?>;
const lb = md.map(d => { const [y,m] = d.month.split('-'); return new Date(y,m-1).toLocaleDateString('en',{month:'short'}); });
const co = {
    responsive:true, maintainAspectRatio:false,
    plugins:{ legend:{display:false}, tooltip:{
        backgroundColor:'#0E1220', titleColor:'#C8F545', bodyColor:'#e2e8f0',
        borderColor:'rgba(255,255,255,0.06)', borderWidth:1, cornerRadius:10, padding:10,
        titleFont:{size:11}, bodyFont:{size:12}
    }},
    scales:{
        y:{ beginAtZero:true, grid:{color:'rgba(255,255,255,0.025)',drawBorder:false}, ticks:{color:'#3a3f50',font:{size:10},callback:v=>v>=1e6?(v/1e6)+'M':v>=1e3?(v/1e3)+'K':v}, border:{display:false} },
        x:{ grid:{display:false}, ticks:{color:'#3a3f50',font:{size:10}}, border:{display:false} }
    }
};

new Chart(document.getElementById('volChart'), {
    type:'bar', data:{ labels:lb.length?lb:['—'], datasets:[{
        data:md.map(d=>d.volume), backgroundColor:'rgba(200,245,69,0.1)', hoverBackgroundColor:'rgba(200,245,69,0.2)',
        borderColor:'rgba(200,245,69,0.4)', borderWidth:1, borderRadius:5, borderSkipped:false, maxBarThickness:28
    }]}, options:co
});

new Chart(document.getElementById('revChart'), {
    type:'line', data:{ labels:lb.length?lb:['—'], datasets:[{
        data:md.map(d=>d.revenue), borderColor:'rgba(245,158,11,0.7)', backgroundColor:'rgba(245,158,11,0.03)',
        borderWidth:2, fill:true, tension:0.4, pointRadius:3, pointHoverRadius:5,
        pointBackgroundColor:'rgb(245,158,11)', pointBorderColor:'#0E1220', pointBorderWidth:2
    }]}, options:co
});
</script>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
