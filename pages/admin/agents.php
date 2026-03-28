<?php
$pageTitle = 'Manage Agents';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireRole(['admin', 'superadmin']);
$db = Database::getInstance();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCSRF();
    $userId = intval($_POST['user_id'] ?? 0);
    $action = post('action');
    if ($action === 'promote') { $db->update('users', ['role' => 'agent'], 'id = ?', [$userId]); setFlash('success', 'User promoted to agent'); }
    elseif ($action === 'demote') { $db->update('users', ['role' => 'user'], 'id = ?', [$userId]); setFlash('success', 'Agent demoted to user'); }
    redirect(APP_URL . '/pages/admin/agents.php');
}
$agents = $db->fetchAll("SELECT * FROM users WHERE role = 'agent' ORDER BY created_at DESC");
$users = $db->fetchAll("SELECT id, first_name, last_name, email FROM users WHERE role = 'user' AND status = 'active' ORDER BY first_name LIMIT 50");
require_once APP_ROOT . '/templates/header.php';
?>
<div class="flex items-center justify-between mb-6"><div><h1 class="text-2xl font-bold text-white">Agents</h1><p class="text-sm text-gray-500 mt-1"><?= count($agents) ?> active agents</p></div></div>
<div class="glass-card rounded-2xl border border-white/[0.06] p-6 mb-6">
    <h3 class="font-semibold text-white mb-3">Promote User to Agent</h3>
    <form method="POST" class="flex gap-3"><input type="hidden" name="action" value="promote"><?= Auth::csrfField() ?>
        <select name="user_id" required class="flex-1 px-4 py-2.5 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600">
            <option value="">Select user...</option>
            <?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['first_name'].' '.$u['last_name'].' ('.$u['email'].')') ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="px-6 py-2.5 bg-accent text-surface text-sm font-medium rounded-xl hover:bg-accent-dark">Promote</button>
    </form>
</div>
<div class="glass-card rounded-2xl border border-white/[0.06] overflow-hidden">
    <div class="overflow-x-auto"><table class="w-full"><thead><tr class="border-b border-white/[0.06] bg-white/[0.02]">
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Agent</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Email</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Joined</th>
        <th class="text-right px-6 py-3"></th>
    </tr></thead><tbody class="divide-y divide-white/[0.04]">
    <?php foreach ($agents as $a): ?>
    <tr class="hover:bg-white/[0.02]">
        <td class="px-6 py-4"><span class="text-sm font-medium"><?= htmlspecialchars($a['first_name'].' '.$a['last_name']) ?></span></td>
        <td class="px-6 py-4"><span class="text-sm text-gray-500"><?= htmlspecialchars($a['email']) ?></span></td>
        <td class="px-6 py-4"><?= statusBadge($a['status']) ?></td>
        <td class="px-6 py-4"><span class="text-sm text-gray-500"><?= date('M j, Y', strtotime($a['created_at'])) ?></span></td>
        <td class="px-6 py-4 text-right">
            <form method="POST" class="inline"><input type="hidden" name="user_id" value="<?= $a['id'] ?>"><input type="hidden" name="action" value="demote"><?= Auth::csrfField() ?>
                <button onclick="return confirm('Demote this agent?')" class="text-xs px-3 py-1 bg-red-500/10 text-red-400 rounded-lg hover:bg-red-100 font-medium">Demote</button></form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php require_once APP_ROOT . '/templates/footer.php'; ?>
