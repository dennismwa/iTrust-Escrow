<?php
$pageTitle = 'Site Media';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireRole(['admin', 'superadmin']);
$db = Database::getInstance();

// Helper: build public URL from stored path
function mediaUrl($path) {
    if (!$path) return '';
    if (strpos($path, 'http') === 0) return $path;
    return APP_URL . '/' . ltrim($path, '/');
}

// Helper: check file exists on disk
function mediaExists($path) {
    if (!$path) return false;
    $full = APP_ROOT . '/' . ltrim($path, '/');
    return file_exists($full);
}

// Handle uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCSRF();
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_media' && isset($_FILES['file'])) {
        $key = trim($_POST['media_key'] ?? '');
        $file = $_FILES['file'];
        if ($key && $file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg','ico'])) {
                // Delete old file
                $old = $db->fetch("SELECT file_path FROM site_media WHERE media_key = ?", [$key]);
                if ($old && $old['file_path'] && mediaExists($old['file_path'])) {
                    @unlink(APP_ROOT . '/' . ltrim($old['file_path'], '/'));
                }
                $fn = $key . '_' . time() . '.' . $ext;
                $dir = APP_ROOT . '/uploads/site';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $diskPath = $dir . '/' . $fn;
                $dbPath = '/uploads/site/' . $fn;
                if (move_uploaded_file($file['tmp_name'], $diskPath)) {
                    $db->update('site_media', ['file_path' => $dbPath], 'media_key = ?', [$key]);
                    setFlash('success', 'Image uploaded successfully');
                } else {
                    setFlash('error', 'Failed to save file. Check uploads/site/ permissions.');
                }
            } else { setFlash('error', 'Invalid file type. Use JPG, PNG, GIF, WebP, SVG, or ICO.'); }
        } else { setFlash('error', 'Upload error'); }

    } elseif ($action === 'upload_setting' && isset($_FILES['file'])) {
        $key = trim($_POST['setting_key'] ?? '');
        $file = $_FILES['file'];
        if ($key && $file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg','ico'])) {
                // Delete old
                $oldPath = Settings::get($key);
                if ($oldPath && mediaExists($oldPath)) {
                    @unlink(APP_ROOT . '/' . ltrim($oldPath, '/'));
                }
                $fn = $key . '_' . time() . '.' . $ext;
                $dir = APP_ROOT . '/uploads/site';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $diskPath = $dir . '/' . $fn;
                $dbPath = '/uploads/site/' . $fn;
                if (move_uploaded_file($file['tmp_name'], $diskPath)) {
                    // Check if setting exists, if not insert it
                    $exists = $db->fetch("SELECT id FROM settings WHERE setting_key = ?", [$key]);
                    if ($exists) {
                        Settings::set($key, $dbPath);
                    } else {
                        $db->insert('settings', ['setting_key' => $key, 'setting_value' => $dbPath, 'type' => 'text', 'category' => 'branding', 'label' => ucwords(str_replace('_', ' ', $key)), 'is_public' => 1]);
                    }
                    Settings::clearCache();
                    setFlash('success', 'Uploaded successfully');
                } else {
                    setFlash('error', 'Failed to save file');
                }
            } else { setFlash('error', 'Invalid file type'); }
        }

    } elseif ($action === 'delete_media') {
        $key = trim($_POST['media_key'] ?? '');
        if ($key) {
            $media = $db->fetch("SELECT file_path FROM site_media WHERE media_key = ?", [$key]);
            if ($media && $media['file_path'] && mediaExists($media['file_path'])) {
                @unlink(APP_ROOT . '/' . ltrim($media['file_path'], '/'));
            }
            $db->update('site_media', ['file_path' => ''], 'media_key = ?', [$key]);
            setFlash('success', 'Image removed');
        }

    } elseif ($action === 'delete_setting') {
        $key = trim($_POST['setting_key'] ?? '');
        if ($key) {
            $oldPath = Settings::get($key);
            if ($oldPath && mediaExists($oldPath)) {
                @unlink(APP_ROOT . '/' . ltrim($oldPath, '/'));
            }
            Settings::set($key, '');
            Settings::clearCache();
            setFlash('success', 'Image removed');
        }
    }

    redirect(APP_URL . '/pages/admin/media.php');
}

// Get all site media grouped by section
$mediaItems = $db->fetchAll("SELECT * FROM site_media ORDER BY section, sort_order");
$grouped = [];
foreach ($mediaItems as $m) {
    $grouped[$m['section']][] = $m;
}

// Section labels
$sectionLabels = [
    'homepage' => ['Landing Page — Hero & About', 'Images displayed on the homepage hero and about sections'],
    'features' => ['Landing Page — Features', 'Feature section images'],
    'general' => ['General', 'Other site media'],
];

// Branding settings
$brandSettings = [
    ['site_logo', 'Site Logo', 'Main logo. SVG or transparent PNG recommended. Shows in nav & sidebar.'],
    ['site_logo_light', 'Logo (Light variant)', 'Light version for dark backgrounds. Used on login page.'],
    ['favicon', 'Favicon', 'Browser tab icon. Use .ico or 32×32 PNG.'],
    ['pwa_icon_192', 'PWA Icon 192×192', 'Required for PWA install prompt.'],
    ['pwa_icon_512', 'PWA Icon 512×512', 'Splash screen icon for PWA.'],
];

require_once APP_ROOT . '/templates/header.php';
?>

