<?php
$pageTitle = 'System Settings';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireRole(['admin', 'superadmin']);

$db = Database::getInstance();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCSRF();
    
    $category = post('category', 'general');
    
    // Handle logo upload
    if (isset($_FILES['logo_url']) && $_FILES['logo_url']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['logo_url'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','svg','webp'])) {
            $filename = 'logo_' . time() . '.' . $ext;
            $uploadPath = APP_ROOT . '/uploads/logos/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                Settings::set('logo_url', APP_URL . '/uploads/logos/' . $filename);
            }
        }
    }
    
    // Update all submitted settings
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $settingKey = substr($key, 8);
            Settings::set($settingKey, $value);
        }
    }
    
    // Handle boolean checkboxes
    $booleanSettings = ['maintenance_mode', 'registration_enabled', 'kyc_required'];
    foreach ($booleanSettings as $bs) {
        if (!isset($_POST['setting_' . $bs])) {
            Settings::set($bs, '0');
        }
    }
    
    Settings::clearCache();
    setFlash('success', 'Settings updated successfully');
    redirect(APP_URL . '/pages/admin/settings.php?tab=' . $category);
}

$activeTab = get('tab', 'general');
$categories = ['general' => 'General', 'branding' => 'Branding', 'fees' => 'Fees', 'escrow' => 'Escrow', 'system' => 'System', 'homepage' => 'Homepage', 'legal' => 'Legal'];

require_once APP_ROOT . '/templates/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">System Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Configure your platform settings</p>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 glass-card rounded-xl border border-white/[0.06] p-1 mb-6 overflow-x-auto">
        <?php foreach ($categories as $key => $label): ?>
            <a href="?tab=<?= $key ?>" class="px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-colors <?= $activeTab === $key ? 'bg-accent text-surface' : 'text-gray-400 hover:bg-surface-100' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="category" value="<?= $activeTab ?>">

        <div class="glass-card rounded-2xl border border-white/[0.06] p-6 space-y-5">
            <?php
            $settings = Settings::getByCategory($activeTab);
            foreach ($settings as $setting):
            ?>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5"><?= htmlspecialchars($setting['label']) ?></label>
                <?php if ($setting['description']): ?>
                    <p class="text-xs text-gray-400 mb-2"><?= htmlspecialchars($setting['description']) ?></p>
                <?php endif; ?>

                <?php if ($setting['setting_type'] === 'textarea'): ?>
                    <textarea name="setting_<?= $setting['setting_key'] ?>" rows="4"
                              class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50 resize-none"><?= htmlspecialchars($setting['setting_value']) ?></textarea>

                <?php elseif ($setting['setting_type'] === 'boolean'): ?>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="setting_<?= $setting['setting_key'] ?>" value="1" <?= $setting['setting_value'] == '1' ? 'checked' : '' ?>
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-surface-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-accent/20 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:glass-card after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-accent"></div>
                    </label>

                <?php elseif ($setting['setting_type'] === 'color'): ?>
                    <div class="flex items-center gap-3">
                        <input type="color" name="setting_<?= $setting['setting_key'] ?>" value="<?= htmlspecialchars($setting['setting_value']) ?>"
                               class="h-10 w-16 rounded-lg border border-white/10 cursor-pointer">
                        <input type="text" value="<?= htmlspecialchars($setting['setting_value']) ?>"
                               class="px-4 py-2.5 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 w-32 font-mono"
                               onchange="this.previousElementSibling.value=this.value" readonly>
                    </div>

                <?php elseif ($setting['setting_type'] === 'image'): ?>
                    <?php if ($setting['setting_value']): ?>
                        <div class="mb-2">
                            <img src="<?= htmlspecialchars($setting['setting_value']) ?>" alt="Current" class="h-12 rounded-lg border">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="<?= $setting['setting_key'] ?>" accept="image/*"
                           class="w-full px-4 py-2.5 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-accent/10 file:text-accent hover:file:bg-accent/20">

                <?php elseif ($setting['setting_type'] === 'number'): ?>
                    <input type="number" name="setting_<?= $setting['setting_key'] ?>" value="<?= htmlspecialchars($setting['setting_value']) ?>" step="any"
                           class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50">

                <?php else: ?>
                    <input type="text" name="setting_<?= $setting['setting_key'] ?>" value="<?= htmlspecialchars($setting['setting_value']) ?>"
                           class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50">
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-end mt-6">
            <button type="submit" class="px-8 py-3 bg-accent hover:bg-accent-dark text-surface text-sm font-semibold rounded-xl transition-all shadow-sm">
                Save Settings
            </button>
        </div>
    </form>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
