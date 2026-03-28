<?php
$pageTitle = 'Settings';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireAuth();
$user = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCSRF();
    $action = post('action');
    $db = Database::getInstance();
    
    if ($action === 'update_notifications') {
        $db->update('users', [
            'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
            'sms_notifications' => isset($_POST['sms_notifications']) ? 1 : 0,
        ], 'id = ?', [$user['id']]);
        setFlash('success', 'Notification preferences updated');
    } elseif ($action === 'update_security') {
        $db->update('users', [
            'two_factor_enabled' => isset($_POST['two_factor_enabled']) ? 1 : 0,
        ], 'id = ?', [$user['id']]);
        setFlash('success', 'Security settings updated');
    }
    redirect(APP_URL . '/pages/settings/index.php');
}

require_once APP_ROOT . '/templates/header.php';
?>
<div class="max-w-2xl mx-auto">
    <div class="mb-6"><h1 class="text-2xl font-bold text-white">Settings</h1><p class="text-sm text-gray-500 mt-1">Manage your account preferences</p></div>
    
    <div class="space-y-4">
        <!-- Quick Links -->
        <a href="<?= APP_URL ?>/pages/profile/index.php" class="group flex items-center justify-between glass-card rounded-2xl border border-white/[0.06] p-5 hover:border-accent/20 hover:shadow-lg hover:shadow-accent/5 transition-all">
            <div class="flex items-center gap-3"><div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center group-hover:bg-accent/20 transition-colors"><svg class="w-5 h-5 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div><div><p class="text-sm font-semibold text-white">Profile Settings</p><p class="text-xs text-gray-500">Update your personal information & photo</p></div></div>
            <svg class="w-5 h-5 text-gray-600 group-hover:text-accent transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <a href="<?= APP_URL ?>/pages/kyc/index.php" class="group flex items-center justify-between glass-card rounded-2xl border border-white/[0.06] p-5 hover:border-purple-500/20 hover:shadow-lg hover:shadow-purple-500/5 transition-all">
            <div class="flex items-center gap-3"><div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center group-hover:bg-purple-500/20 transition-colors"><svg class="w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div><div><p class="text-sm font-semibold text-white">KYC Verification</p><p class="text-xs text-gray-500">Verify your identity — <?= statusBadge($user['kyc_status'] ?? 'none') ?></p></div></div>
            <svg class="w-5 h-5 text-gray-600 group-hover:text-purple-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <a href="<?= APP_URL ?>/pages/wallet/index.php" class="group flex items-center justify-between glass-card rounded-2xl border border-white/[0.06] p-5 hover:border-emerald-500/20 hover:shadow-lg hover:shadow-emerald-500/5 transition-all">
            <div class="flex items-center gap-3"><div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center group-hover:bg-emerald-500/20 transition-colors"><svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg></div><div><p class="text-sm font-semibold text-white">Wallet & Payments</p><p class="text-xs text-gray-500">Manage funds, deposits & withdrawals</p></div></div>
            <svg class="w-5 h-5 text-gray-600 group-hover:text-emerald-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>

        <!-- Notification Preferences -->
        <form method="POST" class="glass-card rounded-2xl border border-white/[0.06] p-6">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="action" value="update_notifications">
            <h3 class="font-semibold text-white mb-4">Notification Preferences</h3>
            <div class="space-y-4">
                <label class="flex items-center justify-between cursor-pointer">
                    <div>
                        <p class="text-sm font-medium text-white">Email Notifications</p>
                        <p class="text-xs text-gray-500">Receive email alerts for escrow updates</p>
                    </div>
                    <div class="relative">
                        <input type="checkbox" name="email_notifications" class="sr-only peer" <?= ($user['email_notifications'] ?? 1) ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-surface-300 peer-focus:ring-2 peer-focus:ring-accent/20 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-accent"></div>
                    </div>
                </label>
                <label class="flex items-center justify-between cursor-pointer">
                    <div>
                        <p class="text-sm font-medium text-white">SMS Notifications</p>
                        <p class="text-xs text-gray-500">Receive SMS for critical actions</p>
                    </div>
                    <div class="relative">
                        <input type="checkbox" name="sms_notifications" class="sr-only peer" <?= ($user['sms_notifications'] ?? 0) ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-surface-300 peer-focus:ring-2 peer-focus:ring-accent/20 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-accent"></div>
                    </div>
                </label>
            </div>
            <button type="submit" class="mt-4 px-5 py-2.5 bg-accent hover:bg-accent-dark text-surface text-sm font-semibold rounded-xl transition-all">Save Preferences</button>
        </form>

        <!-- Security -->
        <div class="glass-card rounded-2xl border border-white/[0.06] p-6">
            <h3 class="font-semibold text-white mb-4">Security</h3>
            <div class="space-y-4">
                <a href="<?= APP_URL ?>/pages/profile/index.php#password" class="flex items-center justify-between p-3 rounded-xl hover:bg-white/[0.03] transition-colors">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <div>
                            <p class="text-sm font-medium text-white">Change Password</p>
                            <p class="text-xs text-gray-500">Update your account password</p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <div class="flex items-center justify-between p-3 rounded-xl">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <div>
                            <p class="text-sm font-medium text-white">Active Sessions</p>
                            <p class="text-xs text-gray-500">Last login: <?= $user['last_login_at'] ? date('M j, Y g:i A', strtotime($user['last_login_at'])) : 'N/A' ?></p>
                        </div>
                    </div>
                    <span class="text-xs text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full">Current</span>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="glass-card rounded-2xl border border-red-500/10 p-6">
            <h3 class="font-semibold text-red-400 mb-2">Danger Zone</h3>
            <p class="text-xs text-gray-500 mb-4">Irreversible account actions</p>
            <a href="<?= APP_URL ?>/pages/auth/logout.php" class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-sm font-medium rounded-xl border border-red-500/20 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Sign Out
            </a>
        </div>
    </div>
</div>
<?php require_once APP_ROOT . '/templates/footer.php'; ?>
