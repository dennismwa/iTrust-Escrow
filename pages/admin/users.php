<?php
$pageTitle = 'Manage Users';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireRole(['admin', 'superadmin']);

$db = Database::getInstance();
$page = max(1, intval($_GET['page'] ?? 1));
$search = get('search');
$status = get('status');
$role = get('role');
$perPage = 20;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCSRF();
    $userId = intval($_POST['user_id'] ?? 0);
    $action = post('action');
    
    if ($action === 'update_status' && $userId) {
        $newStatus = post('new_status');
        $db->update('users', ['status' => $newStatus], 'id = ?', [$userId]);
        setFlash('success', 'User status updated');
    } elseif ($action === 'update_role' && $userId) {
        $newRole = post('new_role');
        $db->update('users', ['role' => $newRole], 'id = ?', [$userId]);
        setFlash('success', 'User role updated');
    }
    redirect(APP_URL . '/pages/admin/users.php?' . http_build_query($_GET));
}

$where = ["role != 'superadmin'"];
$params = [];
if ($search) { $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)"; $s = "%{$search}%"; $params = array_merge($params, [$s,$s,$s]); }
if ($status) { $where[] = "status = ?"; $params[] = $status; }
if ($role) { $where[] = "role = ?"; $params[] = $role; }

$whereStr = implode(' AND ', $where);
$total = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE {$whereStr}", $params);
$offset = ($page - 1) * $perPage;
$users = $db->fetchAll("SELECT u.*, w.balance as wallet_balance FROM users u LEFT JOIN wallets w ON w.user_id = u.id WHERE {$whereStr} ORDER BY u.created_at DESC LIMIT {$perPage} OFFSET {$offset}", $params);
$totalPages = ceil($total / $perPage);

require_once APP_ROOT . '/templates/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div><h1 class="text-2xl font-bold text-white">Users</h1><p class="text-sm text-gray-500 mt-1"><?= number_format($total) ?> total users</p></div>
</div>

<div class="glass-card rounded-2xl border border-white/[0.06] p-4 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search users..." class="flex-1 px-4 py-2.5 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent/50">
        <select name="status" class="px-4 py-2.5 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600">
            <option value="">All Statuses</option>
            <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
            <option value="pending" <?= $status==='pending'?'selected':'' ?>>Pending</option>
            <option value="suspended" <?= $status==='suspended'?'selected':'' ?>>Suspended</option>
            <option value="banned" <?= $status==='banned'?'selected':'' ?>>Banned</option>
        </select>
        <select name="role" class="px-4 py-2.5 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600">
            <option value="">All Roles</option>
            <option value="user" <?= $role==='user'?'selected':'' ?>>User</option>
            <option value="agent" <?= $role==='agent'?'selected':'' ?>>Agent</option>
            <option value="admin" <?= $role==='admin'?'selected':'' ?>>Admin</option>
        </select>
        <button type="submit" class="px-6 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-xl hover:bg-gray-800">Filter</button>
    </form>
</div>

<div class="glass-card rounded-2xl border border-white/[0.06] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead><tr class="border-b border-white/[0.06] bg-white/[0.02]">
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">KYC</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Trust</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Balance</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Joined</th>
                <th class="text-right px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-white/[0.04]">
                <?php foreach ($users as $u): ?>
                <tr class="hover:bg-white/[0.02]">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-accent/20 flex items-center justify-center text-accent text-sm font-bold"><?= strtoupper(substr($u['first_name'],0,1)) ?></div>
                            <div>
                                <p class="text-sm font-medium text-white"><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($u['email']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4"><span class="text-sm capitalize font-medium text-gray-300"><?= $u['role'] ?></span></td>
                    <td class="px-6 py-4"><?= statusBadge($u['kyc_status']) ?></td>
                    <td class="px-6 py-4"><span class="text-sm font-medium"><?= number_format($u['trust_score'],0) ?>%</span></td>
                    <td class="px-6 py-4"><span class="text-sm font-semibold"><?= formatMoney($u['wallet_balance'] ?? 0) ?></span></td>
                    <td class="px-6 py-4"><?= statusBadge($u['status']) ?></td>
                    <td class="px-6 py-4"><span class="text-sm text-gray-500"><?= date('M j, Y', strtotime($u['created_at'])) ?></span></td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <form method="POST" class="inline">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="action" value="update_status">
                                <select name="new_status" onchange="this.form.submit()" class="text-xs px-2 py-1 border border-white/10 rounded-lg glass-card">
                                    <option value="active" <?= $u['status']==='active'?'selected':'' ?>>Active</option>
                                    <option value="suspended" <?= $u['status']==='suspended'?'selected':'' ?>>Suspend</option>
                                    <option value="banned" <?= $u['status']==='banned'?'selected':'' ?>>Ban</option>
                                </select>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= renderPagination($page, $totalPages, "?search={$search}&status={$status}&role={$role}") ?>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
