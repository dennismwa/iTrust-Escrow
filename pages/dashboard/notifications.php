<?php
$pageTitle="Notifications";require_once __DIR__."/../../includes/init.php";Auth::requireAuth();
$user=Auth::user();$db=Database::getInstance();
$db->update("notifications",["is_read"=>1],"user_id=? AND is_read=0",[$user["id"]]);
$notifs=$db->fetchAll("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50",[$user["id"]]);
require_once APP_ROOT."/templates/header.php";?>
<div class="max-w-3xl mx-auto"><div class="glass-card rounded-2xl divide-y divide-white/[0.04]">
<?php if(empty($notifs)):?><p class="px-6 py-12 text-sm text-gray-400 text-center">No notifications</p>
<?php else:foreach($notifs as $n):?>
<a href="<?= $n["link"]?APP_URL.$n["link"]:"#" ?>" class="flex items-start gap-3 px-6 py-4 hover:bg-white/[0.02]">
<div class="w-8 h-8 rounded-full bg-accent/10 flex items-center justify-center shrink-0"><svg class="w-4 h-4 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg></div>
<div><p class="text-sm font-medium text-white"><?= htmlspecialchars($n["title"]) ?></p><p class="text-xs text-gray-500"><?= htmlspecialchars($n["message"]) ?></p><p class="text-xs text-gray-400 mt-1"><?= timeAgo($n["created_at"]) ?></p></div>
</a>
<?php endforeach;endif;?></div></div>
<?php require_once APP_ROOT."/templates/footer.php";?>
