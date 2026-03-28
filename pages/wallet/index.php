<?php
$pageTitle = 'Wallet';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireAuth();

$user = Auth::user();
$db = Database::getInstance();
$wallet = $db->fetch("SELECT * FROM wallets WHERE user_id = ? LIMIT 1", [$user['id']]);
$pg = new PaymentGateway();
$activeGateways = $pg->getActiveGateways();

// Handle deposit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'deposit') {
    Auth::verifyCSRF();
    $amount = floatval($_POST['amount'] ?? 0);
    $method = post('payment_method', 'mpesa');
    $currency = post('currency', $user['preferred_currency']);
    
    if ($amount < 100) {
        setFlash('error', 'Minimum deposit is 100'); redirect(APP_URL . '/pages/wallet/index.php');
    }
    
    if ($method === 'mpesa') {
        $phone = post('mpesa_phone', $user['phone']);
        $result = $pg->mpesaStkPush($phone, $amount, 'DEP-' . $user['id'], $user['id']);
        if ($result['success']) { setFlash('success', $result['message']); }
        else { setFlash('error', $result['error']); }
        
    } elseif ($method === 'bank_transfer') {
        $proofPath = null;
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','pdf'])) {
                $fn = 'proof_' . $user['id'] . '_' . time() . '.' . $ext;
                $dir = APP_ROOT . '/uploads/attachments';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $dir . '/' . $fn)) {
                    $proofPath = '/uploads/attachments/' . $fn;
                }
            }
        }
        $details = ['sender_name' => post('sender_name'), 'reference' => post('bank_reference')];
        $result = $pg->createManualDeposit($user['id'], $amount, $currency, 'bank_transfer', $proofPath, $details);
        if ($result['success']) { setFlash('success', $result['message']); }
        else { setFlash('error', $result['error'] ?? 'Failed'); }
        
    } elseif ($method === 'manual') {
        $proofPath = null;
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','pdf'])) {
                $fn = 'proof_' . $user['id'] . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['payment_proof']['tmp_name'], APP_ROOT . '/uploads/attachments/' . $fn);
                $proofPath = '/uploads/attachments/' . $fn;
            }
        }
        $result = $pg->createManualDeposit($user['id'], $amount, $currency, 'manual', $proofPath, ['notes' => post('deposit_notes')]);
        if ($result['success']) { setFlash('success', $result['message']); }
        else { setFlash('error', $result['error'] ?? 'Failed'); }
        
    } else {
        setFlash('error', 'Invalid payment method');
    }
    redirect(APP_URL . '/pages/wallet/index.php');
}

$transactions = $db->fetchAll(
    "SELECT t.*, e.escrow_id as esc_ref FROM transactions t LEFT JOIN escrows e ON e.id=t.escrow_id WHERE t.user_id = ? ORDER BY t.created_at DESC LIMIT 25",
    [$user['id']]
);

// Get bank details for display
$bankGw = $db->fetch("SELECT config FROM payment_gateways WHERE name = 'bank_transfer' AND is_active = 1");
$bankConfig = $bankGw ? (json_decode($bankGw['config'], true) ?: []) : [];

require_once APP_ROOT . '/templates/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Wallet</h1>
        <p class="text-sm text-gray-500 mt-1">Manage your funds</p>
    </div>

    <!-- Balance Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 mb-8">
        <div class="rounded-2xl p-6 text-white border border-accent/10" style="background:rgba(200,245,69,.06)">
            <p class="text-sm text-accent/80 font-medium">Available Balance</p>
            <p class="text-2xl sm:text-3xl font-bold mt-2 truncate"><?= formatMoney($wallet['balance'] ?? 0, $user['preferred_currency']) ?></p>
            <div class="flex gap-2 mt-4">
                <button onclick="document.getElementById('depositModal').classList.remove('hidden')" class="px-4 py-2 bg-accent text-surface text-sm font-bold rounded-xl hover:opacity-90 transition-all">Deposit</button>
                <a href="<?= APP_URL ?>/pages/wallet/withdrawals.php" class="px-4 py-2 bg-white/10 hover:bg-white/15 text-white text-sm font-medium rounded-xl transition-colors">Withdraw</a>
            </div>
        </div>
        <div class="glass-card rounded-2xl p-6 border border-white/[0.06]">
            <p class="text-sm text-gray-500 font-medium">In Escrow</p>
            <p class="text-xl sm:text-2xl font-bold text-white mt-2 truncate"><?= formatMoney($wallet['escrow_balance'] ?? 0, $user['preferred_currency']) ?></p>
            <p class="text-xs text-gray-500 mt-2">Held in active escrows</p>
        </div>
        <div class="glass-card rounded-2xl p-6 border border-white/[0.06]">
            <p class="text-sm text-gray-500 font-medium">Total Earned</p>
            <p class="text-xl sm:text-2xl font-bold text-white mt-2 truncate"><?= formatMoney($wallet['total_earned'] ?? 0, $user['preferred_currency']) ?></p>
            <p class="text-xs text-gray-500 mt-2">Lifetime from escrows</p>
        </div>
    </div>

    <!-- Transactions -->
    <div class="glass-card rounded-2xl border border-white/[0.06] overflow-hidden">
        <div class="px-6 py-4 border-b border-white/[0.06]"><h3 class="font-semibold text-white">Transaction History</h3></div>
        <div class="divide-y divide-white/[0.04]">
            <?php if (empty($transactions)): ?>
                <p class="px-6 py-12 text-sm text-gray-500 text-center">No transactions yet</p>
            <?php else: foreach ($transactions as $txn): ?>
            <div class="flex items-center justify-between px-6 py-4 hover:bg-white/[0.02]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center <?= in_array($txn['type'], ['escrow_release','deposit','escrow_refund']) ? 'bg-emerald-500/10' : 'bg-red-500/10' ?>">
                        <?php if (in_array($txn['type'], ['escrow_release','deposit','escrow_refund'])): ?>
                            <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
                        <?php else: ?>
                            <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/></svg>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-white"><?= htmlspecialchars($txn['description'] ?? ucwords(str_replace('_',' ',$txn['type']))) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($txn['transaction_ref']) ?> · <?= ucfirst($txn['payment_method'] ?? '-') ?> · <?= timeAgo($txn['created_at']) ?></p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold <?= in_array($txn['type'], ['escrow_release','deposit','escrow_refund']) ? 'text-emerald-400' : 'text-red-400' ?>">
                        <?= in_array($txn['type'], ['escrow_release','deposit','escrow_refund']) ? '+' : '-' ?><?= formatMoney($txn['amount'], $txn['currency']) ?>
                    </p>
                    <?= statusBadge($txn['status']) ?>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- Deposit Modal -->
