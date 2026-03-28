<?php
$pageTitle = 'KYC Verification';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireAuth();

$user = Auth::user();
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    Auth::verifyCSRF();
    $docType = post('document_type');
    $file = $_FILES['document'];
    
    if ($file['error'] === UPLOAD_ERR_OK && $file['size'] <= MAX_FILE_SIZE) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','pdf'])) {
            $filename = 'kyc_' . $user['id'] . '_' . $docType . '_' . time() . '.' . $ext;
            $uploadPath = APP_ROOT . '/uploads/kyc/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $db->insert('kyc_documents', [
                    'user_id' => $user['id'],
                    'document_type' => $docType,
                    'file_path' => '/uploads/kyc/' . $filename,
                    'original_name' => $file['name'],
                    'status' => 'pending'
                ]);
                if ($user['kyc_status'] === 'none') {
                    $db->update('users', ['kyc_status' => 'pending'], 'id = ?', [$user['id']]);
                }
                setFlash('success', 'Document uploaded for review');
            }
        } else { setFlash('error', 'Only JPG, PNG, or PDF allowed'); }
    } else { setFlash('error', 'File too large or upload error'); }
    redirect(APP_URL . '/pages/kyc/index.php');
}

$documents = $db->fetchAll("SELECT * FROM kyc_documents WHERE user_id = ? ORDER BY created_at DESC", [$user['id']]);
$docTypes = ['national_id' => 'National ID / Passport', 'selfie' => 'Selfie Verification', 'proof_of_address' => 'Proof of Address', 'business_registration' => 'Business Registration'];

require_once APP_ROOT . '/templates/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">KYC Verification</h1>
        <p class="text-sm text-gray-500 mt-1">Verify your identity to unlock all platform features</p>
    </div>

    <!-- Status Banner -->
    <div class="mb-6 rounded-2xl p-5 <?= $user['kyc_status']==='approved' ? 'bg-emerald-500/10 border border-emerald-500/20' : ($user['kyc_status']==='pending' ? 'bg-amber-500/10 border border-amber-500/20' : ($user['kyc_status']==='rejected' ? 'bg-red-500/10 border border-red-500/20' : 'bg-surface-100 border border-white/10')) ?>">
        <div class="flex items-center gap-3">
            <?php if ($user['kyc_status'] === 'approved'): ?>
                <svg class="w-8 h-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <div><p class="font-semibold text-emerald-400">Verified</p><p class="text-sm text-emerald-400">Your identity has been verified</p></div>
            <?php elseif ($user['kyc_status'] === 'pending'): ?>
                <svg class="w-8 h-8 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div><p class="font-semibold text-amber-400">Under Review</p><p class="text-sm text-amber-400">Your documents are being reviewed</p></div>
            <?php else: ?>
                <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
                <div><p class="font-semibold text-gray-200">Not Verified</p><p class="text-sm text-gray-400">Upload documents below to get verified</p></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Upload Documents -->
    <?php foreach ($docTypes as $type => $label): 
        $existing = array_filter($documents, fn($d) => $d['document_type'] === $type);
        $latest = !empty($existing) ? reset($existing) : null;
    ?>
    <div class="glass-card rounded-2xl border border-white/[0.06] p-6 mb-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h3 class="font-semibold text-white"><?= $label ?></h3>
                <?php if ($latest): ?>
                    <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($latest['original_name']) ?> · <?= statusBadge($latest['status']) ?></p>
                    <?php if ($latest['rejection_reason']): ?>
                        <p class="text-sm text-red-400 mt-1"><?= htmlspecialchars($latest['rejection_reason']) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-sm text-gray-400 mt-1">Not uploaded</p>
                <?php endif; ?>
            </div>
            <?php if (!$latest || $latest['status'] === 'rejected'): ?>
            <form method="POST" enctype="multipart/form-data" class="shrink-0">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="document_type" value="<?= $type ?>">
                <label class="inline-flex items-center gap-2 px-4 py-2 bg-accent/10 hover:bg-accent/20 text-accent text-sm font-medium rounded-xl cursor-pointer transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Upload
                    <input type="file" name="document" class="hidden" accept=".jpg,.jpeg,.png,.pdf" onchange="this.form.submit()">
                </label>
            </form>
            <?php elseif ($latest['status'] === 'approved'): ?>
                <svg class="w-6 h-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
