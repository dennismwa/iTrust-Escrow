<?php
$pageTitle = 'Profile';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireAuth();

$user = Auth::user();
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCSRF();
    $action = post('action');
    
    if ($action === 'update_profile') {
        $db->update('users', [
            'first_name' => post('first_name'),
            'last_name' => post('last_name'),
            'phone' => post('phone'),
            'business_name' => post('business_name'),
            'city' => post('city'),
            'country' => post('country'),
            'address' => post('address'),
            'preferred_currency' => post('preferred_currency', 'KES')
        ], 'id = ?', [$user['id']]);
        setFlash('success', 'Profile updated');
    } elseif ($action === 'upload_avatar') {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','gif']) && $file['size'] <= MAX_FILE_SIZE) {
                $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
                $uploadDir = APP_ROOT . '/uploads/site/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $uploadPath = $uploadDir . $filename;
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // Delete old avatar file if exists
                    if (!empty($user['avatar'])) {
                        $oldPath = APP_ROOT . '/' . ltrim($user['avatar'], '/');
                        if (file_exists($oldPath)) @unlink($oldPath);
                    }
                    $avatarPath = '/uploads/site/' . $filename;
                    $db->update('users', ['avatar' => $avatarPath], 'id = ?', [$user['id']]);
                    $_SESSION['user_avatar'] = $avatarPath;
                    setFlash('success', 'Profile photo updated');
                } else {
                    setFlash('error', 'Failed to upload photo');
                }
            } else {
                setFlash('error', 'Invalid file. Use JPG, PNG, or WebP under 10MB');
            }
        }
    } elseif ($action === 'remove_avatar') {
        if (!empty($user['avatar'])) {
            $oldPath = APP_ROOT . '/' . ltrim($user['avatar'], '/');
            if (file_exists($oldPath)) @unlink($oldPath);
        }
        $db->update('users', ['avatar' => null], 'id = ?', [$user['id']]);
        $_SESSION['user_avatar'] = null;
        setFlash('success', 'Profile photo removed');
    } elseif ($action === 'change_password') {
        $auth = new Auth();
        $result = $auth->updatePassword($user['id'], $_POST['current_password'], $_POST['new_password']);
        if ($result['success']) { setFlash('success', 'Password updated'); }
        else { setFlash('error', implode(', ', $result['errors'])); }
    }
    redirect(APP_URL . '/pages/profile/index.php');
}

// Refresh user data after potential avatar upload
$user = $db->fetch("SELECT u.*, w.balance as wallet_balance, w.escrow_balance FROM users u LEFT JOIN wallets w ON w.user_id=u.id WHERE u.id=?", [$user['id']]);
$avatarUrl = !empty($user['avatar']) ? (strpos($user['avatar'], 'http') === 0 ? $user['avatar'] : APP_URL . '/' . ltrim($user['avatar'], '/')) : '';

require_once APP_ROOT . '/templates/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Profile</h1>
        <p class="text-sm text-gray-500 mt-1">Manage your account information</p>
    </div>

    <!-- Profile Header with Avatar Upload -->
    <div class="glass-card rounded-2xl border border-white/[0.06] p-6 mb-6">
        <div class="flex flex-col sm:flex-row items-center gap-6">
            <!-- Avatar with upload -->
            <div class="relative group">
                <?php if ($avatarUrl): ?>
                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Profile" class="w-24 h-24 rounded-2xl object-cover border-2 border-accent/20">
                <?php else: ?>
                    <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-accent/30 to-accent/10 flex items-center justify-center text-accent text-3xl font-bold border-2 border-accent/20">
                        <?= strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1)) ?>
                    </div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data" id="avatarForm" class="absolute inset-0">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="action" value="upload_avatar">
                    <label class="absolute inset-0 flex items-center justify-center bg-black/50 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <input type="file" name="avatar" class="hidden" accept="image/jpeg,image/png,image/webp,image/gif" onchange="document.getElementById('avatarForm').submit()">
                    </label>
                </form>
            </div>
            <div class="text-center sm:text-left flex-1">
                <h2 class="text-xl font-bold text-white"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></h2>
                <p class="text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
                <div class="flex flex-wrap items-center gap-3 mt-3 justify-center sm:justify-start">
                    <?= statusBadge($user['kyc_status'] === 'approved' ? 'approved' : ($user['kyc_status'] ?? 'none')) ?>
                    <span class="text-xs font-medium text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full">★ Trust: <?= number_format($user['trust_score'],0) ?>%</span>
                </div>
                <div class="flex items-center gap-2 mt-3 justify-center sm:justify-start">
                    <label class="inline-flex items-center gap-1.5 text-xs font-medium text-accent hover:text-accent-dark cursor-pointer transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Change Photo
                        <form method="POST" enctype="multipart/form-data" id="avatarForm2" class="hidden">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="action" value="upload_avatar">
                        </form>
                        <input type="file" name="avatar" form="avatarForm2" class="hidden" accept="image/jpeg,image/png,image/webp,image/gif" onchange="document.getElementById('avatarForm2').submit()">
                    </label>
                    <?php if ($avatarUrl): ?>
                    <form method="POST" class="inline">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="action" value="remove_avatar">
                        <button type="submit" class="text-xs font-medium text-red-400 hover:text-red-300 transition-colors">Remove</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Form -->
    <form method="POST" class="glass-card rounded-2xl border border-white/[0.06] p-6 mb-6 space-y-4">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="action" value="update_profile">
        <h3 class="font-semibold text-white mb-2">Personal Information</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50"></div>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50"></div>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Phone</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50"></div>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Currency</label>
                <select name="preferred_currency" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600">
                    <?php foreach(['KES','USD','NGN','GHS','ZAR'] as $c): ?>
                    <option value="<?= $c ?>" <?= $user['preferred_currency']===$c?'selected':'' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Business Name</label>
                <input type="text" name="business_name" value="<?= htmlspecialchars($user['business_name'] ?? '') ?>" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50"></div>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">City</label>
                <input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50"></div>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Country</label>
                <input type="text" name="country" value="<?= htmlspecialchars($user['country'] ?? '') ?>" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50"></div>
        </div>
        <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Address</label>
            <textarea name="address" rows="2" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 resize-none"><?= htmlspecialchars($user['address'] ?? '') ?></textarea></div>
        <button type="submit" class="px-6 py-3 bg-accent hover:bg-accent-dark text-surface text-sm font-semibold rounded-xl transition-all">Save Changes</button>
    </form>

    <!-- Password Change -->
    <form method="POST" class="glass-card rounded-2xl border border-white/[0.06] p-6 space-y-4">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="action" value="change_password">
        <h3 class="font-semibold text-white mb-2">Change Password</h3>
        <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Current Password</label>
            <input type="password" name="current_password" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50"></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">New Password</label>
                <input type="password" name="new_password" required minlength="8" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50"></div>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Confirm</label>
                <input type="password" name="confirm_password" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50"></div>
        </div>
        <button type="submit" class="px-6 py-3 bg-surface-200 hover:bg-surface-300 text-white text-sm font-semibold rounded-xl transition-all border border-white/10">Update Password</button>
    </form>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
