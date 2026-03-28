<?php
$pageTitle = 'Open Dispute';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireAuth();

$user = Auth::user();
$db = Database::getInstance();
$escrowId = intval($_GET['escrow_id'] ?? 0);
$escrow = $db->fetch("SELECT e.*, CONCAT(b.first_name,' ',b.last_name) as buyer_name, CONCAT(s.first_name,' ',s.last_name) as seller_name FROM escrows e LEFT JOIN users b ON b.id=e.buyer_id LEFT JOIN users s ON s.id=e.seller_id WHERE e.id = ?", [$escrowId]);

if (!$escrow || !in_array($escrow['status'], ['funded','delivered','in_progress'])) {
    setFlash('error', 'Invalid escrow for dispute');
    redirect(APP_URL . '/pages/escrow/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCSRF();
    $againstUser = ($user['id'] == $escrow['buyer_id']) ? $escrow['seller_id'] : $escrow['buyer_id'];
    $disputeId = 'DSP-' . strtoupper(substr(md5(uniqid()), 0, 8));
    
    $id = $db->insert('disputes', [
        'uuid' => bin2hex(random_bytes(16)),
        'dispute_id' => $disputeId,
        'escrow_id' => $escrowId,
        'raised_by' => $user['id'],
        'against_user' => $againstUser,
        'reason' => post('reason'),
        'description' => post('description'),
        'status' => 'open',
        'deadline' => date('Y-m-d H:i:s', strtotime('+' . Settings::get('dispute_deadline_days', 7) . ' days'))
    ]);
    
    $db->update('escrows', ['status' => 'disputed'], 'id = ?', [$escrowId]);
    
    $db->insert('notifications', ['user_id' => $againstUser, 'type' => 'dispute.opened', 'title' => 'Dispute Opened', 'message' => "A dispute has been opened for escrow {$escrow['escrow_id']}", 'link' => "/pages/disputes/index.php"]);
    
    setFlash('success', 'Dispute ' . $disputeId . ' opened successfully');
    redirect(APP_URL . '/pages/disputes/index.php');
}

require_once APP_ROOT . '/templates/header.php';
?>

<div class="max-w-2xl mx-auto">
    <a href="<?= APP_URL ?>/pages/escrow/view.php?id=<?= $escrowId ?>" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-300 mb-4">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Escrow
    </a>
    
    <h1 class="text-2xl font-bold text-white mb-2">Open Dispute</h1>
    <p class="text-sm text-gray-500 mb-6">Escrow: <?= htmlspecialchars($escrow['escrow_id']) ?> — <?= htmlspecialchars($escrow['title']) ?></p>

    <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-6 text-sm text-red-400">
        Opening a dispute will freeze the escrow funds until resolved by an admin. Please provide clear evidence.
    </div>

    <form method="POST" class="glass-card rounded-2xl border border-white/[0.06] p-6 space-y-5">
        <?= Auth::csrfField() ?>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1.5">Reason</label>
            <select name="reason" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20">
                <option value="">Select reason</option>
                <option value="item_not_received">Item Not Received</option>
                <option value="item_not_as_described">Item Not As Described</option>
                <option value="service_incomplete">Service Incomplete</option>
                <option value="quality_issue">Quality Issue</option>
                <option value="fraud">Suspected Fraud</option>
                <option value="communication">Communication Issues</option>
                <option value="deadline_missed">Deadline Missed</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1.5">Description</label>
            <textarea name="description" rows="5" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 resize-none" placeholder="Explain the issue in detail..."></textarea>
        </div>
        <div class="flex gap-3">
            <a href="<?= APP_URL ?>/pages/escrow/view.php?id=<?= $escrowId ?>" class="flex-1 text-center px-4 py-3 bg-surface-200 text-gray-300 text-sm font-medium rounded-xl hover:bg-surface-300 border border-white/10">Cancel</a>
            <button type="submit" class="flex-1 px-4 py-3 bg-red-600 text-white text-sm font-semibold rounded-xl hover:bg-red-700">Open Dispute</button>
        </div>
    </form>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
