<?php
$pageTitle = 'Manage Escrows';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireRole(['admin', 'superadmin']);

$db = Database::getInstance();
$escrowModel = new Escrow();
$page = max(1, intval($_GET['page'] ?? 1));
$status = get('status');
$search = get('search');

$filters = [];
if ($status) $filters['status'] = $status;
if ($search) $filters['search'] = $search;

$escrows = $escrowModel->list($filters, $page, 20);

require_once APP_ROOT . '/templates/header.php';
?>
<div class="flex items-center justify-between mb-6">
    <div><h1 class="text-2xl font-bold text-white">All Escrows</h1><p class="text-sm text-gray-500 mt-1"><?= number_format($escrows['total']) ?> total</p></div>
</div>
<div class="glass-card rounded-2xl border border-white/[0.06] p-4 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search..." class="flex-1 px-4 py-2.5 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20">
        <select name="status" class="px-4 py-2.5 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600">
            <option value="">All Statuses</option>
            <?php foreach(['draft','pending','funded','in_progress','delivered','completed','disputed','cancelled','refunded'] as $s): ?>
            <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-6 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-xl hover:bg-gray-800">Filter</button>
    </form>
</div>
<div class="glass-card rounded-2xl border border-white/[0.06] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead><tr class="border-b border-white/[0.06] bg-white/[0.02]">
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">ID</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Title</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Buyer</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Seller</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="text-right px-6 py-3.5"></th>
            </tr></thead>
            <tbody class="divide-y divide-white/[0.04]">
                <?php foreach ($escrows['data'] as $e): ?>
                <tr class="hover:bg-white/[0.02]">
                    <td class="px-6 py-4"><span class="text-sm font-mono text-accent"><?= htmlspecialchars($e['escrow_id']) ?></span></td>
                    <td class="px-6 py-4"><span class="text-sm text-white truncate max-w-[180px] block"><?= htmlspecialchars($e['title']) ?></span></td>
                    <td class="px-6 py-4"><span class="text-sm text-gray-300"><?= htmlspecialchars($e['buyer_name']) ?></span></td>
                    <td class="px-6 py-4"><span class="text-sm text-gray-300"><?= htmlspecialchars($e['seller_name'] ?? '-') ?></span></td>
                    <td class="px-6 py-4"><span class="text-sm font-semibold"><?= formatMoney($e['amount'], $e['currency']) ?></span></td>
                    <td class="px-6 py-4"><?= statusBadge($e['status']) ?></td>
                    <td class="px-6 py-4"><span class="text-sm text-gray-500"><?= date('M j', strtotime($e['created_at'])) ?></span></td>
                    <td class="px-6 py-4 text-right"><a href="<?= APP_URL ?>/pages/escrow/view.php?id=<?= $e['id'] ?>" class="text-sm text-accent hover:text-accent font-medium">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= renderPagination($escrows['page'], $escrows['total_pages'], "?status={$status}&search={$search}") ?>
</div>
<?php require_once APP_ROOT . '/templates/footer.php'; ?>
