<?php
$pageTitle = 'Agent Dashboard';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireRole(['agent', 'admin', 'superadmin']);
$user = Auth::user();
$db = Database::getInstance();
$assignedEscrows = $db->fetchAll("SELECT e.*, CONCAT(b.first_name,' ',b.last_name) as buyer_name, CONCAT(s.first_name,' ',s.last_name) as seller_name FROM escrows e LEFT JOIN users b ON b.id=e.buyer_id LEFT JOIN users s ON s.id=e.seller_id WHERE e.agent_id = ? ORDER BY e.created_at DESC", [$user['id']]);
require_once APP_ROOT . '/templates/header.php';
?>
<div class="mb-6"><h1 class="text-2xl font-bold text-white">Agent Dashboard</h1><p class="text-sm text-gray-500 mt-1">Manage your assigned escrows</p></div>
<div class="glass-card rounded-2xl border border-white/[0.06] overflow-hidden">
    <div class="overflow-x-auto"><table class="w-full"><thead><tr class="border-b border-white/[0.06] bg-white/[0.02]">
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Escrow</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Buyer</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Seller</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
        <th class="text-right px-6 py-3"></th>
    </tr></thead><tbody class="divide-y divide-white/[0.04]">
    <?php if (empty($assignedEscrows)): ?><tr><td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">No assigned escrows</td></tr>
    <?php else: foreach ($assignedEscrows as $e): ?>
    <tr class="hover:bg-white/[0.02]">
        <td class="px-6 py-4"><span class="text-sm font-mono text-accent"><?= htmlspecialchars($e['escrow_id']) ?></span><p class="text-xs text-gray-500"><?= htmlspecialchars($e['title']) ?></p></td>
        <td class="px-6 py-4"><span class="text-sm"><?= htmlspecialchars($e['buyer_name']) ?></span></td>
        <td class="px-6 py-4"><span class="text-sm"><?= htmlspecialchars($e['seller_name'] ?? '-') ?></span></td>
        <td class="px-6 py-4"><span class="text-sm font-semibold"><?= formatMoney($e['amount'],$e['currency']) ?></span></td>
        <td class="px-6 py-4"><?= statusBadge($e['status']) ?></td>
        <td class="px-6 py-4 text-right"><a href="<?= APP_URL ?>/pages/escrow/view.php?id=<?= $e['id'] ?>" class="text-sm text-accent font-medium">View</a></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody></table></div>
</div>
<?php require_once APP_ROOT . '/templates/footer.php'; ?>
