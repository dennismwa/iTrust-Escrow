<?php
$currentUser = Auth::user();
$unreadNotifs = getUnreadNotifications($currentUser['id']);
$unreadMsgs = getUnreadMessages($currentUser['id']);
$platformName = Settings::get('platform_name', 'Amani Escrow');
$accentColor = Settings::get('primary_color', '#C8F545');
$isAdmin = Auth::isAdmin();
$isAgent = Auth::isAgent();
$currentPage = basename(dirname($_SERVER['PHP_SELF']));
$currentFile = basename($_SERVER['PHP_SELF'], '.php');
$logoUrl = siteLogo();
$faviconUrl = favicon();
$pwaEnabled = Settings::get('pwa_enabled', '1');
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="<?= htmlspecialchars(Settings::get('pwa_theme_color', '#0B0F19')) ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> — <?= htmlspecialchars($platformName) ?></title>
    <?php if ($faviconUrl): ?><link rel="icon" href="<?= htmlspecialchars($faviconUrl) ?>"><?php endif; ?>
    <?php if ($pwaEnabled === '1'): ?>
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars(Settings::get('pwa_icon_192') ? APP_URL.'/'.ltrim(Settings::get('pwa_icon_192'),'/') : '') ?>">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: {
            surface: { DEFAULT:'#0B0F19', 50:'#0E1220', 100:'#131825', 200:'#1A2035', 300:'#222940' },
            accent: { DEFAULT:'<?= htmlspecialchars($accentColor) ?>', dark:'#A8D435' },
        }, fontFamily: { sans:['Outfit','system-ui','sans-serif'], display:['Clash Display','system-ui','sans-serif'] } }}}
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{font-family:'Outfit',system-ui,sans-serif;box-sizing:border-box}
        body{background:#0B0F19;color:#e2e8f0;overflow-x:hidden;min-width:0}
        html{overflow-x:hidden}
        .sidebar-link{transition:all .15s ease;border-left:2px solid transparent}
        .sidebar-link:hover{background:rgba(255,255,255,.04);border-left-color:rgba(200,245,69,.3)}
        .sidebar-link.active{background:rgba(200,245,69,.06);border-left-color:#C8F545;color:#C8F545}
        .glass-card{background:rgba(19,24,37,.7);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.06)}
        .glow-accent{box-shadow:0 0 20px rgba(200,245,69,.08)}
        ::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:#222940;border-radius:10px}
        .dropdown-menu{display:none}.dropdown-menu.show{display:block;animation:slideDown .15s ease}
        @keyframes slideDown{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
        .mobile-sidebar{transform:translateX(-100%);transition:transform .3s cubic-bezier(.4,0,.2,1)}.mobile-sidebar.open{transform:translateX(0)}
        @keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}.fade-in{animation:fadeIn .4s ease}
        input,select,textarea{color-scheme:dark;max-width:100%}
        a, button { transition: all .15s ease; }
        table tr { transition: background .15s ease; }
        .peer:checked ~ div { background: #C8F545; }
        main > * { animation: fadeIn .3s ease both; }
        main > *:nth-child(2) { animation-delay: .05s; }
        main > *:nth-child(3) { animation-delay: .1s; }
        main > *:nth-child(4) { animation-delay: .15s; }

        /* ═══ GLOBAL MOBILE RESPONSIVE ═══ */
        *, *::before, *::after { box-sizing: border-box; }
        main, .flex-1, section, div, form, article { max-width: 100%; min-width: 0; }
        img, video, svg:not(.w-3):not(.w-4):not(.w-5), canvas { max-width: 100%; height: auto; }
        pre, code { overflow-x: auto; word-break: break-all; }
        .truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }

        @media (max-width: 1023px) {
            /* Reduce main padding on tablets */
            main { padding-left: 14px !important; padding-right: 14px !important; }
        }

        @media (max-width: 768px) {
            /* Core layout */
            main { padding-left: 12px !important; padding-right: 12px !important; }

            /* Reduce all px-6 to px-3 on mobile */
            .px-6 { padding-left: 12px !important; padding-right: 12px !important; }
            .lg\:px-8 { padding-left: 12px !important; padding-right: 12px !important; }

            /* Table cells — reduce padding */
            td.px-6, th.px-6, td[class*="px-6"], th[class*="px-6"] {
                padding-left: 10px !important; padding-right: 10px !important;
            }

            /* Tables — scrollable container */
            .overflow-x-auto { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .overflow-x-auto table { min-width: 580px; }

            /* Card grids — smaller gaps */
            .grid { gap: 8px; }
            .gap-6 { gap: 12px; }
            .gap-4 { gap: 8px; }

            /* Typography scaling */
            .text-2xl { font-size: 1.15rem; }
            .text-3xl { font-size: 1.35rem; }
            h1.text-2xl { font-size: 1.25rem; }

            /* Forms — single column on mobile */
            form .grid-cols-2,
            form .sm\:grid-cols-2 { grid-template-columns: 1fr !important; }
            form select, form input[type="text"], form input[type="email"],
            form input[type="number"], form input[type="tel"], form input[type="password"],
            form textarea { width: 100% !important; min-width: 0 !important; }

            /* Buttons — prevent overflow */
            .flex-wrap { flex-wrap: wrap; }
            button, .inline-flex, a.inline-flex { max-width: 100%; }

            /* Admin dispute resolve form */
            form.inline-flex, form .inline-flex { flex-wrap: wrap; gap: 6px; }
            form .inline-flex select, form .inline-flex input { min-width: 100px; }

            /* Modal fixes */
            .confirm-box { margin: 12px; max-width: calc(100vw - 24px) !important; padding: 20px !important; }

            /* Dropdown menus */
            .dropdown-menu { max-width: calc(100vw - 24px); right: 0; }

            /* Chat widget */
            .chat-widget { bottom: 16px; right: 12px; }
            .chat-panel { width: calc(100vw - 24px); right: -4px; }
            .chat-bubble { width: 50px; height: 50px; }

            /* Header topbar */
            header h2 { font-size: 0.95rem; max-width: 50vw; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

            /* Glass cards — less padding on mobile */
            .glass-card.p-6, .glass-card .p-6 { padding: 16px; }

            /* Max width containers */
            .max-w-3xl, .max-w-4xl, .max-w-5xl { max-width: 100%; }

            /* Fix escrow progress tracker */
            .min-w-\[500px\] { min-width: 480px; }

            /* Notification dropdown */
            #notifDD { width: calc(100vw - 24px) !important; max-width: 340px; }
        }

        @media (max-width: 480px) {
            main { padding-left: 10px !important; padding-right: 10px !important; }
            .px-6 { padding-left: 10px !important; padding-right: 10px !important; }

            /* Stat cards */
            .text-2xl { font-size: 1.05rem; }
            .text-xl { font-size: 0.95rem; }
            .text-lg { font-size: 0.9rem; }

            /* Smaller card padding */
            .p-6 { padding: 14px; }
            .p-5 { padding: 12px; }
            .p-4 { padding: 10px; }

            /* Stack flex items */
            .flex.items-center.gap-6,
            .flex.items-center.gap-8,
            .flex.items-center.gap-10 { gap: 12px; }

            /* Button text */
            .text-sm { font-size: 0.8rem; }
            .text-xs { font-size: 0.68rem; }

            /* Stats orbs */
            .orb { width: 36px; height: 36px; }
        }

        @media (max-width: 380px) {
            main { padding-left: 8px !important; padding-right: 8px !important; }
            .px-6 { padding-left: 8px !important; padding-right: 8px !important; }
            .text-2xl { font-size: 0.95rem; }
            .text-3xl { font-size: 1.1rem; }
            .gap-3 { gap: 4px; }
            .gap-4 { gap: 6px; }
            .orb { width: 32px; height: 32px; }
            .p-6 { padding: 12px; }
            .p-5 { padding: 10px; }
            .p-4 { padding: 8px; }
            .rounded-2xl { border-radius: 12px; }
            td.px-6, th.px-6, td[class*="px-6"], th[class*="px-6"] {
                padding-left: 6px !important; padding-right: 6px !important;
            }
        }
        /* Custom confirm modal */
        .confirm-overlay{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);animation:cFadeIn .15s ease}
        .confirm-box{background:#131825;border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:28px;max-width:380px;width:calc(100% - 32px);animation:cSlideUp .2s ease}
        @keyframes cFadeIn{from{opacity:0}to{opacity:1}}
        @keyframes cSlideUp{from{opacity:0;transform:translateY(12px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}
        /* Chat widget */
        .chat-widget{position:fixed;bottom:24px;right:24px;z-index:50}
        .chat-bubble{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 4px 24px rgba(0,0,0,.4);transition:transform .2s}
        .chat-bubble:hover{transform:scale(1.08)}
        .chat-panel{position:absolute;bottom:68px;right:0;width:340px;max-width:calc(100vw - 32px);border-radius:20px;overflow:hidden;box-shadow:0 12px 48px rgba(0,0,0,.5);transform:scale(.9) translateY(10px);opacity:0;pointer-events:none;transition:all .2s ease}
        .chat-panel.open{transform:scale(1) translateY(0);opacity:1;pointer-events:auto}
        /* Mobile overflow fix */
        .overflow-clip{overflow:hidden;max-width:100vw}
    </style>
</head>
<script>
function toggleSidebar() {
    const mobile = document.getElementById('mobileSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const isOpen = mobile.classList.contains('open');
    mobile.classList.toggle('open', !isOpen);
    overlay.classList.toggle('hidden', isOpen);
    document.body.style.overflow = !isOpen ? 'hidden' : '';
}

function toggleDropdown(id) {
    document.querySelectorAll('.dropdown-menu.show').forEach(el => {
        if (el.id !== id) el.classList.remove('show');
    });
    document.getElementById(id)?.classList.toggle('show');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('[onclick*="toggleDropdown"]') && !e.target.closest('.dropdown-menu')) {
        document.querySelectorAll('.dropdown-menu.show').forEach(el => el.classList.remove('show'));
    }
});
</script>
<body class="h-full overflow-x-hidden">
<div class="flex h-full">

<!-- Sidebar -->
<aside id="sidebar" class="hidden lg:flex lg:flex-col w-[260px] bg-surface-100 fixed inset-y-0 left-0 z-40 border-r border-white/[0.06]">
    <div class="flex items-center gap-3 px-6 h-16 border-b border-white/[0.06]">
        <?php if ($logoUrl): ?>
            <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($platformName) ?>" class="h-8 w-auto max-w-[200px] object-contain object-left">
        <?php else: ?>
            <div class="w-8 h-8 rounded-lg bg-accent flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-surface" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <span class="text-[15px] font-bold text-white tracking-tight"><?= htmlspecialchars($platformName) ?></span>
        <?php endif; ?>
    </div>

    <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-0.5">
        <?php if ($isAdmin): ?>
            <p class="px-3 pt-1 pb-2 text-[10px] font-bold uppercase tracking-[.15em] text-gray-500">Administration</p>
            <?php
            $adminLinks = [
                ['dashboard','admin','Dashboard','M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                ['users','admin','Users','M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                ['agents','admin','Agents','M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                ['escrows','admin','Escrows','M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                ['disputes','admin','Disputes','M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                ['payments','admin','Payments','M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                ['kyc','admin','KYC Verification','M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2'],
                ['gateways','admin','Payment Gateways','M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                ['reports','admin','Reports','M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                ['settings','admin','System Settings','M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                ['media','admin','Site Media','M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ['audit','admin','Audit Logs','M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
            ];
            foreach ($adminLinks as $l):
                $active = ($currentFile === $l[0] && $currentPage === $l[1]);
            ?>
            <a href="<?= APP_URL ?>/pages/<?= $l[1] ?>/<?= $l[0] ?>.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13px] text-gray-400 hover:text-white <?= $active ? 'active' : '' ?>">
                <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $l[3] ?>"/></svg>
                <?= $l[2] ?>
            </a>
            <?php endforeach; ?>

        <?php else: ?>
            <p class="px-3 pt-1 pb-2 text-[10px] font-bold uppercase tracking-[.15em] text-gray-500">Main</p>
            <?php
            $userLinks = [
                ['index','dashboard','Dashboard','M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                ['index','escrow','Escrows','M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
            ];
            $financeLinks = [
                ['index','wallet','Wallet','M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                ['withdrawals','wallet','Withdrawals','M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ];
            $supportLinks = [
                ['index','disputes','Disputes','M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                ['index','messages','Messages','M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                ['index','reports','Reports','M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
            ];
            $accountLinks = [
                ['index','kyc','KYC Verification','M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0'],
                ['index','profile','Profile','M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ['index','settings','Settings','M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
            ];
            $sections = [
                ['Main', $userLinks], ['Finance', $financeLinks], ['Support', $supportLinks], ['Account', $accountLinks]
            ];
            foreach ($sections as $si => $sec):
                if ($si > 0): ?><p class="px-3 pt-5 pb-2 text-[10px] font-bold uppercase tracking-[.15em] text-gray-500"><?= $sec[0] ?></p><?php endif;
                foreach ($sec[1] as $l):
                    $active = ($currentFile === $l[0] && $currentPage === $l[1]) || ($currentPage === $l[1] && $l[0] === 'index');
                ?>
                <a href="<?= APP_URL ?>/pages/<?= $l[1] ?>/<?= $l[0] ?>.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13px] text-gray-400 hover:text-white <?= $active ? 'active' : '' ?>">
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $l[3] ?>"/></svg>
                    <?= $l[2] ?>
                    <?php if ($l[2] === 'Messages' && $unreadMsgs > 0): ?>
                        <span class="ml-auto w-5 h-5 rounded-full bg-accent text-surface text-[10px] font-bold flex items-center justify-center"><?= $unreadMsgs ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach;
            endforeach; ?>
        <?php endif; ?>
    </nav>

    <div class="px-4 py-4 border-t border-white/[0.06]">
        <div class="flex items-center gap-3">
            <?php
            $avatarUrl = !empty($currentUser['avatar']) ? (strpos($currentUser['avatar'], 'http') === 0 ? $currentUser['avatar'] : APP_URL . '/' . ltrim($currentUser['avatar'], '/')) : '';
            ?>
            <?php if ($avatarUrl): ?>
                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="" class="w-9 h-9 rounded-full object-cover border border-accent/20">
            <?php else: ?>
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-accent/30 to-accent/10 flex items-center justify-center text-accent text-xs font-bold border border-accent/20">
                    <?= strtoupper(substr($currentUser['first_name'],0,1).substr($currentUser['last_name'],0,1)) ?>
                </div>
            <?php endif; ?>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($currentUser['first_name'].' '.$currentUser['last_name']) ?></p>
                <p class="text-[11px] text-gray-500 truncate"><?= htmlspecialchars($currentUser['email']) ?></p>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile overlay -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-30 hidden lg:hidden" onclick="toggleSidebar()"></div>
<aside id="mobileSidebar" class="mobile-sidebar fixed inset-y-0 left-0 z-40 w-[280px] bg-surface-100 border-r border-white/[0.06] lg:hidden overflow-y-auto">
    <div class="flex items-center justify-between gap-3 px-6 h-16 border-b border-white/[0.06]">
        <?php if ($logoUrl): ?>
            <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($platformName) ?>" class="h-8 w-auto max-w-[180px] object-contain object-left">
        <?php else: ?>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-accent flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-surface" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <span class="text-[15px] font-bold text-white tracking-tight"><?= htmlspecialchars($platformName) ?></span>
            </div>
        <?php endif; ?>
        <button onclick="toggleSidebar()" class="p-2 rounded-lg hover:bg-white/5 text-gray-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <nav class="flex-1 py-5 px-3 space-y-0.5">
        <?php if ($isAdmin): ?>
            <p class="px-3 pt-1 pb-2 text-[10px] font-bold uppercase tracking-[.15em] text-gray-500">Administration</p>
            <?php foreach ($adminLinks as $l):
                $active = ($currentFile === $l[0] && $currentPage === $l[1]); ?>
            <a href="<?= APP_URL ?>/pages/<?= $l[1] ?>/<?= $l[0] ?>.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13px] text-gray-400 hover:text-white <?= $active ? 'active' : '' ?>" onclick="toggleSidebar()">
                <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $l[3] ?>"/></svg>
                <?= $l[2] ?>
            </a>
            <?php endforeach; ?>
        <?php else: ?>
            <?php foreach ($sections as $si => $sec):
                if ($si > 0): ?><p class="px-3 pt-5 pb-2 text-[10px] font-bold uppercase tracking-[.15em] text-gray-500"><?= $sec[0] ?></p><?php endif;
                foreach ($sec[1] as $l):
                    $active = ($currentFile === $l[0] && $currentPage === $l[1]) || ($currentPage === $l[1] && $l[0] === 'index'); ?>
                <a href="<?= APP_URL ?>/pages/<?= $l[1] ?>/<?= $l[0] ?>.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13px] text-gray-400 hover:text-white <?= $active ? 'active' : '' ?>" onclick="toggleSidebar()">
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $l[3] ?>"/></svg>
                    <?= $l[2] ?>
                    <?php if ($l[2] === 'Messages' && $unreadMsgs > 0): ?>
                        <span class="ml-auto w-5 h-5 rounded-full bg-accent text-surface text-[10px] font-bold flex items-center justify-center"><?= $unreadMsgs ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach;
            endforeach; ?>
        <?php endif; ?>
    </nav>
    <div class="px-4 py-4 border-t border-white/[0.06]">
        <div class="flex items-center gap-3">
            <?php
            $avatarUrl = !empty($currentUser['avatar']) ? (strpos($currentUser['avatar'], 'http') === 0 ? $currentUser['avatar'] : APP_URL . '/' . ltrim($currentUser['avatar'], '/')) : '';
            ?>
            <?php if ($avatarUrl): ?>
                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="" class="w-9 h-9 rounded-full object-cover border border-accent/20">
            <?php else: ?>
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-accent/30 to-accent/10 flex items-center justify-center text-accent text-xs font-bold border border-accent/20">
                    <?= strtoupper(substr($currentUser['first_name'],0,1).substr($currentUser['last_name'],0,1)) ?>
                </div>
            <?php endif; ?>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($currentUser['first_name'].' '.$currentUser['last_name']) ?></p>
                <p class="text-[11px] text-gray-500 truncate"><?= htmlspecialchars($currentUser['email']) ?></p>
            </div>
        </div>
    </div>
</aside>

<!-- Main -->
<div class="flex-1 lg:ml-[260px] flex flex-col min-h-screen">
    <!-- Top bar -->
    <header class="sticky top-0 z-20 bg-surface/80 backdrop-blur-xl border-b border-white/[0.06]">
        <div class="flex items-center justify-between px-4 lg:px-8 h-16">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-white/5 text-gray-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h2 class="text-lg font-bold text-white"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h2>
            </div>
            <div class="flex items-center gap-1 sm:gap-2 shrink-0">
                <?php if (!$isAdmin): ?>
                <a href="<?= APP_URL ?>/pages/escrow/create.php" class="hidden sm:inline-flex items-center gap-2 bg-accent hover:bg-accent-dark text-surface text-sm font-bold px-4 py-2 rounded-xl transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    New Escrow
                </a>
                <?php endif; ?>
                <!-- Notifications -->
                <div class="relative">
                    <button onclick="toggleDropdown('notifDD')" class="relative p-2 rounded-xl hover:bg-white/5 transition-colors text-gray-400 hover:text-white shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <?php if ($unreadNotifs > 0): ?><span class="absolute top-1 right-1 w-2 h-2 bg-accent rounded-full"></span><?php endif; ?>
                    </button>
                    <div id="notifDD" class="dropdown-menu absolute right-0 mt-2 w-80 max-w-[calc(100vw-24px)] rounded-2xl overflow-hidden shadow-2xl border border-white/[0.08]" style="background:#0E1220">
                        <div class="px-4 py-3 border-b border-white/[0.08]" style="background:#131825"><p class="text-sm font-bold text-white">Notifications</p></div>
                        <div class="max-h-72 overflow-y-auto" id="notifList" style="background:#0E1220"><p class="px-4 py-6 text-sm text-gray-500 text-center">Loading...</p></div>
                        <a href="<?= APP_URL ?>/pages/dashboard/notifications.php" class="block px-4 py-2.5 text-center text-xs font-semibold text-accent border-t border-white/[0.08]" style="background:#131825">View all</a>
                    </div>
                </div>
                <!-- User menu -->
                <div class="relative">
                    <button onclick="toggleDropdown('userDD')" class="flex items-center gap-1 p-1 rounded-xl hover:bg-white/5 transition-colors shrink-0">
                        <?php if ($avatarUrl): ?>
                            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="" class="w-7 h-7 sm:w-8 sm:h-8 rounded-full object-cover border border-accent/20">
                        <?php else: ?>
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-accent/20 flex items-center justify-center text-accent text-[10px] sm:text-xs font-bold border border-accent/20"><?= strtoupper(substr($currentUser['first_name'],0,1)) ?></div>
                        <?php endif; ?>
                        <svg class="w-3 h-3 text-gray-500 hidden sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="userDD" class="dropdown-menu absolute right-0 mt-2 w-56 max-w-[calc(100vw-24px)] rounded-2xl overflow-hidden shadow-2xl border border-white/[0.08]" style="background:#0E1220">
                        <div class="px-4 py-3 border-b border-white/[0.06]">
                            <p class="text-sm font-bold text-white"><?= htmlspecialchars($currentUser['first_name'].' '.$currentUser['last_name']) ?></p>
                            <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($currentUser['email']) ?></p>
                        </div>
                        <div class="py-1">
                            <a href="<?= APP_URL ?>/pages/profile/index.php" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-white/[0.03]">Profile</a>
                            <a href="<?= APP_URL ?>/pages/settings/index.php" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-white/[0.03]">Settings</a>
                        </div>
                        <div class="py-1 border-t border-white/[0.06]">
                            <a href="<?= APP_URL ?>/pages/auth/logout.php" class="flex items-center gap-2 px-4 py-2 text-sm text-red-400 hover:text-red-300 hover:bg-white/[0.03]">Sign Out</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash -->
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="mx-4 lg:mx-8 mt-4 fade-in">
        <div class="rounded-xl px-4 py-3 flex items-center gap-3 border <?= $flash['type']==='success' ? 'bg-lime-500/10 border-lime-500/20 text-lime-400' : 'bg-red-500/10 border-red-500/20 text-red-400' ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $flash['type']==='success' ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' : 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' ?>"/></svg>
            <p class="text-sm font-medium"><?= htmlspecialchars($flash['message']) ?></p>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    </div>
    <?php endif; ?>

    <main class="flex-1 px-4 lg:px-8 py-6">
