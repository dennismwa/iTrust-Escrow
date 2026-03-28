<?php
require_once __DIR__.'/../../includes/init.php';
if(Auth::check())redirect(APP_URL.'/pages/dashboard/index.php');
$sent=false;$pn=Settings::get('platform_name','Amani Escrow');$accent=Settings::get('primary_color','#C8F545');$fav=favicon();
if($_SERVER['REQUEST_METHOD']==='POST'){Auth::verifyCSRF();$sent=true;}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Reset — <?= htmlspecialchars($pn) ?></title><?php if($fav):?><link rel="icon" href="<?= htmlspecialchars($fav) ?>"><?php endif;?><script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet"><style>*{font-family:'Outfit',sans-serif}body{background:#0B0F19}</style></head>
<body class="min-h-screen flex items-center justify-center px-6"><div class="w-full max-w-md text-center">
    <h1 class="text-2xl font-bold text-white">Reset Password</h1>
    <?php if($sent):?><div class="mt-4 rounded-xl p-4 bg-green-500/10 border border-green-500/20 text-sm text-green-400">If an account exists, a reset link was sent.</div><?php endif;?>
    <form method="POST" class="mt-6 space-y-4 text-left"><?= Auth::csrfField() ?>
        <div><label class="block text-sm font-medium text-gray-300 mb-1">Email</label><input type="email" name="email" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-[<?= $accent ?>]/30" placeholder="you@example.com"></div>
        <button type="submit" class="w-full font-bold py-3 rounded-xl text-sm" style="background:<?= $accent ?>;color:#0B0F19">Send Reset Link</button>
    </form>
    <p class="mt-6 text-sm text-gray-500"><a href="<?= APP_URL ?>/pages/auth/login.php" class="font-semibold" style="color:<?= $accent ?>">Back to Sign In</a></p>
</div></body></html>
