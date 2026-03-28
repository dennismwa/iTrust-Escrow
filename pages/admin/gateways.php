<?php
$pageTitle = 'Payment Gateways';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireRole(['admin', 'superadmin']);

$db = Database::getInstance();

// Handle gateway update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCSRF();
    $gatewayId = intval($_POST['gateway_id'] ?? 0);
    $action = post('action');
    
    if ($action === 'toggle') {
        $gateway = $db->fetch("SELECT * FROM payment_gateways WHERE id = ?", [$gatewayId]);
        if ($gateway) {
            $db->update('payment_gateways', ['is_active' => $gateway['is_active'] ? 0 : 1], 'id = ?', [$gatewayId]);
            setFlash('success', $gateway['display_name'] . ($gateway['is_active'] ? ' disabled' : ' enabled'));
        }
    } elseif ($action === 'update_config') {
        $config = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'config_') === 0) {
                $configKey = substr($key, 7);
                $config[$configKey] = $value;
            }
        }
        $environment = post('environment', 'sandbox');
        $feeValue = floatval($_POST['fee_value'] ?? 0);
        $feeFixed = floatval($_POST['fee_fixed'] ?? 0);
        
        $db->update('payment_gateways', [
            'config' => json_encode($config),
            'environment' => $environment,
            'fee_value' => $feeValue,
            'fee_fixed' => $feeFixed
        ], 'id = ?', [$gatewayId]);
        
        setFlash('success', 'Gateway configuration updated');
    }
    redirect(APP_URL . '/pages/admin/gateways.php');
}

$gateways = $db->fetchAll("SELECT * FROM payment_gateways ORDER BY sort_order ASC");

require_once APP_ROOT . '/templates/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Payment Gateways</h1>
        <p class="text-sm text-gray-500 mt-1">Configure and manage payment methods</p>
    </div>

    <div class="space-y-4">
        <?php foreach ($gateways as $gw): 
            $config = json_decode($gw['config'], true) ?: [];
        ?>
        <div class="glass-card rounded-2xl border border-white/[0.06] overflow-hidden">
            <!-- Gateway Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/[0.06]">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-surface-100 flex items-center justify-center">
                        <?php if ($gw['name'] === 'mpesa'): ?>
                            <span class="text-lg font-bold text-green-600">M</span>
                        <?php elseif ($gw['name'] === 'stripe'): ?>
                            <span class="text-lg font-bold text-indigo-400">S</span>
                        <?php elseif ($gw['name'] === 'paypal'): ?>
                            <span class="text-lg font-bold text-blue-400">P</span>
                        <?php elseif ($gw['name'] === 'crypto'): ?>
                            <span class="text-lg font-bold text-amber-400">₿</span>
                        <?php else: ?>
                            <span class="text-lg font-bold text-gray-400">B</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white"><?= htmlspecialchars($gw['display_name']) ?></h3>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($gw['description']) ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-medium px-2 py-1 rounded-full <?= $gw['environment'] === 'live' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400' ?>">
                        <?= ucfirst($gw['environment']) ?>
                    </span>
                    <form method="POST" class="inline">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="gateway_id" value="<?= $gw['id'] ?>">
                        <input type="hidden" name="action" value="toggle">
                        <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors <?= $gw['is_active'] ? 'bg-accent' : 'bg-surface-300' ?>">
                            <span class="inline-block h-4 w-4 transform rounded-full glass-card transition-transform <?= $gw['is_active'] ? 'translate-x-6' : 'translate-x-1' ?>"></span>
                        </button>
                    </form>
                    <button onclick="toggleConfig('config-<?= $gw['id'] ?>')" class="p-2 hover:bg-white/[0.05] rounded-lg transition-colors">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>
                </div>
            </div>

            <!-- Configuration Panel (Hidden by default) -->
            <div id="config-<?= $gw['id'] ?>" class="hidden border-t border-white/[0.04] bg-white/[0.02]">
                <form method="POST" class="p-6 space-y-4">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="gateway_id" value="<?= $gw['id'] ?>">
                    <input type="hidden" name="action" value="update_config">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1.5">Environment</label>
                            <select name="environment" class="w-full px-4 py-2.5 glass-card border border-white/10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-accent/20">
                                <option value="sandbox" <?= $gw['environment']==='sandbox'?'selected':'' ?>>Sandbox</option>
                                <option value="live" <?= $gw['environment']==='live'?'selected':'' ?>>Live</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1.5">Fee (%)</label>
                            <input type="number" name="fee_value" value="<?= $gw['fee_value'] ?>" step="0.01"
                                   class="w-full px-4 py-2.5 glass-card border border-white/10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-accent/20">
                        </div>
                    </div>

                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 pt-2">API Configuration</p>

                    <?php foreach ($config as $key => $value): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5"><?= ucwords(str_replace('_',' ',$key)) ?></label>
                        <?php if (is_bool($value)): ?>
                            <select name="config_<?= $key ?>" class="w-full px-4 py-2.5 glass-card border border-white/10 rounded-xl text-sm">
                                <option value="1" <?= $value ? 'selected' : '' ?>>Enabled</option>
                                <option value="0" <?= !$value ? 'selected' : '' ?>>Disabled</option>
                            </select>
                        <?php else: ?>
                            <input type="text" name="config_<?= $key ?>" value="<?= htmlspecialchars(is_string($value) ? $value : '') ?>"
                                   class="w-full px-4 py-2.5 glass-card border border-white/10 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-accent/20"
                                   placeholder="Enter <?= str_replace('_',' ',$key) ?>">
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-6 py-2.5 bg-accent hover:bg-accent-dark text-surface text-sm font-medium rounded-xl transition-colors">
                            Save Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleConfig(id) {
    const el = document.getElementById(id);
    el.classList.toggle('hidden');
}
</script>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
