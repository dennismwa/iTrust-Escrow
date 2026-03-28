<?php
require_once __DIR__.'/../../includes/init.php';
if(Auth::check())redirect(APP_URL.'/pages/dashboard/index.php');
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    Auth::verifyCSRF();$auth=new Auth();$r=$auth->register($_POST);
    if($r['success']){$auth->login($_POST['email'],$_POST['password']);setFlash('success','Welcome to Amani Escrow!');redirect(APP_URL.'/pages/dashboard/index.php');}
    else $errors=$r['errors'];
}
$pn=Settings::get('platform_name','Amani Escrow');$accent=Settings::get('primary_color','#C8F545');$logo=siteLogo();$fav=favicon();
?>
<!DOCTYPE html><html lang="en" class="h-full"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Register — <?= htmlspecialchars($pn) ?></title><?php if($fav):?><link rel="icon" href="<?= htmlspecialchars($fav) ?>"><?php endif;?><script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"><style>*{font-family:'Outfit',sans-serif}body{background:#0B0F19}</style></head>
<body class="h-full"><div class="min-h-full flex items-center justify-center px-6 py-12">
<div class="w-full max-w-md">
    <div class="flex items-center gap-3 mb-8">
        <?php if($logo):?><img src="<?= htmlspecialchars($logo) ?>" class="h-9"><?php else:?>
        <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:<?= $accent ?>"><svg class="w-5 h-5 text-[#0B0F19]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div>
        <span class="text-lg font-bold text-white"><?= htmlspecialchars($pn) ?></span><?php endif;?>
    </div>
    <h1 class="text-2xl font-bold text-white">Create your account</h1>
    <p class="mt-2 text-sm text-gray-500">Start securing transactions in minutes</p>
    <?php if(!empty($errors)):?><div class="mt-4 rounded-xl px-4 py-3 bg-red-500/10 border border-red-500/20"><?php foreach($errors as $e):?><p class="text-sm text-red-400"><?= htmlspecialchars($e) ?></p><?php endforeach;?></div><?php endif;?>
    <form method="POST" class="mt-6 space-y-4"><?= Auth::csrfField() ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-300 mb-1">First name</label><input type="text" name="first_name" value="<?= htmlspecialchars($_POST['first_name']??'') ?>" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-[<?= $accent ?>]/30"></div>
            <div><label class="block text-sm font-medium text-gray-300 mb-1">Last name</label><input type="text" name="last_name" value="<?= htmlspecialchars($_POST['last_name']??'') ?>" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-[<?= $accent ?>]/30"></div>
        </div>
        <div><label class="block text-sm font-medium text-gray-300 mb-1">Email</label><input type="email" name="email" value="<?= htmlspecialchars($_POST['email']??'') ?>" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-[<?= $accent ?>]/30" placeholder="you@example.com"></div>
        <div><label class="block text-sm font-medium text-gray-300 mb-1">Phone</label><input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone']??'') ?>" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-[<?= $accent ?>]/30" placeholder="+254 7XX XXX XXX"></div>
        <div><label class="block text-sm font-medium text-gray-300 mb-1">Password</label><input type="password" name="password" required minlength="8" class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-[<?= $accent ?>]/30" placeholder="Min 8 characters"></div>
        <div><label class="block text-sm font-medium text-gray-300 mb-1">Confirm password</label><input type="password" name="confirm_password" required class="w-full px-4 py-3 bg-[#131825] border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-[<?= $accent ?>]/30"></div>
        <button type="submit" class="w-full font-bold py-3.5 rounded-xl text-sm" style="background:<?= $accent ?>;color:#0B0F19">Create Account</button>
    </form>
    <p class="mt-6 text-center text-sm text-gray-500">Already have an account? <a href="<?= APP_URL ?>/pages/auth/login.php" class="font-semibold" style="color:<?= $accent ?>">Sign in</a></p>
</div>
</div></body></html>
