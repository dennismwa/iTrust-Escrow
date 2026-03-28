<?php
$pageTitle = 'Payments & Withdrawals';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireRole(['admin', 'superadmin']);
$db = Database::getInstance();
$pg = new PaymentGateway();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCSRF();
    $action = $_POST['action'] ?? '';
    
    // Deposit actions
    if ($action === 'confirm_deposit') {
        $result = $pg->confirmDeposit(intval($_POST['txn_id'] ?? 0), $_SESSION['user_id']);
        setFlash($result['success'] ? 'success' : 'error', $result['success'] ? 'Deposit confirmed & wallet credited' : ($result['error'] ?? 'Failed'));
    } elseif ($action === 'reject_deposit') {
        $result = $pg->rejectDeposit(intval($_POST['txn_id'] ?? 0), $_SESSION['user_id'], post('reject_reason'));
        setFlash($result['success'] ? 'success' : 'error', $result['success'] ? 'Deposit rejected' : 'Failed');
    }
    // Withdrawal actions
    elseif ($action === 'approve_withdrawal') {
        $wId = intval($_POST['withdrawal_id'] ?? 0);
        $db->update('withdrawals', ['status'=>'completed','processed_by'=>$_SESSION['user_id'],'processed_at'=>date('Y-m-d H:i:s')], 'id=?', [$wId]);
        $w = $db->fetch("SELECT * FROM withdrawals WHERE id=?", [$wId]);
        if ($w) { $db->insert('notifications', ['user_id'=>$w['user_id'],'type'=>'withdrawal.completed','title'=>'Withdrawal Completed','message'=>'Your withdrawal of '.formatMoney($w['amount'],$w['currency']).' has been processed.','link'=>'/pages/wallet/withdrawals.php']); }
        setFlash('success', 'Withdrawal approved');
    } elseif ($action === 'reject_withdrawal') {
        $wId = intval($_POST['withdrawal_id'] ?? 0);
        $w = $db->fetch("SELECT * FROM withdrawals WHERE id=?", [$wId]);
        if ($w) {
            $wallet = $db->fetch("SELECT * FROM wallets WHERE user_id=? AND currency=?", [$w['user_id'],$w['currency']]);
            if ($wallet) $db->update('wallets', ['balance'=>$wallet['balance']+$w['amount']], 'id=?', [$wallet['id']]);
            $db->update('withdrawals', ['status'=>'rejected','admin_notes'=>post('admin_notes'),'processed_by'=>$_SESSION['user_id'],'processed_at'=>date('Y-m-d H:i:s')], 'id=?', [$wId]);
            $db->insert('notifications', ['user_id'=>$w['user_id'],'type'=>'withdrawal.rejected','title'=>'Withdrawal Rejected','message'=>'Your withdrawal was rejected. '.post('admin_notes'),'link'=>'/pages/wallet/withdrawals.php']);
        }
        setFlash('success', 'Withdrawal rejected, funds returned');
    }
    redirect(APP_URL . '/pages/admin/payments.php');
}

// Pending deposits
$pendingDeposits = $db->fetchAll("SELECT t.*, CONCAT(u.first_name,' ',u.last_name) as user_name, u.email FROM transactions t JOIN users u ON u.id=t.user_id WHERE t.type='deposit' AND t.status='pending' ORDER BY t.created_at DESC LIMIT 50");

// All withdrawals
$withdrawals = $db->fetchAll("SELECT w.*, CONCAT(u.first_name,' ',u.last_name) as user_name, u.email FROM withdrawals w JOIN users u ON u.id=w.user_id ORDER BY FIELD(w.status,'pending','processing','completed','rejected'), w.created_at DESC LIMIT 50");