<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-white">Site Media</h1>
        <p class="text-sm text-gray-500 mt-1">Upload and manage all images across your platform</p>
    </div>

    <!-- BRANDING -->
    <div class="glass-card rounded-2xl border border-white/[0.06] p-6 mb-6">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-9 h-9 rounded-lg bg-accent/10 flex items-center justify-center">
                <svg class="w-4.5 h-4.5 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
            </div>
            <div>
                <h2 class="text-base font-bold text-white">Branding & Icons</h2>
                <p class="text-xs text-gray-500">Logo, favicon, and PWA icons</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($brandSettings as $bs):
                $currentPath = Settings::get($bs[0]);
                $hasImg = $currentPath && mediaExists($currentPath);
            ?>
            <div class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-4">
                <p class="text-sm font-semibold text-white mb-0.5"><?= $bs[1] ?></p>
                <p class="text-[11px] text-gray-500 mb-3 leading-relaxed"><?= $bs[2] ?></p>

                <?php if ($hasImg): ?>
                <div class="mb-3 p-3 rounded-lg bg-surface-100 flex items-center justify-center min-h-[64px]">
                    <img src="<?= mediaUrl($currentPath) ?>" alt="<?= $bs[1] ?>" class="max-h-16 w-auto object-contain">
                </div>
                <div class="flex gap-2">
                    <form method="POST" enctype="multipart/form-data" class="flex-1">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="action" value="upload_setting">
                        <input type="hidden" name="setting_key" value="<?= $bs[0] ?>">
                        <label class="flex items-center justify-center gap-1.5 px-3 py-2 border border-white/10 rounded-lg text-xs text-gray-400 hover:bg-white/[0.04] hover:border-accent/30 cursor-pointer transition-all w-full">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Replace
                            <input type="file" name="file" class="hidden" accept="image/*,.ico" onchange="this.form.submit()">
                        </label>
                    </form>
                    <form method="POST">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="action" value="delete_setting">
                        <input type="hidden" name="setting_key" value="<?= $bs[0] ?>">
                        <button type="submit" onclick="return confirm('Remove this image?')" class="px-3 py-2 border border-red-500/20 rounded-lg text-xs text-red-400 hover:bg-red-500/10 transition-all">Remove</button>
                    </form>
                </div>
                <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="action" value="upload_setting">
                    <input type="hidden" name="setting_key" value="<?= $bs[0] ?>">
                    <label class="flex flex-col items-center justify-center gap-2 px-4 py-6 border-2 border-dashed border-white/10 rounded-lg text-gray-500 hover:bg-white/[0.02] hover:border-accent/30 cursor-pointer transition-all">
                        <svg class="w-7 h-7 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="text-xs">Click to upload</span>
                        <input type="file" name="file" class="hidden" accept="image/*,.ico" onchange="this.form.submit()">
                    </label>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- LANDING PAGE IMAGES — grouped by section -->
    <?php foreach ($grouped as $section => $items):
        $label = $sectionLabels[$section] ?? [ucfirst($section), ''];
    ?>
    <div class="glass-card rounded-2xl border border-white/[0.06] p-6 mb-6">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-9 h-9 rounded-lg bg-blue-500/10 flex items-center justify-center">
                <svg class="w-4.5 h-4.5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <h2 class="text-base font-bold text-white"><?= $label[0] ?></h2>
                <?php if ($label[1]): ?><p class="text-xs text-gray-500"><?= $label[1] ?></p><?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($items as $m):
                $hasFile = $m['file_path'] && mediaExists($m['file_path']);
            ?>
            <div class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-4">
                <p class="text-sm font-semibold text-white mb-0.5"><?= htmlspecialchars($m['title']) ?></p>
                <p class="text-[10px] text-gray-600 font-mono mb-3"><?= $m['media_key'] ?></p>

                <?php if ($hasFile): ?>
                <div class="mb-3 rounded-lg overflow-hidden bg-surface-100 border border-white/[0.04]">
                    <img src="<?= mediaUrl($m['file_path']) ?>" alt="<?= htmlspecialchars($m['alt_text'] ?? '') ?>" class="w-full h-36 object-cover">
                </div>
                <div class="flex gap-2">
                    <form method="POST" enctype="multipart/form-data" class="flex-1">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="action" value="upload_media">
                        <input type="hidden" name="media_key" value="<?= $m['media_key'] ?>">
                        <label class="flex items-center justify-center gap-1.5 px-3 py-2 border border-white/10 rounded-lg text-xs text-gray-400 hover:bg-white/[0.04] hover:border-accent/30 cursor-pointer transition-all w-full">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Replace
                            <input type="file" name="file" class="hidden" accept="image/*" onchange="this.form.submit()">
                        </label>
                    </form>
                    <form method="POST">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="action" value="delete_media">
                        <input type="hidden" name="media_key" value="<?= $m['media_key'] ?>">
                        <button type="submit" onclick="return confirm('Remove this image?')" class="px-3 py-2 border border-red-500/20 rounded-lg text-xs text-red-400 hover:bg-red-500/10 transition-all">Remove</button>
                    </form>
                </div>
                <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="action" value="upload_media">
                    <input type="hidden" name="media_key" value="<?= $m['media_key'] ?>">
                    <label class="flex flex-col items-center justify-center gap-2 px-4 py-8 border-2 border-dashed border-white/10 rounded-lg text-gray-500 hover:bg-white/[0.02] hover:border-accent/30 cursor-pointer transition-all">
                        <svg class="w-8 h-8 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="text-xs">Click to upload</span>
                        <input type="file" name="file" class="hidden" accept="image/*" onchange="this.form.submit()">
                    </label>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Info -->
    <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-4 mb-6">
        <p class="text-xs text-amber-400"><strong>Tip:</strong> For best results use PNG or WebP images. Hero mockup images look best at 600–800px tall. Background images for Hero B should be at least 1920×1080px. Make sure the <code class="bg-white/5 px-1 rounded">uploads/site/</code> folder has write permissions (755 or 775).</p>
    </div>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
