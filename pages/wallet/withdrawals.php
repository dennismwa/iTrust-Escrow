<?php
$pageTitle = 'Withdrawals';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireAuth();
$user = Auth::user();
$db = Database::getInstance();
$wallet = $db->fetch("SELECT * FROM wallets WHERE user_id = ? LIMIT 1", [$user['id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCSRF();
    $amount = floatval($_POST['amount'] ?? 0);
    $method = post('method', 'mpesa');
    $minW = floatval(Settings::get('min_withdrawal', 1000));
    $fee = floatval(Settings::get('withdrawal_fee', 50));
    if ($amount < $minW) { setFlash('error', "Minimum withdrawal is " . formatMoney($minW)); }
    elseif ($amount > $wallet['balance']) { setFlash('error', 'Insufficient balance'); }
    else {
        $db->update('wallets', ['balance' => $wallet['balance'] - $amount], 'id = ?', [$wallet['id']]);
        $db->insert('withdrawals', [
            'uuid' => bin2hex(random_bytes(16)), 'user_id' => $user['id'], 'amount' => $amount,
            'fee' => $fee, 'net_amount' => $amount - $fee, 'currency' => $user['preferred_currency'],
            'method' => $method, 'account_details' => json_encode(['phone' => post('account')]), 'status' => 'pending'
        ]);
        setFlash('success', 'Withdrawal request submitted');
    }
    redirect(APP_URL . '/pages/wallet/withdrawals.php');
}
$withdrawals = $db->fetchAll("SELECT * FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC LIMIT 20", [$user['id']]);
require_once APP_ROOT . '/templates/header.php';
?>
<div class="max-w-3xl mx-auto">
    <div class="mb-6"><h1 class="text-2xl font-bold text-white">Withdrawals</h1></div>
    <div class="glass-card rounded-2xl border border-white/[0.06] p-6 mb-6">
        <h3 class="font-semibold text-white mb-4">Request Withdrawal</h3>
        <p class="text-sm text-gray-500 mb-4">Available: <span class="font-bold text-white"><?= formatMoney($wallet['balance'] ?? 0) ?></span></p>
        <form method="POST" class="space-y-4">
            <?= Auth::csrfField() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Amount</label>
                    <input type="number" name="amount" required min="<?= Settings::get('min_withdrawal', 1000) ?>" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20"></div>
                <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Method</label>
                    <select name="method" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600">
                        <option value="mpesa">M-Pesa</option><option value="bank">Bank Transfer</option>
                    </select></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Account (Phone/Bank Account)</label>
                <input type="text" name="account" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600" placeholder="e.g., 0712345678"></div>
            <button type="submit" class="px-6 py-3 bg-accent hover:bg-accent-dark text-surface text-sm font-semibold rounded-xl">Submit Withdrawal</button>
        </form>
    </div>
    <div class="glass-card rounded-2xl border border-white/[0.06] overflow-hidden">
        <div class="px-6 py-4 border-b border-white/[0.06]"><h3 class="font-semibold text-white">Withdrawal History</h3></div>
        <div class="divide-y divide-white/[0.04]">
            <?php if (empty($withdrawals)): ?><p class="px-6 py-8 text-sm text-gray-400 text-center">No withdrawals yet</p>
            <?php else: foreach ($withdrawals as $w): ?>
            <div class="flex items-center justify-between px-6 py-4">
                <div><p class="text-sm font-medium"><?= formatMoney($w['amount'],$w['currency']) ?></p><p class="text-xs text-gray-500"><?= ucfirst($w['method']) ?> · <?= timeAgo($w['created_at']) ?></p></div>
                <?= statusBadge($w['status']) ?>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
<?php require_once APP_ROOT . '/templates/footer.php'; ?>
