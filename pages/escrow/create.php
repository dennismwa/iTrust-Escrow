<?php
$pageTitle = 'Create Escrow';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireAuth();

$user = Auth::user();
$escrowModel = new Escrow();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCSRF();
    
    $data = [
        'buyer_id' => $user['id'],
        'title' => post('title'),
        'description' => post('description'),
        'category' => post('category'),
        'amount' => floatval($_POST['amount'] ?? 0),
        'currency' => post('currency', 'KES'),
        'fee_paid_by' => post('fee_paid_by', 'buyer'),
        'inspection_days' => intval($_POST['inspection_days'] ?? 3),
        'deadline' => post('deadline') ?: null,
        'terms' => post('terms'),
        'invitation_email' => post('seller_email') ?: null,
        'is_milestone' => isset($_POST['is_milestone']) ? 1 : 0,
    ];

    // Check if seller email is provided and find user
    if (!empty($data['invitation_email'])) {
        $db = Database::getInstance();
        $seller = $db->fetch("SELECT id FROM users WHERE email = ?", [$data['invitation_email']]);
        if ($seller) {
            if ($seller['id'] == $user['id']) {
                $errors[] = 'You cannot create an escrow with yourself';
            } else {
                $data['seller_id'] = $seller['id'];
            }
        }
    }

    if (empty($errors)) {
        $result = $escrowModel->create($data);
        if ($result['success']) {
            setFlash('success', 'Escrow ' . $result['escrow_ref'] . ' created successfully!');
            redirect(APP_URL . '/pages/escrow/view.php?id=' . $result['escrow_id']);
        } else {
            $errors = $result['errors'];
        }
    }
}

// Calculate fee preview
$feePercent = Settings::get('escrow_fee_percentage', 2.5);

