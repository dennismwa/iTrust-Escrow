<?php
$pageTitle = 'View Dispute';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireAuth();

$user = Auth::user();
$db = Database::getInstance();
$id = intval($_GET['id'] ?? 0);

$dispute = $db->fetch(
    "SELECT d.*, e.escrow_id as esc_ref, e.title as escrow_title, e.amount, e.currency, e.status as escrow_status,
            CONCAT(u1.first_name,' ',u1.last_name) as raised_by_name, u1.email as raised_by_email,
            CONCAT(u2.first_name,' ',u2.last_name) as against_name, u2.email as against_email,
            CONCAT(r.first_name,' ',r.last_name) as resolved_by_name
     FROM disputes d 
     JOIN escrows e ON e.id = d.escrow_id
     JOIN users u1 ON u1.id = d.raised_by
     JOIN users u2 ON u2.id = d.against_user
     LEFT JOIN users r ON r.id = d.resolved_by
     WHERE d.id = ?", [$id]
);

if (!$dispute) { setFlash('error', 'Dispute not found'); redirect(APP_URL . '/pages/disputes/index.php'); }

// Check access
$isRaiser = $dispute['raised_by'] == $user['id'];
$isAgainst = $dispute['against_user'] == $user['id'];
$isAdmin = Auth::isAdmin();
if (!$isRaiser && !$isAgainst && !$isAdmin) {
    setFlash('error', 'Access denied'); redirect(APP_URL . '/pages/disputes/index.php');
}

