<?php
require_once __DIR__ . '/includes/init.php';
header('Content-Type: application/json');
$icon192 = Settings::get('pwa_icon_192') ? APP_URL.'/'.ltrim(Settings::get('pwa_icon_192'),'/') : APP_URL.'/uploads/site/pwa-192.png';
$icon512 = Settings::get('pwa_icon_512') ? APP_URL.'/'.ltrim(Settings::get('pwa_icon_512'),'/') : APP_URL.'/uploads/site/pwa-512.png';
echo json_encode([
    'name' => Settings::get('pwa_name', 'Amani Escrow'),
    'short_name' => Settings::get('pwa_short_name', 'Amani'),
    'description' => Settings::get('platform_tagline', "Africa's Most Trusted Escrow Platform"),
    'start_url' => APP_URL . '/pages/dashboard/index.php',
    'display' => 'standalone',
    'background_color' => Settings::get('pwa_bg_color', '#0B0F19'),
    'theme_color' => Settings::get('pwa_theme_color', '#0B0F19'),
    'orientation' => 'portrait-primary',
    'icons' => [
        ['src' => $icon192, 'sizes' => '192x192', 'type' => 'image/png'],
        ['src' => $icon512, 'sizes' => '512x512', 'type' => 'image/png']
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
