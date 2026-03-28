<?php
$pageTitle = 'View Escrow';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireAuth();

$user = Auth::user();
$escrowModel = new Escrow();
$db = Database::getInstance();

$id = intval($_GET['id'] ?? 0);
$escrow = $escrowModel->getById($id);

if (!$escrow) {
    setFlash('error', 'Escrow not found');
    redirect(APP_URL . '/pages/escrow/index.php');
}

// Check access
$isBuyer = $escrow['buyer_id'] == $user['id'];
$isSeller = $escrow['seller_id'] == $user['id'];
$isAdminUser = Auth::isAdmin();
$isAgentAssigned = $escrow['agent_id'] == $user['id'];

if (!$isBuyer && !$isSeller && !$isAdminUser && !$isAgentAssigned) {
    setFlash('error', 'You do not have access to this escrow');
    redirect(APP_URL . '/pages/escrow/index.php');
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    Auth::verifyCSRF();
    $action = $_POST['action'];
    $result = ['success' => false, 'errors' => ['Invalid action']];

    switch ($action) {
        case 'fund':
            $result = $escrowModel->fund($id, $user['id']);
            break;
        case 'mark_delivered':
            $result = $escrowModel->markDelivered($id, $user['id']);
            break;
        case 'confirm_delivery':
            $result = $escrowModel->confirmDelivery($id, $user['id']);
            break;
        case 'cancel':
            $result = $escrowModel->cancel($id, $user['id']);
            break;
        case 'release':
            if ($isAdminUser) $result = $escrowModel->releaseFunds($id, $user['id']);
            break;
        case 'refund':
            if ($isAdminUser) $result = $escrowModel->refundBuyer($id, $user['id']);
            break;
    }

    if ($result['success']) {
        setFlash('success', 'Action completed successfully');
    } else {
        setFlash('error', implode(', ', $result['errors'] ?? ['Action failed']));
    }
    redirect(APP_URL . '/pages/escrow/view.php?id=' . $id);
}

// Get milestones
$milestones = $db->fetchAll("SELECT * FROM escrow_milestones WHERE escrow_id = ? ORDER BY order_num", [$id]);

// Get attachments
$attachments = $db->fetchAll("SELECT ea.*, CONCAT(u.first_name,' ',u.last_name) as uploader FROM escrow_attachments ea JOIN users u ON u.id = ea.user_id WHERE ea.escrow_id = ? ORDER BY ea.created_at DESC", [$id]);

// Get messages/conversation
$conversation = $db->fetch("SELECT * FROM conversations WHERE escrow_id = ? AND type = 'escrow'", [$id]);
$messages = [];
if ($conversation) {
    $messages = $db->fetchAll(
        "SELECT m.*, CONCAT(u.first_name,' ',u.last_name) as sender_name, u.avatar as sender_avatar 
         FROM messages m JOIN users u ON u.id = m.sender_id 
         WHERE m.conversation_id = ? ORDER BY m.created_at ASC",
        [$conversation['id']]
    );
}

// Get contract
$contract = $db->fetch("SELECT * FROM escrow_contracts WHERE escrow_id = ?", [$id]);

// Get user wallet for funding check
$userWallet = $db->fetch("SELECT * FROM wallets WHERE user_id = ? AND currency = ?", [$user['id'], $escrow['currency']]);
$walletBalance = $userWallet['balance'] ?? 0;
$canFund = $walletBalance >= $escrow['total_amount'];

// Get dispute if any
$dispute = $db->fetch("SELECT * FROM disputes WHERE escrow_id = ? ORDER BY created_at DESC LIMIT 1", [$id]);

// Timeline / activity
$activities = $db->fetchAll(
    "SELECT * FROM activity_logs WHERE entity_type = 'escrows' AND entity_id = ? ORDER BY created_at DESC LIMIT 20",
    [$id]
);

require_once APP_ROOT . '/templates/header.php';
?>

