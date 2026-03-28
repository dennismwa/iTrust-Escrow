<?php
$pageTitle = 'My Escrows';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireAuth();

$user = Auth::user();
$escrowModel = new Escrow();

$page = max(1, intval($_GET['page'] ?? 1));
$status = get('status');
$search = get('search');
$category = get('category');

$filters = ['user_id' => $user['id']];
if ($status) $filters['status'] = $status;
if ($search) $filters['search'] = $search;
if ($category) $filters['category'] = $category;

$escrows = $escrowModel->list($filters, $page, 15);
$baseUrl = "?status={$status}&search={$search}&category={$category}";

// Quick summary stats
$allStats = $escrowModel->getUserStats($user['id']);

require_once APP_ROOT . '/templates/header.php';
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-white">My Escrows</h1>
        <p class="text-sm text-gray-500 mt-1"><?= number_format($escrows['total']) ?> transaction<?= $escrows['total'] !== 1 ? 's' : '' ?> total</p>
    </div>
    <a href="<?= APP_URL ?>/pages/escrow/create.php" class="inline-flex items-center gap-2 bg-accent hover:bg-accent-dark text-surface text-sm font-bold px-5 py-2.5 rounded-xl transition-all shadow-sm hover:shadow-md hover:shadow-accent/10">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        New Escrow
    </a>
</div>

<!-- Quick Status Tabs -->
<div class="flex items-center gap-2 mb-5 overflow-x-auto pb-1 -mx-1 px-1 scrollbar-hide">
    <?php
    $tabs = [
        '' => ['All', $allStats['total_escrows'], 'text-white bg-white/[0.06]'],
        'funded' => ['Active', $allStats['active_escrows'], 'text-blue-400 bg-blue-500/10'],
        'completed' => ['Completed', $allStats['completed_escrows'], 'text-lime-400 bg-lime-500/10'],
        'disputed' => ['Disputed', $allStats['open_disputes'], 'text-red-400 bg-red-500/10'],
    ];
    foreach ($tabs as $key => $tab):
        $isActive = ($status === $key) || ($key === '' && !$status);
    ?>
    <a href="?status=<?= $key ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>"
       class="shrink-0 inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all <?= $isActive ? $tab[2] . ' ring-1 ring-white/10' : 'text-gray-500 hover:text-gray-300 hover:bg-white/[0.03]' ?>">
        <?= $tab[0] ?>
        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md <?= $isActive ? 'bg-white/10' : 'bg-white/[0.04]' ?>"><?= $tab[1] ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="glass-card rounded-2xl border border-white/[0.06] p-4 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1 relative">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-600 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by ID or title..."
                   class="w-full pl-10 pr-4 py-2.5 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50">
        </div>
        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
        <select name="category" class="px-4 py-2.5 bg-[#131825] border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-accent/20">
            <option value="">All Categories</option>
            <?php foreach (['car','property','freelance','marketplace','import_export','electronics','digital_services','other'] as $c): ?>
                <option value="<?= $c ?>" <?= $category === $c ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$c)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-5 py-2.5 bg-surface-200 hover:bg-surface-300 text-white text-sm font-medium rounded-xl border border-white/10 transition-colors">
            <svg class="w-4 h-4 sm:hidden inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            Filter
        </button>
    </form>
</div>

<!-- Empty State -->
<?php if (empty($escrows['data'])): ?>
<div class="glass-card rounded-2xl border border-white/[0.06] p-12 text-center">
    <div class="flex flex-col items-center gap-4">
        <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-accent/10 to-accent/5 border border-accent/10 flex items-center justify-center">
            <svg class="w-10 h-10 text-accent/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        </div>
        <div>
            <p class="text-lg font-semibold text-white">No escrows found</p>
            <p class="text-sm text-gray-500 mt-1">Start your first secure transaction today</p>
        </div>
        <a href="<?= APP_URL ?>/pages/escrow/create.php" class="inline-flex items-center gap-2 bg-accent hover:bg-accent-dark text-surface text-sm font-bold px-6 py-3 rounded-xl transition-all mt-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Create Escrow
        </a>
    </div>
</div>

<?php else: ?>

