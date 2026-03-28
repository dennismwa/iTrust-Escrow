<?php
require_once __DIR__ . '/includes/init.php';
if (Auth::check()) { if (Auth::isAdmin()) redirect(APP_URL.'/pages/admin/dashboard.php'); else redirect(APP_URL.'/pages/dashboard/index.php'); }

$pn = Settings::get('platform_name', 'Amani Escrow');
$accent = Settings::get('primary_color', '#C8F545');
$bgDark = Settings::get('bg_dark', '#0B0F19');
$heroStyle = Settings::get('hero_style', 'A');
$heroTitle = Settings::get('homepage_hero_title', 'Secure Escrow Services for Online Transactions');
$heroSub = Settings::get('homepage_hero_subtitle', 'Apply quickly and easily for the most secure escrow transactions on the market.');
$btn1 = Settings::get('homepage_hero_btn1_text', 'Get Started');
$btn2 = Settings::get('homepage_hero_btn2_text', 'How it works?');
$heroBgOpacity = Settings::get('hero_bg_opacity', '0.4');
$aboutTitle = Settings::get('homepage_about_title', 'Trust your transactions with us — Safe, Secure, and Efficient!');
$aboutText = Settings::get('homepage_about_text', 'Amani Escrow delivers secure escrow solutions built to protect your transactions.');
$statsTxn = Settings::get('homepage_stats_transactions', '50K+');
$statsUsers = Settings::get('homepage_stats_users', '168K+');
$statsCountries = Settings::get('homepage_stats_countries', '8+');
$statsLabel1 = Settings::get('homepage_stats_label1', 'Transactions');
$statsLabel2 = Settings::get('homepage_stats_label2', 'Users');
$statsLabel3 = Settings::get('homepage_stats_label3', 'Countries');
$logo = siteLogo();
$fav = favicon();
$heroImg = siteMedia('hero_image');
$heroMockup = siteMedia('hero_mockup');
$heroBgImg = siteMedia('hero_bg_image');
$aboutImg = siteMedia('about_image');
$pwaEnabled = Settings::get('pwa_enabled', '1');