<div class="max-w-5xl mx-auto">
    <!-- Breadcrumb -->
    <a href="<?= APP_URL ?>/pages/escrow/index.php" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-300 mb-4">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Escrows
    </a>

    <!-- Header -->
    <div class="flex flex-col gap-4 mb-6">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-bold text-white"><?= htmlspecialchars($escrow['escrow_id']) ?></h1>
                <?= statusBadge($escrow['status']) ?>
            </div>
            <p class="text-sm text-gray-500 mt-1 truncate"><?= htmlspecialchars($escrow['title']) ?></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <?php if ($isBuyer && in_array($escrow['status'], ['pending','draft'])): ?>
                <?php if ($canFund): ?>
                <form method="POST" class="inline"><input type="hidden" name="action" value="fund"><?= Auth::csrfField() ?>
                    <button type="submit" onclick="return confirm('Fund this escrow? <?= formatMoney($escrow['total_amount'],$escrow['currency']) ?> will be deducted from your wallet.')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl transition-colors">Fund Escrow</button>
                </form>
                <?php else: ?>
                <button onclick="document.getElementById('depositPrompt').classList.remove('hidden')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl transition-colors">Fund Escrow</button>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($isSeller && in_array($escrow['status'], ['funded','in_progress'])): ?>
                <form method="POST" class="inline"><input type="hidden" name="action" value="mark_delivered"><?= Auth::csrfField() ?>
                    <button type="submit" onclick="return confirm('Mark this escrow as delivered?')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-colors">Mark Delivered</button>
                </form>
            <?php endif; ?>
            <?php if ($isBuyer && $escrow['status'] === 'delivered'): ?>
                <form method="POST" class="inline"><input type="hidden" name="action" value="confirm_delivery"><?= Auth::csrfField() ?>
                    <button type="submit" onclick="return confirm('Confirm delivery and release funds to seller?')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl transition-colors">Confirm & Release</button>
                </form>
                <a href="<?= APP_URL ?>/pages/disputes/create.php?escrow_id=<?= $id ?>" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-colors">Open Dispute</a>
            <?php endif; ?>
            <?php if (($isBuyer || $isAdminUser) && in_array($escrow['status'], ['draft','pending'])): ?>
                <form method="POST" class="inline"><input type="hidden" name="action" value="cancel"><?= Auth::csrfField() ?>
                    <button type="submit" onclick="return confirm('Cancel this escrow?')" class="px-4 py-2 bg-surface-200 hover:bg-surface-300 text-gray-300 text-sm font-medium rounded-xl border border-white/10 transition-colors">Cancel</button>
                </form>
            <?php endif; ?>
            <?php if ($isAdminUser && in_array($escrow['status'], ['funded','delivered','disputed'])): ?>
                <form method="POST" class="inline"><input type="hidden" name="action" value="release"><?= Auth::csrfField() ?>
                    <button type="submit" onclick="return confirm('Release funds to seller?')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl transition-colors">Release Funds</button>
                </form>
                <form method="POST" class="inline"><input type="hidden" name="action" value="refund"><?= Auth::csrfField() ?>
                    <button type="submit" onclick="return confirm('Refund buyer?')" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-xl transition-colors">Refund Buyer</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Progress Tracker -->
    <div class="glass-card rounded-2xl border border-white/[0.06] p-4 sm:p-6 mb-6 overflow-x-auto">
        <div class="flex items-center justify-between min-w-[500px] sm:min-w-0">
            <?php
            $steps = ['draft'=>'Created','pending'=>'Pending','funded'=>'Funded','in_progress'=>'In Progress','delivered'=>'Delivered','completed'=>'Completed'];
            $statusOrder = array_keys($steps);
            $currentIdx = array_search($escrow['status'], $statusOrder);
            if ($currentIdx === false) $currentIdx = -1;
            $isCancelled = in_array($escrow['status'], ['cancelled','refunded','disputed']);
            $i = 0;
            foreach ($steps as $sKey => $sLabel):
                $done = $currentIdx >= $i && !$isCancelled;
                $active = $currentIdx == $i && !$isCancelled;
                $last = $i === count($steps)-1;
            ?>
            <div class="flex items-center <?= $last ? '' : 'flex-1' ?>">
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-all <?= $done ? 'bg-accent/20 border-accent text-accent' : ($isCancelled && $i > $currentIdx ? 'bg-surface-200 border-white/10 text-gray-600' : 'bg-surface-200 border-white/10 text-gray-500') ?>">
                        <?php if ($done && !$active): ?>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <?php else: echo $i+1; endif; ?>
                    </div>
                    <p class="text-[10px] sm:text-[11px] font-medium mt-1.5 whitespace-nowrap <?= $done ? 'text-accent' : 'text-gray-600' ?>"><?= $sLabel ?></p>
                </div>
                <?php if (!$last): ?>
                <div class="flex-1 h-0.5 mx-2 rounded <?= ($currentIdx > $i && !$isCancelled) ? 'bg-accent/40' : 'bg-white/[0.06]' ?>"></div>
                <?php endif; $i++; ?>
            </div>
            <?php endforeach; ?>
            <?php if ($isCancelled): ?>
            <div class="flex flex-col items-center ml-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold border-2 bg-red-500/10 border-red-500/30 text-red-400">!</div>
                <p class="text-[11px] font-medium mt-1.5 text-red-400"><?= ucfirst($escrow['status']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Escrow Details Card -->
            <div class="glass-card rounded-2xl border border-white/[0.06] p-6">
                <h3 class="font-semibold text-white mb-4">Transaction Details</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Description</p>
                        <p class="text-sm text-gray-300 leading-relaxed"><?= nl2br(htmlspecialchars($escrow['description'])) ?></p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Category</p>
                            <p class="text-sm text-white font-medium"><?= ucwords(str_replace('_',' ',$escrow['category'])) ?></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Inspection Period</p>
                            <p class="text-sm text-white font-medium"><?= $escrow['inspection_period_days'] ?> days</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Delivery Deadline</p>
                            <p class="text-sm text-white font-medium"><?= $escrow['delivery_deadline'] ? date('M j, Y', strtotime($escrow['delivery_deadline'])) : 'Not set' ?></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Created</p>
                            <p class="text-sm text-white font-medium"><?= date('M j, Y g:i A', strtotime($escrow['created_at'])) ?></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Contract Hash</p>
                            <p class="text-xs font-mono text-gray-500 truncate"><?= htmlspecialchars($escrow['contract_hash'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                    <?php if ($escrow['terms']): ?>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Terms & Conditions</p>
                        <div class="bg-surface-100 rounded-xl p-4 text-sm text-gray-300"><?= nl2br(htmlspecialchars($escrow['terms'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Auto-complete timer -->
            <?php if ($escrow['status'] === 'delivered' && $escrow['auto_complete_at']): ?>
            <div class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="text-sm font-semibold text-amber-400">Auto-completion scheduled</p>
                    <p class="text-sm text-amber-400 mt-0.5">This escrow will auto-complete on <strong><?= date('M j, Y g:i A', strtotime($escrow['auto_complete_at'])) ?></strong> if no dispute is raised.</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Messages / Chat -->
            <div class="glass-card rounded-2xl border border-white/[0.06] overflow-hidden">
                <div class="px-6 py-4 border-b border-white/[0.06]">
                    <h3 class="font-semibold text-white">Messages</h3>
                </div>
                <div class="max-h-96 overflow-y-auto p-4 space-y-3" id="messagesContainer">
                    <?php if (empty($messages)): ?>
                        <p class="text-sm text-gray-400 text-center py-8">No messages yet. Start the conversation!</p>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                        <div class="flex gap-3 <?= $msg['sender_id'] == $user['id'] ? 'flex-row-reverse' : '' ?>">
                            <div class="w-8 h-8 rounded-full bg-accent/20 flex items-center justify-center shrink-0 text-xs font-bold text-accent">
                                <?= strtoupper(substr($msg['sender_name'], 0, 1)) ?>
                            </div>
                            <div class="max-w-[70%] <?= $msg['sender_id'] == $user['id'] ? 'bg-accent/10 border-accent/20' : 'bg-surface-100 border-white/[0.06]' ?> border rounded-2xl px-4 py-3">
                                <p class="text-xs font-medium <?= $msg['sender_id'] == $user['id'] ? 'text-accent' : 'text-gray-500' ?>"><?= htmlspecialchars($msg['sender_name']) ?></p>
                                <p class="text-sm text-gray-300 mt-1"><?= nl2br(htmlspecialchars($msg['content'])) ?></p>
                                <p class="text-[10px] text-gray-400 mt-1"><?= timeAgo($msg['created_at']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if ($conversation && !in_array($escrow['status'], ['completed','cancelled','refunded'])): ?>
                <div class="px-4 py-3 border-t border-white/[0.06]">
                    <form method="POST" action="<?= APP_URL ?>/api/messages.php" id="messageForm" class="flex gap-2">
                        <input type="hidden" name="conversation_id" value="<?= $conversation['id'] ?>">
                        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= Auth::generateCSRF() ?>">
                        <input type="text" name="content" required placeholder="Type a message..." 
                               class="flex-1 px-4 py-2.5 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50">
                        <button type="submit" class="px-4 py-2.5 bg-accent hover:bg-accent-dark text-surface rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Activity Timeline -->
            <div class="glass-card rounded-2xl border border-white/[0.06] p-6">
                <h3 class="font-semibold text-white mb-4">Activity Timeline</h3>
                <div class="space-y-4">
                    <?php if (empty($activities)): ?>
                        <p class="text-sm text-gray-400">No activity recorded yet</p>
                    <?php else: ?>
                        <?php foreach ($activities as $act): ?>
                        <div class="flex gap-3">
                            <div class="w-2 h-2 rounded-full bg-accent mt-2 shrink-0"></div>
                            <div>
                                <p class="text-sm text-gray-300"><?= htmlspecialchars($act['description']) ?></p>
                                <p class="text-xs text-gray-400 mt-0.5"><?= date('M j, Y g:i A', strtotime($act['created_at'])) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Financial Summary -->
            <div class="glass-card rounded-2xl border border-white/[0.06] p-6">
                <h3 class="font-semibold text-white mb-4">Financial Summary</h3>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Amount</span>
                        <span class="font-semibold text-white"><?= formatMoney($escrow['amount'], $escrow['currency']) ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Escrow Fee</span>
                        <span class="font-medium text-gray-300"><?= formatMoney($escrow['escrow_fee'], $escrow['currency']) ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Fee Paid By</span>
                        <span class="font-medium text-gray-300 capitalize"><?= $escrow['fee_paid_by'] ?></span>
                    </div>
                    <div class="border-t border-white/[0.06] pt-3 flex justify-between text-sm">
                        <span class="font-semibold text-white">Total</span>
                        <span class="font-bold text-accent text-lg"><?= formatMoney($escrow['total_amount'], $escrow['currency']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Parties -->
            <div class="glass-card rounded-2xl border border-white/[0.06] p-6">
                <h3 class="font-semibold text-white mb-4">Parties</h3>
                <div class="space-y-4">
                    <!-- Buyer -->
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-400 text-sm font-bold">
                            <?= strtoupper(substr($escrow['buyer_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white"><?= htmlspecialchars($escrow['buyer_name']) ?></p>
                            <p class="text-xs text-blue-400 font-medium">Buyer</p>
                        </div>
                        <?php if ($escrow['buyer_trust'] > 0): ?>
                        <span class="ml-auto text-xs font-medium text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full">★ <?= number_format($escrow['buyer_trust'],0) ?>%</span>
                        <?php endif; ?>
                    </div>
                    <!-- Seller -->
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-green-500/10 flex items-center justify-center text-green-400 text-sm font-bold">
                            <?= $escrow['seller_name'] ? strtoupper(substr($escrow['seller_name'], 0, 1)) : '?' ?>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white"><?= htmlspecialchars($escrow['seller_name'] ?? 'Pending Invitation') ?></p>
                            <p class="text-xs text-green-600 font-medium">Seller</p>
                        </div>
                        <?php if ($escrow['seller_trust'] > 0): ?>
                        <span class="ml-auto text-xs font-medium text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full">★ <?= number_format($escrow['seller_trust'],0) ?>%</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($escrow['agent_name']): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-purple-500/10 flex items-center justify-center text-purple-400 text-sm font-bold">
                            <?= strtoupper(substr($escrow['agent_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white"><?= htmlspecialchars($escrow['agent_name']) ?></p>
                            <p class="text-xs text-purple-400 font-medium">Agent</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Attachments -->
            <div class="glass-card rounded-2xl border border-white/[0.06] p-6">
                <h3 class="font-semibold text-white mb-4">Attachments</h3>
                <?php if (empty($attachments)): ?>
                    <p class="text-sm text-gray-400">No attachments yet</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($attachments as $att): ?>
                        <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-100">
                            <div class="w-8 h-8 rounded-lg bg-surface-200 flex items-center justify-center">
                                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-300 truncate"><?= htmlspecialchars($att['file_name']) ?></p>
                                <p class="text-xs text-gray-400"><?= htmlspecialchars($att['uploader']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!in_array($escrow['status'], ['completed','cancelled','refunded'])): ?>
                <form method="POST" action="<?= APP_URL ?>/api/attachments.php" enctype="multipart/form-data" class="mt-3">
                    <input type="hidden" name="escrow_id" value="<?= $id ?>">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= Auth::generateCSRF() ?>">
                    <label class="flex items-center justify-center gap-2 px-4 py-2 border-2 border-dashed border-white/10 rounded-xl text-sm text-gray-500 hover:bg-surface-100 hover:border-gray-300 cursor-pointer transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Upload File
                        <input type="file" name="attachment" class="hidden" onchange="this.form.submit()">
                    </label>
                </form>
                <?php endif; ?>
            </div>

            <!-- Dispute Status -->
            <?php if ($dispute): ?>
            <div class="bg-red-500/10 rounded-2xl border border-red-500/20 p-6">
                <h3 class="font-semibold text-red-400 mb-2">Dispute Active</h3>
                <p class="text-sm text-red-400 mb-3"><?= htmlspecialchars($dispute['dispute_id']) ?> — <?= ucwords(str_replace('_',' ',$dispute['reason'])) ?></p>
                <?= statusBadge($dispute['status']) ?>
                <a href="<?= APP_URL ?>/pages/disputes/view.php?id=<?= $dispute['id'] ?>" class="block mt-3 text-sm font-medium text-red-400 hover:text-red-400">View Dispute →</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Insufficient Funds Deposit Prompt -->
<?php if ($isBuyer && in_array($escrow['status'], ['pending','draft']) && !$canFund): ?>
<div id="depositPrompt" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="w-full max-w-sm mx-4 rounded-3xl overflow-hidden" style="background:#131825;border:1px solid rgba(255,255,255,.08)">
        <!-- M-Pesa style header -->
        <div class="px-6 pt-6 pb-4 text-center">
            <div class="w-14 h-14 rounded-full bg-red-500/10 flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h3 class="text-lg font-bold text-white">Insufficient Balance</h3>
            <p class="text-sm text-gray-400 mt-1">You need more funds to complete this escrow</p>
        </div>
        <!-- Balance breakdown -->
        <div class="mx-6 rounded-2xl bg-[#0B0F19] p-4 space-y-3 mb-4">
            <div class="flex justify-between text-sm"><span class="text-gray-500">Escrow Total</span><span class="font-bold text-white"><?= formatMoney($escrow['total_amount'], $escrow['currency']) ?></span></div>
            <div class="flex justify-between text-sm"><span class="text-gray-500">Your Balance</span><span class="font-bold <?= $canFund ? 'text-emerald-400' : 'text-red-400' ?>"><?= formatMoney($walletBalance, $escrow['currency']) ?></span></div>
            <div class="border-t border-white/[0.06] pt-3 flex justify-between text-sm"><span class="text-gray-400">Shortfall</span><span class="font-bold text-amber-400"><?= formatMoney(max(0, $escrow['total_amount'] - $walletBalance), $escrow['currency']) ?></span></div>
        </div>
        <!-- Actions -->
        <div class="px-6 pb-6 space-y-2">
            <a href="<?= APP_URL ?>/pages/wallet/index.php" class="flex items-center justify-center gap-2 w-full py-3 bg-accent text-surface font-bold rounded-xl text-sm hover:opacity-90 transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Deposit Funds
            </a>
            <button onclick="document.getElementById('depositPrompt').classList.add('hidden')" class="w-full py-3 text-sm text-gray-400 hover:text-white transition-colors">Maybe Later</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.getElementById('messageForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    fetch(form.action, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => { if (data.success) location.reload(); });
});
</script>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