<!-- MOBILE: Card Layout (visible < lg) -->
<div class="lg:hidden space-y-3 mb-6">
    <?php foreach ($escrows['data'] as $esc): ?>
    <a href="<?= APP_URL ?>/pages/escrow/view.php?id=<?= $esc['id'] ?>" class="block glass-card rounded-2xl border border-white/[0.06] p-4 hover:border-accent/20 hover:shadow-lg hover:shadow-accent/5 transition-all active:scale-[0.99]">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-mono font-bold text-accent"><?= htmlspecialchars($esc['escrow_id']) ?></span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold <?= $esc['buyer_id'] == $user['id'] ? 'bg-blue-500/10 text-blue-400' : 'bg-emerald-500/10 text-emerald-400' ?>">
                        <?= $esc['buyer_id'] == $user['id'] ? 'BUYER' : 'SELLER' ?>
                    </span>
                </div>
                <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($esc['title']) ?></p>
            </div>
            <svg class="w-4 h-4 text-gray-600 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </div>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <p class="text-lg font-bold text-white"><?= formatMoney($esc['amount'], $esc['currency']) ?></p>
                <?= statusBadge($esc['status']) ?>
            </div>
            <div class="text-right">
                <p class="text-[11px] text-gray-500"><?= date('M j', strtotime($esc['created_at'])) ?></p>
                <p class="text-[11px] text-gray-600"><?= $esc['buyer_id'] == $user['id'] ? htmlspecialchars($esc['seller_name'] ?? 'Pending') : htmlspecialchars($esc['buyer_name']) ?></p>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<!-- DESKTOP: Table Layout (visible >= lg) -->
<div class="hidden lg:block glass-card rounded-2xl border border-white/[0.06] overflow-hidden mb-6">
    <table class="w-full">
        <thead>
            <tr class="border-b border-white/[0.06] bg-white/[0.02]">
                <th class="text-left px-6 py-3.5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Escrow</th>
                <th class="text-left px-6 py-3.5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Counterparty</th>
                <th class="text-left px-6 py-3.5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Amount</th>
                <th class="text-left px-6 py-3.5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Role</th>
                <th class="text-left px-6 py-3.5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Status</th>
                <th class="text-left px-6 py-3.5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Date</th>
                <th class="text-right px-6 py-3.5"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/[0.04]">
            <?php foreach ($escrows['data'] as $esc): ?>
            <tr class="hover:bg-white/[0.02] transition-colors cursor-pointer group" onclick="window.location='<?= APP_URL ?>/pages/escrow/view.php?id=<?= $esc['id'] ?>'">
                <td class="px-6 py-4">
                    <span class="text-sm font-mono font-medium text-accent"><?= htmlspecialchars($esc['escrow_id']) ?></span>
                    <p class="text-sm text-gray-500 mt-0.5 truncate max-w-[220px]"><?= htmlspecialchars($esc['title']) ?></p>
                </td>
                <td class="px-6 py-4">
                    <p class="text-sm text-gray-300"><?= $esc['buyer_id'] == $user['id'] ? htmlspecialchars($esc['seller_name'] ?? 'Pending') : htmlspecialchars($esc['buyer_name']) ?></p>
                </td>
                <td class="px-6 py-4">
                    <p class="text-sm font-bold text-white"><?= formatMoney($esc['amount'], $esc['currency']) ?></p>
                    <p class="text-[11px] text-gray-600">Fee: <?= formatMoney($esc['escrow_fee'], $esc['currency']) ?></p>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold <?= $esc['buyer_id'] == $user['id'] ? 'bg-blue-500/10 text-blue-400' : 'bg-emerald-500/10 text-emerald-400' ?>">
                        <?= $esc['buyer_id'] == $user['id'] ? 'Buyer' : 'Seller' ?>
                    </span>
                </td>
                <td class="px-6 py-4"><?= statusBadge($esc['status']) ?></td>
                <td class="px-6 py-4">
                    <p class="text-sm text-gray-500"><?= date('M j, Y', strtotime($esc['created_at'])) ?></p>
                </td>
                <td class="px-6 py-4 text-right">
                    <svg class="w-4 h-4 text-gray-600 group-hover:text-accent transition-colors inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Pagination -->
<?= renderPagination($escrows['page'], $escrows['total_pages'], $baseUrl) ?>

<style>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
