<?php
$pageTitle = 'Manage Disputes';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireRole(['admin', 'superadmin']);
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCSRF();
    $disputeId = intval($_POST['dispute_id'] ?? 0);
    $action = post('action');
    if ($action === 'resolve') {
        $resolution = post('resolution');
        $notes = post('resolution_notes');
        $db->update('disputes', [
            'status' => 'resolved', 'resolution' => $resolution, 'resolution_notes' => $notes,
            'resolved_by' => $_SESSION['user_id'], 'resolved_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$disputeId]);
        $dispute = $db->fetch("SELECT * FROM disputes WHERE id = ?", [$disputeId]);
        if ($dispute) {
            $escrowModel = new Escrow();
            if ($resolution === 'buyer_refund') { $escrowModel->refundBuyer($dispute['escrow_id'], $_SESSION['user_id']); }
            elseif ($resolution === 'seller_release') { $escrowModel->releaseFunds($dispute['escrow_id'], $_SESSION['user_id']); }
        }
        setFlash('success', 'Dispute resolved');
    }
    redirect(APP_URL . '/pages/admin/disputes.php');
}

$disputes = $db->fetchAll("SELECT d.*, e.escrow_id as esc_ref, e.title as escrow_title, e.amount, e.currency, CONCAT(u1.first_name,' ',u1.last_name) as raised_by_name, CONCAT(u2.first_name,' ',u2.last_name) as against_name FROM disputes d JOIN escrows e ON e.id=d.escrow_id JOIN users u1 ON u1.id=d.raised_by JOIN users u2 ON u2.id=d.against_user ORDER BY d.created_at DESC LIMIT 50");

require_once APP_ROOT . '/templates/header.php';
?>
<div class="mb-6"><h1 class="text-2xl font-bold text-white">Disputes</h1></div>
<div class="glass-card rounded-2xl border border-white/[0.06] overflow-hidden">
    <div class="overflow-x-auto"><table class="w-full"><thead><tr class="border-b border-white/[0.06] bg-white/[0.02]">
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">ID</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Escrow</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Raised By</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Against</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Reason</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
        <th class="text-right px-6 py-3"></th>
    </tr></thead><tbody class="divide-y divide-white/[0.04]">
    <?php foreach ($disputes as $d): ?>
    <tr class="hover:bg-white/[0.02]">
        <td class="px-6 py-4"><span class="text-sm font-mono text-red-400"><?= htmlspecialchars($d['dispute_id']) ?></span></td>
        <td class="px-6 py-4"><span class="text-sm"><?= htmlspecialchars($d['esc_ref']) ?></span></td>
        <td class="px-6 py-4"><span class="text-sm"><?= htmlspecialchars($d['raised_by_name']) ?></span></td>
        <td class="px-6 py-4"><span class="text-sm"><?= htmlspecialchars($d['against_name']) ?></span></td>
        <td class="px-6 py-4"><span class="text-sm"><?= ucwords(str_replace('_',' ',$d['reason'])) ?></span></td>
        <td class="px-6 py-4"><span class="text-sm font-semibold"><?= formatMoney($d['amount'],$d['currency']) ?></span></td>
        <td class="px-6 py-4"><?= statusBadge($d['status']) ?></td>
        <td class="px-6 py-4 text-right">
            <?php if ($d['status'] !== 'resolved' && $d['status'] !== 'closed'): ?>
            <form method="POST" class="flex flex-wrap items-center gap-2 mt-2">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="dispute_id" value="<?= $d['id'] ?>">
                <input type="hidden" name="action" value="resolve">
                <select name="resolution" required class="text-xs px-2 py-1 bg-[#131825] border border-white/10 rounded-lg text-white">
                    <option value="">Resolve...</option>
                    <option value="buyer_refund">Refund Buyer</option>
                    <option value="seller_release">Release to Seller</option>
                </select>
                <input type="text" name="resolution_notes" placeholder="Notes" class="text-xs px-2 py-1 bg-[#131825] border border-white/10 rounded-lg text-white w-full sm:w-24">
                <button type="submit" class="text-xs px-3 py-1 bg-accent text-surface rounded-lg hover:bg-accent-dark">Resolve</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php require_once APP_ROOT . '/templates/footer.php'; ?>
