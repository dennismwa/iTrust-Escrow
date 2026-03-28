<?php
class Settings {
    private static $cache = null;
    public static function get($key, $default = null) {
        self::loadAll();
        return self::$cache[$key] ?? $default;
    }
    public static function set($key, $value) {
        $db = Database::getInstance();
        $existing = $db->fetch("SELECT id FROM settings WHERE setting_key=?", [$key]);
        if ($existing) $db->update('settings', ['setting_value'=>$value], 'setting_key=?', [$key]);
        self::$cache[$key] = $value;
    }
    private static function loadAll() {
        if (self::$cache !== null) return;
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
        self::$cache = [];
        foreach ($rows as $r) self::$cache[$r['setting_key']] = $r['setting_value'];
    }
    public static function getByCategory($cat) {
        return Database::getInstance()->fetchAll("SELECT * FROM settings WHERE category=? ORDER BY id", [$cat]);
    }
    public static function getCategories() {
        return Database::getInstance()->fetchAll("SELECT DISTINCT category FROM settings ORDER BY category");
    }
    public static function formatCurrency($amount, $currency = null) {
        $currency = $currency ?? 'KES';
        $db = Database::getInstance();
        $c = $db->fetch("SELECT symbol FROM currencies WHERE code=?", [$currency]);
        return ($c ? $c['symbol'] : $currency) . ' ' . number_format($amount, 2);
    }
    public static function clearCache() { self::$cache = null; }
    
    // Get site media
    public static function getMedia($key) {
        $db = Database::getInstance();
        $m = $db->fetch("SELECT * FROM site_media WHERE media_key=?", [$key]);
        return $m && $m['file_path'] ? $m['file_path'] : null;
    }
    public static function getMediaBySection($section) {
        return Database::getInstance()->fetchAll("SELECT * FROM site_media WHERE section=? AND is_active=1 ORDER BY sort_order", [$section]);
    }
}
