<?php require_once __DIR__.'/../../includes/init.php';(new Auth())->logout();redirect(APP_URL.'/pages/auth/login.php');
