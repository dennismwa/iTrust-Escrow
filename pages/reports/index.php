<?php
$pageTitle = 'Reports';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireAuth();
$user = Auth::user();
$escrowModel = new Escrow();
$stats = $escrowModel->getUserStats($user['id']);
$monthlyData = $escrowModel->getMonthlyStats(12);
$db = Database::getInstance();
$wallet = $db->fetch("SELECT * FROM wallets WHERE user_id = ? LIMIT 1", [$user['id']]);

// Recent completed for breakdown
$recentCompleted = $db->fetchAll(
    "SELECT category, COUNT(*) as cnt, SUM(amount) as vol FROM escrows WHERE (buyer_id=? OR seller_id=?) AND status='completed' GROUP BY category ORDER BY vol DESC LIMIT 6",
    [$user['id'], $user['id']]
);

require_once APP_ROOT . '/templates/header.php';
?>
<div class="max-w-5xl mx-auto">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white">Reports</h1>
            <p class="text-sm text-gray-500 mt-1">Your transaction analytics & insights</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= APP_URL ?>/pages/escrow/index.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/[0.03] hover:bg-white/[0.06] border border-white/[0.06] text-sm text-gray-400 hover:text-white transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                View Escrows
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="glass-card rounded-2xl p-5 border border-indigo-500/10 bg-gradient-to-br from-indigo-500/10 to-indigo-500/5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-500/15 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Total Escrows</p>
            <p class="text-2xl font-extrabold text-indigo-400 mt-1"><?= number_format($stats['total_escrows']) ?></p>
        </div>
        <div class="glass-card rounded-2xl p-5 border border-emerald-500/10 bg-gradient-to-br from-emerald-500/10 to-emerald-500/5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/15 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/></svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Total Volume</p>
            <p class="text-2xl font-extrabold text-emerald-400 mt-1"><?= formatMoney($stats['total_volume']) ?></p>
        </div>
        <div class="glass-card rounded-2xl p-5 border border-lime-500/10 bg-gradient-to-br from-lime-500/10 to-lime-500/5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-lime-500/15 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-lime-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Completed</p>
            <p class="text-2xl font-extrabold text-lime-400 mt-1"><?= number_format($stats['completed_escrows']) ?></p>
        </div>
        <div class="glass-card rounded-2xl p-5 border border-red-500/10 bg-gradient-to-br from-red-500/10 to-red-500/5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-red-500/15 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Open Disputes</p>
            <p class="text-2xl font-extrabold text-red-400 mt-1"><?= number_format($stats['open_disputes']) ?></p>
        </div>
    </div>

    <!-- Chart + Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Volume Chart -->
        <div class="lg:col-span-2 glass-card rounded-2xl border border-white/[0.06] p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-bold text-white">Transaction Volume</h3>
                <span class="text-xs text-gray-600 bg-surface-200 px-2.5 py-1 rounded-full">12 months</span>
            </div>
            <div class="relative w-full" style="height: clamp(200px, 40vw, 280px);">
                <canvas id="chart"></canvas>
            </div>
        </div>

        <!-- Category Breakdown -->
        <div class="glass-card rounded-2xl border border-white/[0.06] p-6">
            <h3 class="font-bold text-white mb-5">By Category</h3>
            <?php if (empty($recentCompleted)): ?>
                <p class="text-sm text-gray-600 text-center py-8">No completed escrows yet</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php
                    $totalVol = array_sum(array_column($recentCompleted, 'vol'));
                    $catColors = ['car'=>'bg-blue-500','property'=>'bg-amber-500','freelance'=>'bg-purple-500','marketplace'=>'bg-cyan-500','import_export'=>'bg-orange-500','electronics'=>'bg-pink-500','digital_services'=>'bg-indigo-500','other'=>'bg-gray-500'];
                    $catTextColors = ['car'=>'text-blue-400','property'=>'text-amber-400','freelance'=>'text-purple-400','marketplace'=>'text-cyan-400','import_export'=>'text-orange-400','electronics'=>'text-pink-400','digital_services'=>'text-indigo-400','other'=>'text-gray-400'];
                    foreach ($recentCompleted as $cat):
                        $pct = $totalVol > 0 ? round(($cat['vol'] / $totalVol) * 100) : 0;
                        $color = $catColors[$cat['category']] ?? 'bg-gray-500';
                        $textColor = $catTextColors[$cat['category']] ?? 'text-gray-400';
                    ?>
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-medium <?= $textColor ?>"><?= ucwords(str_replace('_',' ',$cat['category'])) ?></span>
                            <span class="text-xs text-gray-500"><?= $cat['cnt'] ?> · <?= $pct ?>%</span>
                        </div>
                        <div class="w-full h-1.5 bg-surface-200 rounded-full overflow-hidden">
                            <div class="h-full <?= $color ?> rounded-full transition-all duration-500" style="width: <?= $pct ?>%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1"><?= formatMoney($cat['vol']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass-card rounded-2xl border border-white/[0.06] p-5">
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-2">Active Escrows</p>
            <p class="text-xl font-extrabold text-amber-400"><?= number_format($stats['active_escrows']) ?></p>
            <p class="text-xs text-gray-500 mt-1">Currently in progress</p>
        </div>
        <div class="glass-card rounded-2xl border border-white/[0.06] p-5">
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-2">Wallet Balance</p>
            <p class="text-xl font-extrabold text-emerald-400"><?= formatMoney($wallet['balance'] ?? 0) ?></p>
            <p class="text-xs text-gray-500 mt-1">Available funds</p>
        </div>
        <div class="glass-card rounded-2xl border border-white/[0.06] p-5">
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-2">Pending Withdrawals</p>
            <p class="text-xl font-extrabold text-orange-400"><?= formatMoney($stats['pending_withdrawals']) ?></p>
            <p class="text-xs text-gray-500 mt-1">Awaiting processing</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const md = <?= json_encode($monthlyData) ?>;
const lb = md.map(d => {
    const [y, m] = d.month.split('-');
    return new Date(y, m-1).toLocaleDateString('en', { month: 'short' });
});
new Chart(document.getElementById('chart'), {
    type: 'bar',
    data: {
        labels: lb.length ? lb : ['No data'],
        datasets: [{
            label: 'Volume',
            data: md.map(d => d.volume),
            backgroundColor: 'rgba(200,245,69,0.15)',
            borderColor: 'rgba(200,245,69,0.6)',
            borderWidth: 1.5,
            borderRadius: 6,
            borderSkipped: false,
            maxBarThickness: 40
        }, {
            label: 'Escrows',
            data: md.map(d => d.total_escrows),
            type: 'line',
            borderColor: 'rgba(99,102,241,0.8)',
            backgroundColor: 'rgba(99,102,241,0.1)',
            borderWidth: 2,
            pointRadius: 3,
            pointBackgroundColor: 'rgba(99,102,241,1)',
            pointBorderColor: '#131825',
            pointBorderWidth: 2,
            tension: 0.3,
            yAxisID: 'y1',
            fill: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                display: true,
                position: 'top',
                align: 'end',
                labels: {
                    color: '#666',
                    font: { size: 11 },
                    boxWidth: 12,
                    boxHeight: 12,
                    borderRadius: 3,
                    useBorderRadius: true,
                    padding: 16
                }
            },
            tooltip: {
                backgroundColor: '#131825',
                titleColor: '#C8F545',
                bodyColor: '#e2e8f0',
                borderColor: 'rgba(255,255,255,0.08)',
                borderWidth: 1,
                cornerRadius: 12,
                padding: 14,
                bodySpacing: 6
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                position: 'left',
                grid: { color: 'rgba(255,255,255,0.03)' },
                ticks: {
                    color: '#555',
                    font: { size: 11 },
                    callback: v => v >= 1e6 ? (v/1e6)+'M' : v >= 1e3 ? (v/1e3)+'K' : v
                }
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                grid: { drawOnChartArea: false },
                ticks: { color: '#555', font: { size: 11 } }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#555', font: { size: 11 } }
            }
        }
    }
});
</script>
<?php require_once APP_ROOT . '/templates/footer.php'; ?>
