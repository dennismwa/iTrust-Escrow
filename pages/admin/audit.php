<?php
$pageTitle = 'Audit Logs';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireRole(['admin', 'superadmin']);
$db = Database::getInstance();
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;
$total = $db->fetchColumn("SELECT COUNT(*) FROM activity_logs");
$logs = $db->fetchAll("SELECT al.*, CONCAT(u.first_name,' ',u.last_name) as user_name FROM activity_logs al LEFT JOIN users u ON u.id=al.user_id ORDER BY al.created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$totalPages = ceil($total / $perPage);
require_once APP_ROOT . '/templates/header.php';
?>
<div class="mb-6"><h1 class="text-2xl font-bold text-white">Audit Logs</h1><p class="text-sm text-gray-500 mt-1"><?= number_format($total) ?> events</p></div>
<div class="glass-card rounded-2xl border border-white/[0.06] overflow-hidden">
    <div class="overflow-x-auto"><table class="w-full"><thead><tr class="border-b border-white/[0.06] bg-white/[0.02]">
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Timestamp</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">User</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Action</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Description</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">IP</th>
    </tr></thead><tbody class="divide-y divide-white/[0.04]">
    <?php foreach ($logs as $log): ?>
    <tr class="hover:bg-white/[0.02]">
        <td class="px-6 py-3"><span class="text-xs font-mono text-gray-500"><?= date('M j, g:i A', strtotime($log['created_at'])) ?></span></td>
        <td class="px-6 py-3"><span class="text-sm"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></span></td>
        <td class="px-6 py-3"><span class="text-xs font-mono bg-surface-200 px-2 py-0.5 rounded"><?= htmlspecialchars($log['action']) ?></span></td>
        <td class="px-6 py-3"><span class="text-sm text-gray-400 truncate max-w-[300px] block"><?= htmlspecialchars($log['description'] ?? '') ?></span></td>
        <td class="px-6 py-3"><span class="text-xs font-mono text-gray-400"><?= htmlspecialchars($log['ip_address'] ?? '') ?></span></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?= renderPagination($page, $totalPages, "?x=1") ?>
</div>
<?php require_once APP_ROOT . '/templates/footer.php'; ?>