require_once APP_ROOT . '/templates/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="<?= APP_URL ?>/pages/escrow/index.php" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-300 mb-4">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Escrows
        </a>
        <h1 class="text-2xl font-bold text-white">Create New Escrow</h1>
        <p class="text-sm text-gray-500 mt-1">Set up a secure transaction between you and another party</p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-3 mb-6">
        <?php foreach ($errors as $error): ?>
            <p class="text-sm text-red-400"><?= htmlspecialchars($error) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <?= Auth::csrfField() ?>

        <!-- Transaction Details -->
        <div class="glass-card rounded-2xl border border-white/[0.06] p-6">
            <h2 class="text-lg font-semibold text-white mb-5">Transaction Details</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Transaction Title *</label>
                    <input type="text" name="title" value="<?= htmlspecialchars(post('title')) ?>" required
                           class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50"
                           placeholder="e.g., Toyota Harrier 2020 Purchase">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Description *</label>
                    <textarea name="description" rows="4" required
                              class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50 resize-none"
                              placeholder="Describe what is being bought/sold, including all relevant details..."><?= htmlspecialchars(post('description')) ?></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Category *</label>
                        <select name="category" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50">
                            <option value="">Select category</option>
                            <option value="car" <?= post('category') === 'car' ? 'selected' : '' ?>>Car Purchase</option>
                            <option value="property" <?= post('category') === 'property' ? 'selected' : '' ?>>Property Transaction</option>
                            <option value="freelance" <?= post('category') === 'freelance' ? 'selected' : '' ?>>Freelance Services</option>
                            <option value="marketplace" <?= post('category') === 'marketplace' ? 'selected' : '' ?>>Marketplace Purchase</option>
                            <option value="import_export" <?= post('category') === 'import_export' ? 'selected' : '' ?>>Import/Export Deal</option>
                            <option value="electronics" <?= post('category') === 'electronics' ? 'selected' : '' ?>>Electronics</option>
                            <option value="digital_services" <?= post('category') === 'digital_services' ? 'selected' : '' ?>>Digital Services</option>
                            <option value="other" <?= post('category') === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Delivery Deadline</label>
                        <input type="date" name="deadline" value="<?= htmlspecialchars(post('deadline')) ?>"
                               class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50"
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Details -->
        <div class="glass-card rounded-2xl border border-white/[0.06] p-6">
            <h2 class="text-lg font-semibold text-white mb-5">Financial Details</h2>
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Amount *</label>
                        <input type="number" name="amount" id="escrowAmount" value="<?= htmlspecialchars(post('amount')) ?>" required min="500" step="0.01"
                               class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50"
                               placeholder="0.00" oninput="calculateFee()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Currency</label>
                        <select name="currency" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50">
                            <option value="KES">KES - Kenyan Shilling</option>
                            <option value="USD">USD - US Dollar</option>
                            <option value="NGN">NGN - Nigerian Naira</option>
                            <option value="GHS">GHS - Ghanaian Cedi</option>
                            <option value="ZAR">ZAR - South African Rand</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Who pays the fee?</label>
                        <select name="fee_paid_by" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50">
                            <option value="buyer">Buyer pays fee</option>
                            <option value="seller">Seller pays fee</option>
                            <option value="split">Split 50/50</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Inspection Period (days)</label>
                        <select name="inspection_days" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50">
                            <option value="1">1 day</option>
                            <option value="3" selected>3 days</option>
                            <option value="5">5 days</option>
                            <option value="7">7 days</option>
                            <option value="14">14 days</option>
                            <option value="30">30 days</option>
                        </select>
                    </div>
                </div>

                <!-- Fee Calculator Preview -->
                <div class="bg-surface-100 rounded-xl p-4 mt-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Fee Breakdown</p>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-gray-400">Transaction Amount</span><span id="previewAmount" class="font-medium">KSh 0.00</span></div>
                        <div class="flex justify-between"><span class="text-gray-400">Escrow Fee (<?= $feePercent ?>%)</span><span id="previewFee" class="font-medium">KSh 0.00</span></div>
                        <div class="border-t border-white/10 pt-2 flex justify-between"><span class="text-white font-semibold">Total</span><span id="previewTotal" class="font-bold text-accent">KSh 0.00</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seller / Counterparty -->
        <div class="glass-card rounded-2xl border border-white/[0.06] p-6">
            <h2 class="text-lg font-semibold text-white mb-5">Counterparty</h2>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Seller Email</label>
                <input type="email" name="seller_email" value="<?= htmlspecialchars(post('seller_email')) ?>"
                       class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50"
                       placeholder="seller@example.com">
                <p class="text-xs text-gray-400 mt-1.5">Enter the seller's email. If they don't have an account, an invitation link will be sent.</p>
            </div>
        </div>

        <!-- Terms -->
        <div class="glass-card rounded-2xl border border-white/[0.06] p-6">
            <h2 class="text-lg font-semibold text-white mb-5">Terms & Conditions</h2>
            <textarea name="terms" rows="4"
                      class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50 resize-none"
                      placeholder="Enter any specific terms or conditions for this escrow..."><?= htmlspecialchars(post('terms')) ?></textarea>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="<?= APP_URL ?>/pages/escrow/index.php" class="px-6 py-3 text-sm font-medium text-gray-300 glass-card border border-white/10 rounded-xl hover:bg-surface-100 transition-colors">Cancel</a>
            <button type="submit" class="px-8 py-3 bg-accent hover:bg-accent-dark text-surface text-sm font-semibold rounded-xl transition-all shadow-sm hover:shadow-md">
                Create Escrow
            </button>
        </div>
    </form>
</div>

<script>
const feePercent = <?= $feePercent ?>;
function calculateFee() {
    const amount = parseFloat(document.getElementById('escrowAmount').value) || 0;
    const fee = Math.round(amount * (feePercent / 100) * 100) / 100;
    const total = amount + fee;
    document.getElementById('previewAmount').textContent = 'KSh ' + amount.toLocaleString('en', {minimumFractionDigits: 2});
    document.getElementById('previewFee').textContent = 'KSh ' + fee.toLocaleString('en', {minimumFractionDigits: 2});
    document.getElementById('previewTotal').textContent = 'KSh ' + total.toLocaleString('en', {minimumFractionDigits: 2});
}
</script>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
