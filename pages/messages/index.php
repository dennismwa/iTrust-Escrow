<?php
$pageTitle = 'Messages';
require_once __DIR__ . '/../../includes/init.php';
Auth::requireAuth();
$user = Auth::user();
$db = Database::getInstance();
$conversations = $db->fetchAll("SELECT c.*, e.escrow_id as esc_ref, e.title as escrow_title, (SELECT content FROM messages WHERE conversation_id=c.id ORDER BY created_at DESC LIMIT 1) as last_msg, (SELECT created_at FROM messages WHERE conversation_id=c.id ORDER BY created_at DESC LIMIT 1) as last_msg_time FROM conversations c JOIN conversation_participants cp ON cp.conversation_id=c.id LEFT JOIN escrows e ON e.id=c.escrow_id WHERE cp.user_id=? ORDER BY last_msg_time DESC", [$user['id']]);
require_once APP_ROOT . '/templates/header.php';
?>
<div class="max-w-3xl mx-auto">
    <div class="mb-6"><h1 class="text-2xl font-bold text-white">Messages</h1></div>
    <div class="glass-card rounded-2xl border border-white/[0.06] overflow-hidden divide-y divide-white/[0.04]">
        <?php if (empty($conversations)): ?><p class="px-6 py-12 text-sm text-gray-400 text-center">No conversations yet</p>
        <?php else: foreach ($conversations as $c): ?>
        <a href="<?= APP_URL ?>/pages/escrow/view.php?id=<?= $c['escrow_id'] ?>#messages" class="flex items-center gap-3 px-6 py-4 hover:bg-surface-100 transition-colors">
            <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center"><svg class="w-5 h-5 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg></div>
            <div class="flex-1 min-w-0"><p class="text-sm font-medium text-white"><?= htmlspecialchars($c['esc_ref'] ?? 'Conversation') ?></p><p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($c['last_msg'] ?? 'No messages') ?></p></div>
            <span class="text-xs text-gray-400"><?= $c['last_msg_time'] ? timeAgo($c['last_msg_time']) : '' ?></span>
        </a>
        <?php endforeach; endif; ?>
    </div>
</div>
<?php require_once APP_ROOT . '/templates/footer.php'; ?>