function renderTitle($t, $a) {
    $t = htmlspecialchars($t);
    $t = preg_replace('/\{\{(.*?)\}\}/', '<span style="color:'.$a.'">$1</span>', $t);
    $t = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $t);
    return $t;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="<?= htmlspecialchars($bgDark) ?>">
    <meta name="description" content="<?= htmlspecialchars($heroSub) ?>">
    <title><?= htmlspecialchars($pn) ?> — Secure Escrow Platform</title>
    <?php if ($fav): ?><link rel="icon" href="<?= htmlspecialchars($fav) ?>"><?php endif; ?>
    <?php if ($pwaEnabled === '1'): ?><link rel="manifest" href="<?= APP_URL ?>/manifest.json"><?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700;9..40,800&display=swap" rel="stylesheet">
    <style>
        *{font-family:'DM Sans',system-ui,sans-serif}
        body{background:<?= $bgDark ?>;color:#94a3b8}
        .at{color:<?= $accent ?>}.ab{background:<?= $accent ?>}
        .card{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06)}.card:hover{border-color:rgba(255,255,255,.12)}
        @keyframes fu{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
        @keyframes fi{from{opacity:0}to{opacity:1}}
        @keyframes fl{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
        .au{animation:fu .5s ease both}.au1{animation:fu .5s .06s ease both;opacity:0}.au2{animation:fu .5s .12s ease both;opacity:0}.au3{animation:fu .5s .18s ease both;opacity:0}
        .af{animation:fi .6s .15s ease both;opacity:0}.flt{animation:fl 5s ease-in-out infinite}
        .orb{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .mob{transform:translateY(-100%);transition:transform .3s ease}.mob.open{transform:translateY(0)}
        html{scroll-behavior:smooth}
    </style>
</head>
<body class="overflow-x-hidden">

<!-- NAV -->
<nav class="fixed top-0 inset-x-0 z-50">
    <div class="absolute inset-0" style="background:<?= $bgDark ?>e8;backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px)"></div>
    <div class="relative max-w-7xl mx-auto px-5 lg:px-8 flex items-center justify-between h-16">
        <a href="<?= APP_URL ?>" class="flex items-center gap-2.5 shrink-0">
            <?php if ($logo): ?><img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($pn) ?>" class="h-7 w-auto">
            <?php else: ?>
            <div class="w-8 h-8 rounded-lg ab flex items-center justify-center"><svg class="w-4 h-4" style="color:<?= $bgDark ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div>
            <span class="text-[15px] font-bold text-white hidden sm:inline"><?= htmlspecialchars($pn) ?></span>
            <?php endif; ?>
        </a>
        <div class="hidden lg:flex items-center gap-1">
            <a href="#how-it-works" class="px-3 py-2 text-[13px] text-gray-400 hover:text-white rounded-lg hover:bg-white/[0.04] transition-all">How It Works</a>
            <a href="#features" class="px-3 py-2 text-[13px] text-gray-400 hover:text-white rounded-lg hover:bg-white/[0.04] transition-all">Features</a>
            <a href="#about" class="px-3 py-2 text-[13px] text-gray-400 hover:text-white rounded-lg hover:bg-white/[0.04] transition-all">About</a>
            <a href="#use-cases" class="px-3 py-2 text-[13px] text-gray-400 hover:text-white rounded-lg hover:bg-white/[0.04] transition-all">Use Cases</a>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= APP_URL ?>/pages/auth/register.php" class="hidden sm:inline text-[13px] text-gray-400 hover:text-white px-3 py-2">Sign up</a>
            <a href="<?= APP_URL ?>/pages/auth/login.php" class="ab text-[13px] font-bold px-5 py-2 rounded-full hover:opacity-90" style="color:<?= $bgDark ?>">Login</a>
            <button onclick="document.getElementById('mm').classList.toggle('open')" class="lg:hidden p-2 -mr-2 text-gray-400"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        </div>
    </div>
    <div id="mm" class="mob lg:hidden absolute top-full inset-x-0 border-b border-white/[0.06]" style="background:<?= $bgDark ?>f2;backdrop-filter:blur(20px)">
        <div class="px-5 py-3 space-y-1">
            <a href="#how-it-works" onclick="document.getElementById('mm').classList.remove('open')" class="block px-4 py-2.5 text-sm text-gray-400 hover:text-white hover:bg-white/[0.04] rounded-xl">How It Works</a>
            <a href="#features" onclick="document.getElementById('mm').classList.remove('open')" class="block px-4 py-2.5 text-sm text-gray-400 hover:text-white hover:bg-white/[0.04] rounded-xl">Features</a>
            <a href="#about" onclick="document.getElementById('mm').classList.remove('open')" class="block px-4 py-2.5 text-sm text-gray-400 hover:text-white hover:bg-white/[0.04] rounded-xl">About</a>
            <a href="#use-cases" onclick="document.getElementById('mm').classList.remove('open')" class="block px-4 py-2.5 text-sm text-gray-400 hover:text-white hover:bg-white/[0.04] rounded-xl">Use Cases</a>
            <div class="pt-2 border-t border-white/[0.06] flex gap-2">
                <a href="<?= APP_URL ?>/pages/auth/register.php" class="flex-1 text-center py-2.5 text-sm text-white border border-white/10 rounded-xl">Sign Up</a>
                <a href="<?= APP_URL ?>/pages/auth/login.php" class="flex-1 text-center py-2.5 text-sm font-bold ab rounded-xl" style="color:<?= $bgDark ?>">Login</a>
            </div>
        </div>
    </div>
</nav>


<?php if ($heroStyle === 'B'): ?>
<!-- ═══ HERO B: Background Image ═══ -->
<section class="relative flex items-end pt-16" style="min-height:88vh">
    <?php if ($heroBgImg): ?>
    <div class="absolute inset-0 bg-cover bg-center" style="background-image:url('<?= htmlspecialchars($heroBgImg) ?>')"></div>
    <div class="absolute inset-0" style="background:<?= $bgDark ?>;opacity:<?= floatval($heroBgOpacity) ?>"></div>
    <?php else: ?>
    <div class="absolute inset-0" style="background:<?= $bgDark ?>"></div>
    <?php endif; ?>
    <div class="absolute bottom-0 left-0 right-0 h-40" style="background:linear-gradient(to top,<?= $bgDark ?>,transparent)"></div>
    <div class="relative max-w-7xl mx-auto px-5 lg:px-8 w-full pb-16 pt-24">
        <div class="max-w-xl">
            <h1 class="text-[clamp(1.8rem,4.5vw,3.2rem)] font-extrabold text-white leading-[1.1] au"><?= renderTitle($heroTitle, $accent) ?></h1>
            <p class="mt-4 text-[15px] text-gray-300 leading-relaxed au1 max-w-md"><?= htmlspecialchars($heroSub) ?></p>
            <div class="flex flex-wrap items-center gap-4 mt-7 au2">
                <a href="<?= APP_URL ?>/pages/auth/register.php" class="group inline-flex items-center gap-2 ab font-bold px-6 py-3 rounded-full text-[14px] hover:opacity-90" style="color:<?= $bgDark ?>"><?= htmlspecialchars($btn1) ?> <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg></a>
                <a href="#how-it-works" class="text-[14px] font-medium text-white/80 hover:text-white"><?= htmlspecialchars($btn2) ?></a>
            </div>
            <div class="flex items-center gap-6 mt-10 au3">
                <div class="flex items-center gap-2"><div class="orb" style="background:<?= $accent ?>20;color:<?= $accent ?>"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div><div><p class="text-base font-extrabold text-white"><?= htmlspecialchars($statsTxn) ?></p><p class="text-[10px] text-gray-400"><?= htmlspecialchars($statsLabel1) ?></p></div></div>
                <div class="flex items-center gap-2"><div class="orb" style="background:rgba(59,130,246,.18);color:#3b82f6"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg></div><div><p class="text-base font-extrabold text-white"><?= htmlspecialchars($statsUsers) ?></p><p class="text-[10px] text-gray-400"><?= htmlspecialchars($statsLabel2) ?></p></div></div>
                <div class="flex items-center gap-2"><div class="orb" style="background:rgba(245,158,11,.18);color:#f59e0b"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064"/></svg></div><div><p class="text-base font-extrabold text-white"><?= htmlspecialchars($statsCountries) ?></p><p class="text-[10px] text-gray-400"><?= htmlspecialchars($statsLabel3) ?></p></div></div>
            </div>
        </div>
    </div>
</section>

<?php else: ?>
<!-- ═══ HERO A: Image Right — Tight Flex Layout ═══ -->
<section class="pt-16">
    <div class="absolute top-40 left-[20%] w-[300px] h-[300px] rounded-full opacity-[0.03]" style="background:<?= $accent ?>;filter:blur(90px)"></div>
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="flex flex-col lg:flex-row items-center gap-6 lg:gap-0 py-12 lg:py-0 lg:min-h-[calc(100vh-64px)]">
            <!-- Text — takes natural width, doesn't stretch -->
            <div class="w-full lg:w-[44%] shrink-0 lg:pr-6 lg:py-16">
                <h1 class="text-[clamp(1.8rem,4.5vw,3.2rem)] font-extrabold text-white leading-[1.1] au"><?= renderTitle($heroTitle, $accent) ?></h1>
                <p class="mt-4 text-[15px] text-gray-400 leading-relaxed au1 max-w-md"><?= htmlspecialchars($heroSub) ?></p>
                <div class="flex flex-wrap items-center gap-4 mt-7 au2">
                    <a href="<?= APP_URL ?>/pages/auth/register.php" class="group inline-flex items-center gap-2 ab font-bold px-6 py-3 rounded-full text-[14px] hover:opacity-90" style="color:<?= $bgDark ?>"><?= htmlspecialchars($btn1) ?> <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg></a>
                    <a href="#how-it-works" class="text-[14px] font-medium text-gray-300 hover:text-white"><?= htmlspecialchars($btn2) ?></a>
                </div>
                <div class="flex items-center gap-6 sm:gap-8 mt-10 au3">
                    <div class="flex items-center gap-2"><div class="orb" style="background:<?= $accent ?>18;color:<?= $accent ?>"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div><div><p class="text-base font-extrabold text-white"><?= htmlspecialchars($statsTxn) ?></p><p class="text-[10px] text-gray-500"><?= htmlspecialchars($statsLabel1) ?></p></div></div>
                    <div class="flex items-center gap-2"><div class="orb" style="background:rgba(59,130,246,.15);color:#3b82f6"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg></div><div><p class="text-base font-extrabold text-white"><?= htmlspecialchars($statsUsers) ?></p><p class="text-[10px] text-gray-500"><?= htmlspecialchars($statsLabel2) ?></p></div></div>
                    <div class="flex items-center gap-2"><div class="orb" style="background:rgba(245,158,11,.15);color:#f59e0b"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064"/></svg></div><div><p class="text-base font-extrabold text-white"><?= htmlspecialchars($statsCountries) ?></p><p class="text-[10px] text-gray-500"><?= htmlspecialchars($statsLabel3) ?></p></div></div>
                </div>
            </div>
            <!-- Image — fills remaining space, no gap -->
            <div class="w-full lg:flex-1 flex justify-center lg:justify-end af">
                <?php if ($heroMockup): ?>
                    <img src="<?= htmlspecialchars($heroMockup) ?>" alt="<?= htmlspecialchars($pn) ?>" class="w-full max-w-[480px] lg:max-w-none lg:w-auto h-auto max-h-[420px] sm:max-h-[480px] lg:max-h-[85vh] object-contain flt">
                <?php elseif ($heroImg): ?>
                    <img src="<?= htmlspecialchars($heroImg) ?>" alt="<?= htmlspecialchars($pn) ?>" class="w-full max-w-[480px] lg:max-w-none lg:w-auto h-auto max-h-[420px] sm:max-h-[480px] lg:max-h-[85vh] object-contain rounded-2xl flt">
                <?php else: ?>
                    <div class="card rounded-2xl p-6 w-full max-w-xs flt">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4" style="background:<?= $accent ?>12"><svg class="w-6 h-6 at" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                        <div class="space-y-2">
                            <div class="flex gap-2"><div class="flex-1 h-10 rounded-lg bg-white/[0.04] border border-white/[0.08] px-3 flex items-center text-xs text-gray-500">I AM SELLING</div><div class="flex-1 h-10 rounded-lg bg-white/[0.04] border border-white/[0.08] px-3 flex items-center text-xs text-gray-500">Amount</div></div>
                            <div class="h-10 rounded-lg bg-white/[0.04] border border-white/[0.08] px-3 flex items-center justify-between text-xs text-gray-500">Category <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></div>
                            <button class="w-full h-10 rounded-lg ab font-bold text-sm" style="color:<?= $bgDark ?>">Continue</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>


<!-- HOW IT WORKS -->
<section id="how-it-works" class="py-20 lg:py-24">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="max-w-xl mb-12"><p class="text-[12px] font-bold uppercase tracking-[.2em] at mb-2">How It Works</p><h2 class="text-[clamp(1.5rem,3vw,2.2rem)] font-extrabold text-white leading-tight">Four steps to a secure transaction.</h2></div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <?php $steps=[['Create','Set up escrow — terms, amount, invite seller.','M12 4v16m8-8H4'],['Fund','Deposit funds into secure vault.','M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],['Deliver','Seller delivers product or service.','M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4'],['Release','Buyer confirms. Funds released.','M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z']];
            foreach($steps as $i=>$s): ?>
            <div class="card rounded-2xl p-4 sm:p-5 transition-all group">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center mb-3" style="background:<?= $accent ?>0d"><svg class="w-4 h-4 at" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $s[2] ?>"/></svg></div>
                <p class="text-[10px] font-bold uppercase tracking-widest at mb-1.5">Step <?= $i+1 ?></p>
                <h3 class="text-sm sm:text-base font-bold text-white mb-1"><?= $s[0] ?></h3>
                <p class="text-xs sm:text-sm text-gray-500 leading-relaxed"><?= $s[1] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- FEATURES -->
<section id="features" class="py-20 lg:py-24" style="background:#0c1120">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="max-w-xl mb-12"><p class="text-[12px] font-bold uppercase tracking-[.2em] at mb-2">Features</p><h2 class="text-[clamp(1.5rem,3vw,2.2rem)] font-extrabold text-white leading-tight">Built for Africa's digital economy.</h2></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
            <?php $feats=[['M-Pesa','Pay via M-Pesa STK Push instantly.','M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],['Milestones','Progressive release for large deals.','M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],['KYC','ID-verified users for max trust.','M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1'],['Multi-Currency','KES, USD, NGN, GHS, ZAR.','M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945'],['Disputes','Fair evidence-based mediation.','M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],['Trust Scores','Reputation from completed deals.','M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],['Contracts','SHA-256 hashed auto-contracts.','M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],['Live Chat','Messaging per escrow.','M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],['Auto-Complete','Releases after inspection.','M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z']];
            foreach($feats as $f): ?>
            <div class="card rounded-2xl p-4 sm:p-5 transition-all group">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center mb-3" style="background:<?= $accent ?>0d"><svg class="w-4 h-4 at opacity-70 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $f[2] ?>"/></svg></div>
                <h3 class="text-[13px] sm:text-[14px] font-bold text-white mb-1"><?= $f[0] ?></h3>
                <p class="text-xs sm:text-[13px] text-gray-500 leading-relaxed"><?= $f[1] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ABOUT -->
<section id="about" class="py-20 lg:py-24">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
            <div>
                <p class="text-[12px] font-bold uppercase tracking-[.2em] at mb-2.5">About Us</p>
                <h2 class="text-[clamp(1.5rem,3vw,2.2rem)] font-extrabold text-white leading-tight"><?= htmlspecialchars($aboutTitle) ?></h2>
                <p class="mt-4 text-gray-400 leading-relaxed text-[15px]"><?= htmlspecialchars($aboutText) ?></p>
                <div class="grid grid-cols-3 gap-3 mt-8">
                    <div class="card rounded-xl p-3 text-center"><p class="text-lg font-extrabold at">99.9%</p><p class="text-[10px] text-gray-500 mt-0.5">Uptime</p></div>
                    <div class="card rounded-xl p-3 text-center"><p class="text-lg font-extrabold at">256-bit</p><p class="text-[10px] text-gray-500 mt-0.5">Encryption</p></div>
                    <div class="card rounded-xl p-3 text-center"><p class="text-lg font-extrabold at">24/7</p><p class="text-[10px] text-gray-500 mt-0.5">Support</p></div>
                </div>
            </div>
            <div class="flex justify-center lg:justify-end">
                <?php if ($aboutImg): ?><img src="<?= htmlspecialchars($aboutImg) ?>" alt="About" class="rounded-2xl max-h-[360px] w-auto border border-white/[0.06]"><?php else: ?><div class="w-full max-w-md aspect-[4/3] rounded-2xl card flex items-center justify-center"><svg class="w-14 h-14 at opacity-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="0.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div><?php endif; ?>
            </div>
        </div>
    </div>
</section>


<!-- USE CASES -->
<section id="use-cases" class="py-20 lg:py-24" style="background:#0c1120">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="text-center max-w-xl mx-auto mb-12"><p class="text-[12px] font-bold uppercase tracking-[.2em] at mb-2">Use Cases</p><h2 class="text-[clamp(1.5rem,3vw,2.2rem)] font-extrabold text-white">Escrow for every deal.</h2></div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <?php foreach(['Car Purchases','Property Deals','Freelance Work','Marketplace','Import/Export','Electronics','Digital Services','High-Value Items'] as $c): ?>
            <div class="card rounded-xl p-4 text-center transition-all"><p class="text-xs sm:text-sm font-bold text-white"><?= $c ?></p></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- CTA -->
<section class="py-20 lg:py-24">
    <div class="max-w-3xl mx-auto px-5 lg:px-8 text-center">
        <h2 class="text-[clamp(1.5rem,3.5vw,2.6rem)] font-extrabold text-white leading-tight">Ready to secure your next <span class="at">transaction</span>?</h2>
        <p class="mt-4 text-base text-gray-400 max-w-md mx-auto">Join thousands who trust <?= htmlspecialchars($pn) ?>.</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mt-8">
            <a href="<?= APP_URL ?>/pages/auth/register.php" class="ab font-bold px-8 py-3 rounded-full text-[14px] hover:opacity-90" style="color:<?= $bgDark ?>">Create Free Account</a>
            <a href="<?= APP_URL ?>/pages/auth/login.php" class="font-medium px-6 py-3 rounded-full text-[14px] text-white border border-white/10 hover:bg-white/[0.04]">Sign In</a>
        </div>
    </div>
</section>


<!-- FOOTER -->
<footer class="border-t border-white/[0.04] py-6">
    <div class="max-w-7xl mx-auto px-5 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-2"><?php if ($logo): ?><img src="<?= htmlspecialchars($logo) ?>" alt="" class="h-5 w-auto opacity-50"><?php else: ?><span class="text-xs font-bold text-white/40"><?= htmlspecialchars($pn) ?></span><?php endif; ?></div>
        <div class="flex items-center gap-4"><a href="#how-it-works" class="text-[11px] text-gray-600 hover:text-gray-400">How It Works</a><a href="#features" class="text-[11px] text-gray-600 hover:text-gray-400">Features</a><a href="#about" class="text-[11px] text-gray-600 hover:text-gray-400">About</a></div>
        <p class="text-[11px] text-gray-600">&copy; <?= date('Y') ?> <?= htmlspecialchars($pn) ?></p>
    </div>
</footer>

<?php if ($pwaEnabled === '1'): ?><script>if('serviceWorker' in navigator){navigator.serviceWorker.register('<?= APP_URL ?>/sw.js').catch(()=>{});}</script><?php endif; ?>
</body>
</html>
