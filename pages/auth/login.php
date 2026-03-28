<?php
require_once __DIR__.'/../../includes/init.php';
if(Auth::check()){if(Auth::isAdmin())redirect(APP_URL.'/pages/admin/dashboard.php');else redirect(APP_URL.'/pages/dashboard/index.php');}
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $auth=new Auth();$r=$auth->login($_POST['email']??'',$_POST['password']??'');
    if($r['success']){$u=$_SESSION['redirect_url']??null;unset($_SESSION['redirect_url']);redirect(Auth::isAdmin()?($u??APP_URL.'/pages/admin/dashboard.php'):($u??APP_URL.'/pages/dashboard/index.php'));}
    else $errors=$r['errors'];
}
$pn=Settings::get('platform_name','Amani Escrow');$logo=siteLogo();$fav=favicon();$accent=Settings::get('primary_color','#C8F545');
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <meta name="theme-color" content="#0B0F19">
    <title>Sign In — <?= htmlspecialchars($pn) ?></title>
    <?php if($fav):?><link rel="icon" href="<?= htmlspecialchars($fav) ?>"><?php endif;?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>*{font-family:'Outfit',sans-serif}body{background:#0B0F19}</style>
</head>
<body class="h-full">
<div class="min-h-full flex">
    <!-- Left panel -->
    <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden flex-col justify-between p-12" style="background:#0E1220">
        <div class="absolute inset-0 opacity-20" style="background:radial-gradient(circle at 30% 40%,<?= $accent ?>22 0%,transparent 50%),radial-gradient(circle at 70% 80%,<?= $accent ?>11 0%,transparent 50%)"></div>
        <div class="absolute top-0 right-0 w-96 h-96 opacity-5" style="background:conic-gradient(from 180deg,<?= $accent ?>,transparent,<?= $accent ?>);border-radius:50%;filter:blur(80px)"></div>
        <div class="relative z-10">
            <?php if($logo):?><img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($pn) ?>" class="h-10 w-auto"><?php else:?>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:<?= $accent ?>"><svg class="w-6 h-6 text-[#0B0F19]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div>
                <span class="text-xl font-bold text-white"><?= htmlspecialchars($pn) ?></span>
            </div>
            <?php endif;?>
        </div>
        <div class="relative z-10 space-y-6">
            <h2 class="text-5xl font-extrabold text-white leading-tight">Secure Escrow<br>Services for<br><span style="color:<?= $accent ?>">Online Transactions</span></h2>
            <p class="text-gray-400 text-lg max-w-md leading-relaxed">Protecting buyers and sellers from fraud. Swift, reliable, and efficient.</p>
            <div class="flex flex-wrap items-center gap-6 sm:gap-10 pt-4">
                <div><p class="text-3xl font-extrabold text-white"><?= Settings::get('homepage_stats_transactions','50K+') ?></p><p class="text-sm text-gray-500">Transactions</p></div>
                <div class="w-px h-12 bg-white/10"></div>
                <div><p class="text-3xl font-extrabold text-white"><?= Settings::get('homepage_stats_users','168K+') ?></p><p class="text-sm text-gray-500">Users</p></div>
                <div class="w-px h-12 bg-white/10"></div>
                <div><p class="text-3xl font-extrabold text-white"><?= Settings::get('homepage_stats_countries','8+') ?></p><p class="text-sm text-gray-500">Countries</p></div>
            </div>
        </div>
        <p class="relative z-10 text-sm text-gray-600">&copy; <?= date('Y') ?> <?= htmlspecialchars($pn) ?></p>
    </div>

    <!-- Right form -->
    <div class="flex-1 flex items-center justify-center px-6 py-12" style="background:#0B0F19">
        <div class="w-full max-w-md">
            <div class="lg:hidden flex items-center gap-3 mb-10">
                <?php if($logo):?><img src="<?= htmlspecialchars($logo) ?>" alt="" class="h-9"><?php else:?>
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:<?= $accent ?>"><svg class="w-5 h-5 text-[#0B0F19]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div>
                <span class="text-lg font-bold text-white"><?= htmlspecialchars($pn) ?></span>
                <?php endif;?>
            </div>

            <h1 class="text-2xl font-bold text-white">Welcome back</h1>
            <p class="mt-2 text-sm text-gray-500">Sign in to your escrow dashboard</p>

            <?php if(!empty($errors)):?><div class="mt-6 rounded-xl px-4 py-3 bg-red-500/10 border border-red-500/20"><?php foreach($errors as $e):?><p class="text-sm text-red-400"><?= htmlspecialchars($e) ?></p><?php endforeach;?></div><?php endif;?>

            <form method="POST" class="mt-8 space-y-5">
                <?= Auth::csrfField() ?>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Email address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($_POST['email']??'') ?>" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-[<?= $accent ?>]/30 focus:border-[<?= $accent ?>]/50 transition-all" placeholder="you@example.com">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-gray-300">Password</label>
                        <a href="<?= APP_URL ?>/pages/auth/forgot-password.php" class="text-xs font-medium" style="color:<?= $accent ?>">Forgot?</a>
                    </div>
                    <input type="password" name="password" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-[<?= $accent ?>]/30 focus:border-[<?= $accent ?>]/50 transition-all" placeholder="Enter password">
                </div>
                <button type="submit" class="w-full font-bold py-3.5 px-4 rounded-xl text-sm transition-all hover:opacity-90" style="background:<?= $accent ?>;color:#0B0F19">Sign In</button>
            </form>

            <p class="mt-8 text-center text-sm text-gray-500">Don't have an account? <a href="<?= APP_URL ?>/pages/auth/register.php" class="font-semibold" style="color:<?= $accent ?>">Create account</a></p>

            
        </div>
    </div>
</div>
</body>
</html>
