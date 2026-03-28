<?php
$pageTitle = 'Disputes';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireAuth();

$user = Auth::user();
$db = Database::getInstance();

$disputes = $db->fetchAll(
    "SELECT d.*, e.escrow_id as esc_ref, e.title as escrow_title, e.amount, e.currency,
            CONCAT(u1.first_name,' ',u1.last_name) as raised_by_name,
            CONCAT(u2.first_name,' ',u2.last_name) as against_name
     FROM disputes d 
     JOIN escrows e ON e.id = d.escrow_id
     JOIN users u1 ON u1.id = d.raised_by
     JOIN users u2 ON u2.id = d.against_user
     WHERE e.buyer_id = ? OR e.seller_id = ?
     ORDER BY d.created_at DESC",
    [$user['id'], $user['id']]
);

require_once APP_ROOT . '/templates/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div><h1 class="text-2xl font-bold text-white">Disputes</h1><p class="text-sm text-gray-500 mt-1">Manage your escrow disputes</p></div>
    </div>

    <?php if (empty($disputes)): ?>
    <div class="glass-card rounded-2xl border border-white/[0.06] p-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-surface-200 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <p class="text-sm text-gray-500">No disputes</p>
        <p class="text-xs text-gray-600 mt-1">Disputes can be opened from an escrow's detail page when delivery is marked.</p>
    </div>
    <?php else: ?>

    <!-- Mobile: Card layout -->
    <div class="lg:hidden space-y-3 mb-6">
        <?php foreach ($disputes as $d): ?>
        <a href="<?= APP_URL ?>/pages/disputes/view.php?id=<?= $d['id'] ?>" class="block glass-card rounded-2xl border border-white/[0.06] p-4 hover:border-red-500/20 transition-all active:scale-[0.99]">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div>
                    <span class="text-xs font-mono font-bold text-red-400"><?= htmlspecialchars($d['dispute_id']) ?></span>
                    <p class="text-sm font-medium text-white mt-0.5"><?= htmlspecialchars($d['escrow_title']) ?></p>
                </div>
                <?= statusBadge($d['status']) ?>
            </div>
            <div class="flex items-center justify-between text-xs text-gray-500">
                <span><?= ucwords(str_replace('_',' ',$d['reason'])) ?></span>
                <span class="font-semibold text-white"><?= formatMoney($d['amount'],$d['currency']) ?></span>
            </div>
            <p class="text-[11px] text-gray-600 mt-1"><?= date('M j, Y', strtotime($d['created_at'])) ?></p>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Desktop: Table -->
    <div class="hidden lg:block glass-card rounded-2xl border border-white/[0.06] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead><tr class="border-b border-white/[0.06] bg-white/[0.02]">
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Dispute</th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Escrow</th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Reason</th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Date</th>
                    <th class="text-right px-6 py-3.5"></th>
                </tr></thead>
                <tbody class="divide-y divide-white/[0.04]">
                    <?php foreach ($disputes as $d): ?>
                    <tr class="hover:bg-white/[0.02] cursor-pointer" onclick="window.location='<?= APP_URL ?>/pages/disputes/view.php?id=<?= $d['id'] ?>'">
                        <td class="px-6 py-4"><span class="text-sm font-mono font-medium text-red-400"><?= htmlspecialchars($d['dispute_id']) ?></span></td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-white"><?= htmlspecialchars($d['esc_ref']) ?></p>
                            <p class="text-xs text-gray-500 truncate max-w-[160px]"><?= htmlspecialchars($d['escrow_title']) ?></p>
                        </td>
                        <td class="px-6 py-4"><span class="text-sm text-gray-300"><?= ucwords(str_replace('_',' ',$d['reason'])) ?></span></td>
                        <td class="px-6 py-4"><span class="text-sm font-semibold text-white"><?= formatMoney($d['amount'],$d['currency']) ?></span></td>
                        <td class="px-6 py-4"><?= statusBadge($d['status']) ?></td>
                        <td class="px-6 py-4"><span class="text-sm text-gray-500"><?= date('M j, Y', strtotime($d['created_at'])) ?></span></td>
                        <td class="px-6 py-4 text-right"><svg class="w-4 h-4 text-gray-600 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- How Disputes Work -->
    <div class="mt-8 glass-card rounded-2xl border border-white/[0.06] p-5">
        <h3 class="text-sm font-bold text-white mb-3">How Disputes Work</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs text-gray-400">
            <div class="flex gap-2"><span class="text-accent font-bold">1.</span> The buyer opens a dispute from the escrow page after delivery is marked. Both parties are notified.</div>
            <div class="flex gap-2"><span class="text-accent font-bold">2.</span> Both parties submit evidence — screenshots, documents, messages — to support their case.</div>
            <div class="flex gap-2"><span class="text-accent font-bold">3.</span> An admin reviews all evidence and resolves the dispute — refund, release, or partial refund.</div>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
