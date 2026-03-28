<?php
$pageTitle = 'KYC Verification';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireRole(['admin', 'superadmin']);
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCSRF();
    $docId = intval($_POST['doc_id'] ?? 0);
    $action = post('action');
    $doc = $db->fetch("SELECT * FROM kyc_documents WHERE id = ?", [$docId]);
    if ($doc) {
        if ($action === 'approve') {
            $db->update('kyc_documents', ['status' => 'approved', 'reviewed_by' => $_SESSION['user_id'], 'reviewed_at' => date('Y-m-d H:i:s')], 'id = ?', [$docId]);
            $allApproved = $db->fetchColumn("SELECT COUNT(*) FROM kyc_documents WHERE user_id = ? AND status != 'approved'", [$doc['user_id']]);
            if ($allApproved == 0) { $db->update('users', ['kyc_status' => 'approved'], 'id = ?', [$doc['user_id']]); }
        } elseif ($action === 'reject') {
            $db->update('kyc_documents', ['status' => 'rejected', 'rejection_reason' => post('rejection_reason'), 'reviewed_by' => $_SESSION['user_id'], 'reviewed_at' => date('Y-m-d H:i:s')], 'id = ?', [$docId]);
            $db->update('users', ['kyc_status' => 'rejected'], 'id = ?', [$doc['user_id']]);
        }
        setFlash('success', 'KYC document ' . $action . 'd');
    }
    redirect(APP_URL . '/pages/admin/kyc.php');
}

$pendingDocs = $db->fetchAll("SELECT kd.*, CONCAT(u.first_name,' ',u.last_name) as user_name, u.email FROM kyc_documents kd JOIN users u ON u.id=kd.user_id WHERE kd.status = 'pending' ORDER BY kd.created_at ASC");

require_once APP_ROOT . '/templates/header.php';
?>
<div class="mb-6"><h1 class="text-2xl font-bold text-white">KYC Verification</h1><p class="text-sm text-gray-500 mt-1"><?= count($pendingDocs) ?> pending reviews</p></div>
<div class="space-y-4">
<?php foreach ($pendingDocs as $doc): ?>
<div class="glass-card rounded-2xl border border-white/[0.06] p-6 flex items-center justify-between gap-4">
    <div>
        <p class="text-sm font-semibold text-white"><?= htmlspecialchars($doc['user_name']) ?></p>
        <p class="text-xs text-gray-500"><?= htmlspecialchars($doc['email']) ?></p>
        <p class="text-xs text-gray-400 mt-1"><?= ucwords(str_replace('_',' ',$doc['document_type'])) ?> · <?= htmlspecialchars($doc['original_name']) ?></p>
    </div>
    <div class="flex items-center gap-2">
        <a href="<?= APP_URL . $doc['file_path'] ?>" target="_blank" class="px-3 py-1.5 text-xs bg-surface-200 text-gray-300 rounded-lg hover:bg-surface-300 font-medium">View File</a>
        <form method="POST" class="inline"><input type="hidden" name="doc_id" value="<?= $doc['id'] ?>"><input type="hidden" name="action" value="approve"><?= Auth::csrfField() ?>
            <button class="px-3 py-1.5 text-xs bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-medium">Approve</button></form>
        <form method="POST" class="inline"><input type="hidden" name="doc_id" value="<?= $doc['id'] ?>"><input type="hidden" name="action" value="reject"><?= Auth::csrfField() ?>
            <input type="text" name="rejection_reason" placeholder="Reason" class="text-xs px-2 py-1.5 border rounded-lg w-28">
            <button class="px-3 py-1.5 text-xs bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">Reject</button></form>
    </div>
</div>
<?php endforeach; ?>
<?php if (empty($pendingDocs)): ?><div class="glass-card rounded-2xl border border-white/[0.06] p-12 text-center"><p class="text-sm text-gray-400">No pending KYC reviews</p></div><?php endif; ?>
</div>
<?php require_once APP_ROOT . '/templates/footer.php'; ?>