require_once APP_ROOT . '/templates/header.php';
?>
<div class="max-w-5xl mx-auto">
    <div class="mb-6"><h1 class="text-2xl font-bold text-white">Payments & Withdrawals</h1><p class="text-sm text-gray-500 mt-1">Confirm deposits and process withdrawal requests</p></div>

    <!-- Pending Deposits -->
    <?php if (!empty($pendingDeposits)): ?>
    <div class="glass-card rounded-2xl border border-amber-500/10 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-white/[0.06] flex items-center gap-3">
            <div class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></div>
            <h2 class="font-bold text-white">Pending Deposits (<?= count($pendingDeposits) ?>)</h2>
        </div>
        <div class="divide-y divide-white/[0.04]">
            <?php foreach ($pendingDeposits as $dep):
                $meta = json_decode($dep['metadata'] ?? '{}', true) ?: [];
            ?>
            <div class="px-6 py-4">
                <div class="flex flex-col sm:flex-row items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-white"><?= htmlspecialchars($dep['user_name']) ?> <span class="text-gray-500 font-normal">· <?= htmlspecialchars($dep['email']) ?></span></p>
                        <p class="text-lg font-bold text-amber-400 mt-1"><?= formatMoney($dep['amount'], $dep['currency']) ?></p>
                        <p class="text-xs text-gray-500 mt-1"><?= ucfirst(str_replace('_',' ',$dep['payment_method'])) ?> · <?= htmlspecialchars($dep['transaction_ref']) ?> · <?= timeAgo($dep['created_at']) ?></p>
                        <?php if (!empty($meta['sender_name'])): ?><p class="text-xs text-gray-400 mt-1">Sender: <?= htmlspecialchars($meta['sender_name']) ?></p><?php endif; ?>
                        <?php if (!empty($meta['reference'])): ?><p class="text-xs text-gray-400">Ref: <?= htmlspecialchars($meta['reference']) ?></p><?php endif; ?>
                        <?php if (!empty($meta['proof'])): ?>
                            <a href="<?= APP_URL . '/' . ltrim($meta['proof'], '/') ?>" target="_blank" class="inline-flex items-center gap-1 text-xs text-accent mt-2 hover:underline">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                View Proof
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                        <form method="POST"><input type="hidden" name="txn_id" value="<?= $dep['id'] ?>"><?= Auth::csrfField() ?>
                            <button name="action" value="confirm_deposit" onclick="return confirm('Confirm this deposit and credit the user wallet?')" class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-700">Confirm</button>
                        </form>
                        <form method="POST"><input type="hidden" name="txn_id" value="<?= $dep['id'] ?>"><?= Auth::csrfField() ?>
                            <input type="hidden" name="reject_reason" value="Payment not verified">
                            <button name="action" value="reject_deposit" onclick="return confirm('Reject this deposit?')" class="px-4 py-2 bg-red-600/20 text-red-400 text-xs font-bold rounded-xl hover:bg-red-600/30 border border-red-500/20">Reject</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Withdrawals -->
    <div class="glass-card rounded-2xl border border-white/[0.06] overflow-hidden">
        <div class="px-6 py-4 border-b border-white/[0.06]"><h2 class="font-bold text-white">Withdrawals</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full"><thead><tr class="border-b border-white/[0.06] bg-white/[0.02]">
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">User</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Method</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Account</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="text-right px-6 py-3"></th>
            </tr></thead><tbody class="divide-y divide-white/[0.04]">
            <?php if (empty($withdrawals)): ?>
                <tr><td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">No withdrawals</td></tr>
            <?php else: foreach ($withdrawals as $w):
                $acct = json_decode($w['account_details'] ?? '{}', true) ?: [];
            ?>
            <tr class="hover:bg-white/[0.02]">
                <td class="px-6 py-4"><p class="text-sm font-medium text-white"><?= htmlspecialchars($w['user_name']) ?></p><p class="text-xs text-gray-500"><?= htmlspecialchars($w['email']) ?></p></td>
                <td class="px-6 py-4"><span class="text-sm font-bold text-white"><?= formatMoney($w['amount'],$w['currency']) ?></span><br><span class="text-[10px] text-gray-500">Fee: <?= formatMoney($w['fee'],$w['currency']) ?></span></td>
                <td class="px-6 py-4"><span class="text-sm"><?= ucfirst($w['method']) ?></span></td>
                <td class="px-6 py-4"><span class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($acct['phone'] ?? $acct['account'] ?? '-') ?></span></td>
                <td class="px-6 py-4"><?= statusBadge($w['status']) ?></td>
                <td class="px-6 py-4"><span class="text-xs text-gray-500"><?= date('M j, Y', strtotime($w['created_at'])) ?></span></td>
                <td class="px-6 py-4 text-right">
                    <?php if ($w['status'] === 'pending'): ?>
                    <form method="POST" class="inline-flex gap-1"><?= Auth::csrfField() ?><input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>">
                        <button name="action" value="approve_withdrawal" onclick="return confirm('Approve this withdrawal?')" class="text-xs px-3 py-1.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-medium">Approve</button>
                        <button name="action" value="reject_withdrawal" onclick="return confirm('Reject and return funds?')" class="text-xs px-3 py-1.5 bg-red-600/20 text-red-400 rounded-lg hover:bg-red-600/30 border border-red-500/20 font-medium">Reject</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody></table>
        </div>
    </div>
</div>
<?php require_once APP_ROOT . '/templates/footer.php'; ?>
