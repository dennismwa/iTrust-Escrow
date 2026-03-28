<?php
require_once __DIR__ . '/../config/config.php';
spl_autoload_register(function ($class) {
    $f = APP_ROOT . '/classes/' . $class . '.php';
    if (file_exists($f)) require_once $f;
});
Auth::initSession();
$csrf_token = Auth::generateCSRF();

function redirect($url) { header("Location: " . $url); exit; }
function setFlash($type, $msg) { $_SESSION['flash'] = ['type' => $type, 'message' => $msg]; }
function getFlash() { if (isset($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; } return null; }
function sanitize($i) { return is_array($i) ? array_map('sanitize', $i) : trim($i); }
function post($k, $d = '') { return isset($_POST[$k]) ? sanitize($_POST[$k]) : $d; }
function get($k, $d = '') { return isset($_GET[$k]) ? sanitize($_GET[$k]) : $d; }
function formatMoney($a, $c = 'KES') { return Settings::formatCurrency($a, $c); }

function timeAgo($dt) {
    $now = new DateTime(); $ago = new DateTime($dt); $d = $now->diff($ago);
    if ($d->y > 0) return $d->y.'y ago'; if ($d->m > 0) return $d->m.'mo ago';
    if ($d->d > 0) return $d->d.'d ago'; if ($d->h > 0) return $d->h.'h ago';
    if ($d->i > 0) return $d->i.'m ago'; return 'Just now';
}

function statusBadge($s) {
    $c = [
        'draft'=>'border-gray-600/30 text-gray-400','pending'=>'border-amber-500/30 text-amber-400',
        'funded'=>'border-blue-500/30 text-blue-400','in_progress'=>'border-indigo-500/30 text-indigo-400',
        'delivered'=>'border-cyan-500/30 text-cyan-400','completed'=>'border-lime-500/30 text-lime-400',
        'disputed'=>'border-red-500/30 text-red-400','cancelled'=>'border-gray-600/30 text-gray-500',
        'refunded'=>'border-orange-500/30 text-orange-400','expired'=>'border-gray-600/30 text-gray-500',
        'active'=>'border-lime-500/30 text-lime-400','suspended'=>'border-red-500/30 text-red-400',
        'banned'=>'border-red-600/30 text-red-500','open'=>'border-red-500/30 text-red-400',
        'under_review'=>'border-amber-500/30 text-amber-400','resolved'=>'border-lime-500/30 text-lime-400',
        'closed'=>'border-gray-600/30 text-gray-500','approved'=>'border-lime-500/30 text-lime-400',
        'rejected'=>'border-red-500/30 text-red-400','none'=>'border-gray-600/30 text-gray-500',
        'processing'=>'border-blue-500/30 text-blue-400','failed'=>'border-red-500/30 text-red-400',
    ];
    $cl = $c[$s] ?? 'border-gray-600/30 text-gray-400';
    $label = ucwords(str_replace('_', ' ', $s));
    return "<span class=\"inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium border {$cl} bg-white/[0.03]\">{$label}</span>";
}

function getUnreadNotifications($uid) {
    return Database::getInstance()->fetchColumn("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0", [$uid]) ?: 0;
}
function getUnreadMessages($uid) {
    return Database::getInstance()->fetchColumn("SELECT COUNT(*) FROM messages m JOIN conversation_participants cp ON cp.conversation_id=m.conversation_id WHERE cp.user_id=? AND m.sender_id!=? AND m.is_read=0", [$uid,$uid]) ?: 0;
}

function renderPagination($cur, $total, $base) {
    if ($total <= 1) return '';
    $h = '<div class="flex items-center justify-center gap-1 mt-6">';
    $start = max(1, $cur-2); $end = min($total, $cur+2);
    if ($cur > 1) $h .= '<a href="'.$base.'&page='.($cur-1).'" class="px-3 py-1.5 text-sm rounded-lg border border-white/10 text-gray-400 hover:bg-white/5 hover:text-white transition-colors">Prev</a>';
    for ($i=$start; $i<=$end; $i++) {
        $act = $i===$cur ? 'bg-[#C8F545] text-[#0B0F19] font-bold' : 'border border-white/10 text-gray-400 hover:bg-white/5';
        $h .= '<a href="'.$base.'&page='.$i.'" class="px-3 py-1.5 text-sm rounded-lg '.$act.' transition-colors">'.$i.'</a>';
    }
    if ($cur < $total) $h .= '<a href="'.$base.'&page='.($cur+1).'" class="px-3 py-1.5 text-sm rounded-lg border border-white/10 text-gray-400 hover:bg-white/5 hover:text-white transition-colors">Next</a>';
    return $h . '</div>';
}

function checkMaintenance() {
    if (Settings::get('maintenance_mode') == '1' && !Auth::isAdmin()) {
        http_response_code(503); include APP_ROOT.'/templates/maintenance.php'; exit;
    }
}

function siteMedia($key, $fallback = '') {
    $path = Settings::getMedia($key);
    if (!$path) return $fallback;
    if (strpos($path, 'http') === 0) return $path;
    return APP_URL . '/' . ltrim($path, '/');
}

function siteLogo($variant = 'default') {
    $key = $variant === 'light' ? 'site_logo_light' : 'site_logo';
    $path = Settings::get($key);
    return $path ? (strpos($path, 'http') === 0 ? $path : APP_URL . '/' . ltrim($path, '/')) : '';
}

function favicon() {
    $f = Settings::get('favicon');
    return $f ? (strpos($f, 'http') === 0 ? $f : APP_URL . '/' . ltrim($f, '/')) : '';
}