<div id="depositModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="glass-card rounded-2xl p-6 w-full max-w-lg mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-white">Deposit Funds</h3>
            <button onclick="document.getElementById('depositModal').classList.add('hidden')" class="p-1 text-gray-500 hover:text-white"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>

        <!-- Method Tabs -->
        <div class="flex gap-2 mb-5 overflow-x-auto pb-1">
            <?php
            $methods = [];
            foreach ($activeGateways as $gw) {
                $ico = ['mpesa'=>'📱','stripe'=>'💳','paypal'=>'🅿️','bank_transfer'=>'🏦','crypto'=>'₿','manual'=>'📋'];
                $methods[] = ['id'=>$gw['name'],'label'=>$gw['display_name'],'icon'=>$ico[$gw['name']]??'💰'];
            }
            // Always add manual option
            $methods[] = ['id'=>'manual','label'=>'Manual','icon'=>'📋'];
            foreach ($methods as $i => $m):
            ?>
            <button onclick="showMethod('<?= $m['id'] ?>')" id="tab-<?= $m['id'] ?>" class="method-tab shrink-0 px-4 py-2 rounded-xl text-xs font-medium border transition-all <?= $i===0 ? 'border-accent/30 text-accent bg-accent/5' : 'border-white/10 text-gray-400 hover:text-white hover:bg-white/[0.04]' ?>">
                <?= $m['icon'] ?> <?= $m['label'] ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- M-Pesa Form -->
        <form method="POST" enctype="multipart/form-data" id="form-mpesa" class="method-form space-y-4">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="action" value="deposit">
            <input type="hidden" name="payment_method" value="mpesa">
            <input type="hidden" name="currency" value="KES">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Amount (KES)</label>
                <input type="number" name="amount" min="100" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-accent/20" placeholder="Enter amount">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">M-Pesa Phone Number</label>
                <input type="tel" name="mpesa_phone" value="<?= htmlspecialchars($user['phone']) ?>" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-accent/20" placeholder="0712345678">
            </div>
            <p class="text-xs text-gray-500">You will receive an STK push on your phone to complete payment.</p>
            <button type="submit" class="w-full py-3 bg-accent text-surface text-sm font-bold rounded-xl hover:opacity-90">Pay with M-Pesa</button>
        </form>

        <!-- Bank Transfer Form -->
        <form method="POST" enctype="multipart/form-data" id="form-bank_transfer" class="method-form hidden space-y-4">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="action" value="deposit">
            <input type="hidden" name="payment_method" value="bank_transfer">
            <input type="hidden" name="currency" value="<?= $user['preferred_currency'] ?>">
            <?php if (!empty($bankConfig['bank_name'])): ?>
            <div class="rounded-xl bg-blue-500/5 border border-blue-500/10 p-4 text-sm space-y-1">
                <p class="text-xs font-bold text-blue-400 uppercase tracking-wider mb-2">Transfer to:</p>
                <p class="text-gray-300"><span class="text-gray-500">Bank:</span> <?= htmlspecialchars($bankConfig['bank_name'] ?? '') ?></p>
                <p class="text-gray-300"><span class="text-gray-500">Account:</span> <?= htmlspecialchars($bankConfig['account_number'] ?? '') ?></p>
                <p class="text-gray-300"><span class="text-gray-500">Name:</span> <?= htmlspecialchars($bankConfig['account_name'] ?? '') ?></p>
                <?php if (!empty($bankConfig['branch'])): ?><p class="text-gray-300"><span class="text-gray-500">Branch:</span> <?= htmlspecialchars($bankConfig['branch']) ?></p><?php endif; ?>
            </div>
            <?php endif; ?>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Amount</label>
                <input type="number" name="amount" min="100" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-accent/20"></div>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Sender Name</label>
                <input type="text" name="sender_name" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-accent/20" placeholder="Name on bank account"></div>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Transaction Reference</label>
                <input type="text" name="bank_reference" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-accent/20" placeholder="Bank reference/receipt number"></div>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Payment Proof (screenshot/receipt)</label>
                <input type="file" name="payment_proof" accept="image/*,.pdf" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-surface-200 file:text-gray-300 hover:file:bg-surface-300"></div>
            <button type="submit" class="w-full py-3 bg-accent text-surface text-sm font-bold rounded-xl hover:opacity-90">Submit Bank Transfer</button>
        </form>

        <!-- Stripe Form -->
        <form method="POST" id="form-stripe" class="method-form hidden space-y-4">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="action" value="deposit">
            <input type="hidden" name="payment_method" value="stripe">
            <input type="hidden" name="currency" value="<?= $user['preferred_currency'] ?>">
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Amount</label>
                <input type="number" name="amount" min="100" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-accent/20"></div>
            <p class="text-xs text-gray-500">You will be redirected to Stripe secure checkout.</p>
            <button type="submit" class="w-full py-3 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700">Pay with Card</button>
        </form>

        <!-- PayPal Form -->
        <form method="POST" id="form-paypal" class="method-form hidden space-y-4">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="action" value="deposit">
            <input type="hidden" name="payment_method" value="paypal">
            <input type="hidden" name="currency" value="USD">
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Amount (USD)</label>
                <input type="number" name="amount" min="5" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-accent/20"></div>
            <button type="submit" class="w-full py-3 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700">Pay with PayPal</button>
        </form>

        <!-- Crypto Form -->
        <form method="POST" id="form-crypto" class="method-form hidden space-y-4">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="action" value="deposit">
            <input type="hidden" name="payment_method" value="manual">
            <input type="hidden" name="currency" value="USD">
            <?php $cryptoGw = $db->fetch("SELECT config FROM payment_gateways WHERE name='crypto' AND is_active=1"); $cc = $cryptoGw ? json_decode($cryptoGw['config'],true) : []; ?>
            <?php if (!empty($cc['btc_address'])): ?><div class="rounded-xl bg-amber-500/5 border border-amber-500/10 p-3"><p class="text-xs font-bold text-amber-400 mb-1">BTC Address</p><p class="text-xs text-gray-300 font-mono break-all"><?= htmlspecialchars($cc['btc_address']) ?></p></div><?php endif; ?>
            <?php if (!empty($cc['eth_address'])): ?><div class="rounded-xl bg-blue-500/5 border border-blue-500/10 p-3"><p class="text-xs font-bold text-blue-400 mb-1">ETH/USDT Address</p><p class="text-xs text-gray-300 font-mono break-all"><?= htmlspecialchars($cc['eth_address']) ?></p></div><?php endif; ?>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Amount (USD equivalent)</label>
                <input type="number" name="amount" min="10" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-accent/20"></div>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">TX Hash / Proof</label>
                <input type="text" name="deposit_notes" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-accent/20" placeholder="Paste transaction hash"></div>
            <button type="submit" class="w-full py-3 bg-amber-600 text-white text-sm font-bold rounded-xl hover:bg-amber-700">Submit Crypto Deposit</button>
        </form>

        <!-- Manual Form -->
        <form method="POST" enctype="multipart/form-data" id="form-manual" class="method-form hidden space-y-4">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="action" value="deposit">
            <input type="hidden" name="payment_method" value="manual">
            <input type="hidden" name="currency" value="<?= $user['preferred_currency'] ?>">
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Amount</label>
                <input type="number" name="amount" min="100" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-accent/20"></div>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Payment Proof</label>
                <input type="file" name="payment_proof" accept="image/*,.pdf" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-surface-200 file:text-gray-300"></div>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Notes</label>
                <textarea name="deposit_notes" rows="2" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-accent/20 resize-none" placeholder="Describe your payment method..."></textarea></div>
            <div class="rounded-xl bg-amber-500/5 border border-amber-500/10 p-3 text-xs text-amber-400">Manual deposits require admin confirmation. Please allow up to 24 hours.</div>
            <button type="submit" class="w-full py-3 bg-accent text-surface text-sm font-bold rounded-xl hover:opacity-90">Submit Deposit</button>
        </form>
    </div>
</div>

<script>
function showMethod(id) {
    document.querySelectorAll('.method-form').forEach(f => f.classList.add('hidden'));
    document.querySelectorAll('.method-tab').forEach(t => { t.classList.remove('border-accent/30','text-accent','bg-accent/5'); t.classList.add('border-white/10','text-gray-400'); });
    const form = document.getElementById('form-' + id);
    const tab = document.getElementById('tab-' + id);
    if (form) form.classList.remove('hidden');
    if (tab) { tab.classList.add('border-accent/30','text-accent','bg-accent/5'); tab.classList.remove('border-white/10','text-gray-400'); }
}
</script>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