// Handle evidence submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCSRF();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'submit_evidence' && !in_array($dispute['status'], ['resolved','closed'])) {
        $content = trim($_POST['evidence_text'] ?? '');
        $filePath = null;
        $type = 'text';
        
        if (isset($_FILES['evidence_file']) && $_FILES['evidence_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['evidence_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','pdf','doc','docx']) && $file['size'] <= MAX_FILE_SIZE) {
                $fn = 'evidence_' . $id . '_' . $user['id'] . '_' . time() . '.' . $ext;
                $dir = APP_ROOT . '/uploads/attachments';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                if (move_uploaded_file($file['tmp_name'], $dir . '/' . $fn)) {
                    $filePath = '/uploads/attachments/' . $fn;
                    $type = in_array($ext, ['jpg','jpeg','png','gif']) ? 'image' : 'document';
                }
            }
        }
        
        if ($content || $filePath) {
            $db->insert('dispute_evidence', [
                'dispute_id' => $id,
                'user_id' => $user['id'],
                'type' => $type,
                'content' => $content ?: ('File: ' . basename($filePath ?? '')),
                'file_path' => $filePath,
            ]);
            
            // Update status to under_review if it was just opened
            if ($dispute['status'] === 'open') {
                $db->update('disputes', ['status' => 'under_review'], 'id = ?', [$id]);
            }
            
            // Notify the other party
            $notifyUser = $isRaiser ? $dispute['against_user'] : $dispute['raised_by'];
            $db->insert('notifications', [
                'user_id' => $notifyUser,
                'type' => 'dispute.evidence',
                'title' => 'New Evidence Submitted',
                'message' => 'New evidence was added to dispute ' . $dispute['dispute_id'],
                'link' => '/pages/disputes/view.php?id=' . $id
            ]);
            
            setFlash('success', 'Evidence submitted');
        } else {
            setFlash('error', 'Please provide text or upload a file');
        }
        redirect(APP_URL . '/pages/disputes/view.php?id=' . $id);
    }
    
    // Admin resolve
    if ($action === 'resolve' && $isAdmin) {
        $resolution = $_POST['resolution'] ?? '';
        $notes = trim($_POST['resolution_notes'] ?? '');
        $partialAmount = floatval($_POST['partial_amount'] ?? 0);
        
        $db->update('disputes', [
            'status' => 'resolved',
            'resolution' => $resolution,
            'resolution_amount' => $resolution === 'partial_refund' ? $partialAmount : null,
            'resolution_notes' => $notes,
            'resolved_by' => $user['id'],
            'resolved_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);
        
        $escrowModel = new Escrow();
        if ($resolution === 'buyer_refund') {
            $escrowModel->refundBuyer($dispute['escrow_id'], $user['id']);
        } elseif ($resolution === 'seller_release') {
            $escrowModel->releaseFunds($dispute['escrow_id'], $user['id']);
        }
        
        // Notify both parties
        foreach ([$dispute['raised_by'], $dispute['against_user']] as $uid) {
            $db->insert('notifications', [
                'user_id' => $uid, 'type' => 'dispute.resolved',
                'title' => 'Dispute Resolved', 'message' => 'Dispute ' . $dispute['dispute_id'] . ' has been resolved: ' . ucwords(str_replace('_',' ',$resolution)),
                'link' => '/pages/disputes/view.php?id=' . $id
            ]);
        }
        
        setFlash('success', 'Dispute resolved');
        redirect(APP_URL . '/pages/disputes/view.php?id=' . $id);
    }
}

// Get evidence
$evidence = $db->fetchAll(
    "SELECT de.*, CONCAT(u.first_name,' ',u.last_name) as user_name, u.role as user_role
     FROM dispute_evidence de JOIN users u ON u.id = de.user_id 
     WHERE de.dispute_id = ? ORDER BY de.created_at ASC", [$id]
);

require_once APP_ROOT . '/templates/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Breadcrumb -->
    <a href="<?= APP_URL ?>/pages/disputes/index.php" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-300 mb-4">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Disputes
    </a>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-white"><?= htmlspecialchars($dispute['dispute_id']) ?></h1>
                <?= statusBadge($dispute['status']) ?>
            </div>
            <p class="text-sm text-gray-500 mt-1"><?= ucwords(str_replace('_',' ',$dispute['reason'])) ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Description -->
            <div class="glass-card rounded-2xl border border-white/[0.06] p-6">
                <h3 class="font-semibold text-white mb-3">Dispute Description</h3>
                <p class="text-sm text-gray-300 leading-relaxed"><?= nl2br(htmlspecialchars($dispute['description'])) ?></p>
            </div>

            <!-- Resolution (if resolved) -->
            <?php if ($dispute['status'] === 'resolved'): ?>
            <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-6">
                <h3 class="font-semibold text-emerald-400 mb-3">Resolution</h3>
                <p class="text-sm text-gray-300"><span class="font-medium text-white">Decision:</span> <?= ucwords(str_replace('_',' ',$dispute['resolution'])) ?></p>
                <?php if ($dispute['resolution_notes']): ?>
                    <p class="text-sm text-gray-400 mt-2"><?= nl2br(htmlspecialchars($dispute['resolution_notes'])) ?></p>
                <?php endif; ?>
                <p class="text-xs text-gray-500 mt-3">Resolved by <?= htmlspecialchars($dispute['resolved_by_name'] ?? 'Admin') ?> · <?= date('M j, Y g:i A', strtotime($dispute['resolved_at'])) ?></p>
            </div>
            <?php endif; ?>

            <!-- Evidence Thread -->
            <div class="glass-card rounded-2xl border border-white/[0.06] overflow-hidden">
                <div class="px-6 py-4 border-b border-white/[0.06]">
                    <h3 class="font-semibold text-white">Evidence & Responses (<?= count($evidence) ?>)</h3>
                </div>
                <div class="divide-y divide-white/[0.04]">
                    <?php if (empty($evidence)): ?>
                        <p class="px-6 py-8 text-sm text-gray-500 text-center">No evidence submitted yet. Upload proof to support your case.</p>
                    <?php else: foreach ($evidence as $ev): ?>
                    <div class="px-6 py-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-xs font-bold <?= $ev['user_id'] == $dispute['raised_by'] ? 'bg-red-500/10 text-red-400' : ($ev['user_role'] === 'admin' || $ev['user_role'] === 'superadmin' ? 'bg-accent/10 text-accent' : 'bg-blue-500/10 text-blue-400') ?>">
                                <?= strtoupper(substr($ev['user_name'], 0, 1)) ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <p class="text-sm font-semibold text-white"><?= htmlspecialchars($ev['user_name']) ?></p>
                                    <?php if (in_array($ev['user_role'], ['admin','superadmin'])): ?>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-accent/10 text-accent font-bold">ADMIN</span>
                                    <?php elseif ($ev['user_id'] == $dispute['raised_by']): ?>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-red-500/10 text-red-400 font-bold">CLAIMANT</span>
                                    <?php else: ?>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-500/10 text-blue-400 font-bold">RESPONDENT</span>
                                    <?php endif; ?>
                                    <span class="text-[11px] text-gray-600"><?= timeAgo($ev['created_at']) ?></span>
                                </div>
                                <p class="text-sm text-gray-300 leading-relaxed"><?= nl2br(htmlspecialchars($ev['content'])) ?></p>
                                <?php if ($ev['file_path']): ?>
                                <div class="mt-2">
                                    <?php if ($ev['type'] === 'image'): ?>
                                        <a href="<?= APP_URL . '/' . ltrim($ev['file_path'], '/') ?>" target="_blank" class="block">
                                            <img src="<?= APP_URL . '/' . ltrim($ev['file_path'], '/') ?>" alt="Evidence" class="max-w-xs rounded-lg border border-white/[0.06] hover:opacity-90 transition-opacity">
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= APP_URL . '/' . ltrim($ev['file_path'], '/') ?>" target="_blank" class="inline-flex items-center gap-2 text-sm text-accent hover:underline">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            Download Attachment
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>

                <!-- Submit Evidence -->
                <?php if (!in_array($dispute['status'], ['resolved','closed'])): ?>
                <div class="px-6 py-4 border-t border-white/[0.06] bg-white/[0.01]">
                    <form method="POST" enctype="multipart/form-data" class="space-y-3">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="action" value="submit_evidence">
                        <textarea name="evidence_text" rows="3" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 resize-none" placeholder="Describe your evidence or response..."></textarea>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                            <label class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-accent cursor-pointer transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                Attach File
                                <input type="file" name="evidence_file" class="hidden" accept="image/*,.pdf,.doc,.docx">
                            </label>
                            <span class="text-[10px] text-gray-600 evidence-filename"></span>
                            <button type="submit" class="sm:ml-auto px-5 py-2 bg-accent text-surface text-sm font-bold rounded-xl hover:opacity-90 transition-all">Submit Evidence</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Admin Resolution Panel -->
            <?php if ($isAdmin && !in_array($dispute['status'], ['resolved','closed'])): ?>
            <div class="glass-card rounded-2xl border border-amber-500/20 p-6">
                <h3 class="font-semibold text-amber-400 mb-4">Admin Resolution</h3>
                <form method="POST" class="space-y-4">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="action" value="resolve">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Resolution Decision</label>
                        <select name="resolution" required id="resolutionSelect" onchange="document.getElementById('partialRow').classList.toggle('hidden', this.value!=='partial_refund')" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-accent/20">
                            <option value="">Select resolution...</option>
                            <option value="buyer_refund">Full Refund to Buyer</option>
                            <option value="seller_release">Release Funds to Seller</option>
                            <option value="partial_refund">Partial Refund</option>
                            <option value="cancelled">Cancel (No action)</option>
                        </select>
                    </div>
                    <div id="partialRow" class="hidden">
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Partial Refund Amount</label>
                        <input type="number" name="partial_amount" step="0.01" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-accent/20" placeholder="Amount to refund">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Resolution Notes</label>
                        <textarea name="resolution_notes" rows="3" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 resize-none" placeholder="Explain the decision..."></textarea>
                    </div>
                    <button type="submit" onclick="return confirm('Are you sure? This action will process the resolution immediately.')" class="px-6 py-3 bg-amber-600 text-white text-sm font-bold rounded-xl hover:bg-amber-700 transition-colors">Resolve Dispute</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Escrow Info -->
            <div class="glass-card rounded-2xl border border-white/[0.06] p-6">
                <h3 class="font-semibold text-white mb-4">Escrow Details</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Escrow</span><a href="<?= APP_URL ?>/pages/escrow/view.php?id=<?= $dispute['escrow_id'] ?>" class="font-mono text-accent hover:underline"><?= htmlspecialchars($dispute['esc_ref']) ?></a></div>
                    <div class="flex justify-between"><span class="text-gray-500">Title</span><span class="text-gray-300 text-right max-w-[160px] truncate"><?= htmlspecialchars($dispute['escrow_title']) ?></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Amount</span><span class="font-bold text-white"><?= formatMoney($dispute['amount'], $dispute['currency']) ?></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Escrow Status</span><?= statusBadge($dispute['escrow_status']) ?></div>
                </div>
            </div>

            <!-- Parties -->
            <div class="glass-card rounded-2xl border border-white/[0.06] p-6">
                <h3 class="font-semibold text-white mb-4">Parties</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-red-400 mb-1">Raised By</p>
                        <p class="text-sm font-medium text-white"><?= htmlspecialchars($dispute['raised_by_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($dispute['raised_by_email']) ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-blue-400 mb-1">Against</p>
                        <p class="text-sm font-medium text-white"><?= htmlspecialchars($dispute['against_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($dispute['against_email']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="glass-card rounded-2xl border border-white/[0.06] p-6">
                <h3 class="font-semibold text-white mb-4">Timeline</h3>
                <div class="space-y-3">
                    <div class="flex gap-3">
                        <div class="w-2 h-2 rounded-full bg-red-400 mt-1.5 shrink-0"></div>
                        <div><p class="text-xs text-gray-300">Dispute opened</p><p class="text-[10px] text-gray-500"><?= date('M j, Y g:i A', strtotime($dispute['created_at'])) ?></p></div>
                    </div>
                    <?php if ($dispute['deadline']): ?>
                    <div class="flex gap-3">
                        <div class="w-2 h-2 rounded-full bg-amber-400 mt-1.5 shrink-0"></div>
                        <div><p class="text-xs text-gray-300">Resolution deadline</p><p class="text-[10px] text-gray-500"><?= date('M j, Y g:i A', strtotime($dispute['deadline'])) ?></p></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($dispute['resolved_at']): ?>
                    <div class="flex gap-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-400 mt-1.5 shrink-0"></div>
                        <div><p class="text-xs text-gray-300">Resolved</p><p class="text-[10px] text-gray-500"><?= date('M j, Y g:i A', strtotime($dispute['resolved_at'])) ?></p></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('input[name="evidence_file"]')?.addEventListener('change', function() {
    const label = this.closest('form').querySelector('.evidence-filename');
    if (label) label.textContent = this.files[0]?.name || '';
});
</script>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
