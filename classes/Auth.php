<?php
class Auth {
    private $db;
    private static $user = null;
    public function __construct() { $this->db = Database::getInstance(); }
    
    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', SESSION_HTTPONLY ? 1 : 0);
            ini_set('session.cookie_secure', SESSION_SECURE ? 1 : 0);
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
            ini_set('session.use_strict_mode', 1);
            session_name(SESSION_NAME);
            session_start();
            if (!isset($_SESSION['_created'])) { $_SESSION['_created'] = time(); }
            elseif (time() - $_SESSION['_created'] > 1800) { session_regenerate_id(true); $_SESSION['_created'] = time(); }
        }
    }
    
    public function register($data) {
        $errors = [];
        if (empty($data['first_name'])) $errors[] = 'First name is required';
        if (empty($data['last_name'])) $errors[] = 'Last name is required';
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required';
        if (empty($data['password']) || strlen($data['password']) < 8) $errors[] = 'Password must be 8+ characters';
        if ($data['password'] !== ($data['confirm_password'] ?? '')) $errors[] = 'Passwords do not match';
        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        $existing = $this->db->fetch("SELECT id FROM users WHERE email = ?", [$data['email']]);
        if ($existing) return ['success' => false, 'errors' => ['Email already registered']];

        $uuid = $this->generateUUID();
        $userId = $this->db->insert('users', [
            'uuid' => $uuid,
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => trim($data['phone'] ?? ''),
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'role' => 'user', 'status' => 'active', 'email_verified' => 0,
            'email_verification_token' => bin2hex(random_bytes(32)),
            'preferred_currency' => $data['currency'] ?? 'KES'
        ]);
        $this->db->insert('wallets', ['user_id' => $userId, 'currency' => $data['currency'] ?? 'KES']);
        $this->logActivity($userId, 'user.registered', 'users', $userId, 'New user registered');
        return ['success' => true, 'user_id' => $userId];
    }

    public function login($email, $password) {
        $user = $this->db->fetch("SELECT id,uuid,first_name,last_name,email,password_hash,role,status,avatar,kyc_status,trust_score FROM users WHERE email=?", [strtolower(trim($email))]);
        if (!$user || !password_verify($password, $user['password_hash'])) return ['success' => false, 'errors' => ['Invalid email or password']];
        if ($user['status'] === 'suspended') return ['success' => false, 'errors' => ['Account suspended. Contact support.']];
        if ($user['status'] === 'banned') return ['success' => false, 'errors' => ['Account banned.']];

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_uuid'] = $user['uuid'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['first_name'].' '.$user['last_name'];
        $_SESSION['user_avatar'] = $user['avatar'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        $this->db->update('users', ['last_login_at' => date('Y-m-d H:i:s'), 'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'], 'id=?', [$user['id']]);
        $this->logActivity($user['id'], 'user.login', 'users', $user['id'], 'User logged in');
        return ['success' => true, 'user' => $user];
    }

    public function logout() {
        if (isset($_SESSION['user_id'])) $this->logActivity($_SESSION['user_id'], 'user.logout', 'users', $_SESSION['user_id'], 'Logged out');
        $_SESSION = [];
        if (ini_get("session.use_cookies")) { $p = session_get_cookie_params(); setcookie(session_name(), '', time()-42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]); }
        session_destroy();
    }

    public static function user() {
        if (self::$user !== null) return self::$user;
        if (!self::check()) return null;
        $db = Database::getInstance();
        self::$user = $db->fetch("SELECT u.*, w.balance as wallet_balance, w.escrow_balance FROM users u LEFT JOIN wallets w ON w.user_id=u.id WHERE u.id=?", [$_SESSION['user_id']]);
        return self::$user;
    }
    public static function check() { return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_id']); }
    public static function hasRole($role) { if (!self::check()) return false; return is_array($role) ? in_array($_SESSION['user_role'], $role) : $_SESSION['user_role'] === $role; }
    public static function isAdmin() { return self::hasRole(['admin','superadmin']); }
    public static function isAgent() { return self::hasRole('agent'); }
    public static function requireAuth() { if (!self::check()) { $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; header('Location: '.APP_URL.'/pages/auth/login.php'); exit; } }
    public static function requireRole($role) { self::requireAuth(); if (!self::hasRole($role)) { http_response_code(403); include APP_ROOT.'/templates/403.php'; exit; } }
    public static function generateCSRF() { if (empty($_SESSION[CSRF_TOKEN_NAME])) $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32)); return $_SESSION[CSRF_TOKEN_NAME]; }
    public static function verifyCSRF($token = null) { $token = $token ?? ($_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''); if (empty($_SESSION[CSRF_TOKEN_NAME]) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], $token)) { http_response_code(403); die(json_encode(['success'=>false,'error'=>'Invalid security token'])); } return true; }
    public static function csrfField() { return '<input type="hidden" name="'.CSRF_TOKEN_NAME.'" value="'.htmlspecialchars(self::generateCSRF()).'">'; }

    public function updatePassword($userId, $current, $new) {
        $user = $this->db->fetch("SELECT password_hash FROM users WHERE id=?", [$userId]);
        if (!$user || !password_verify($current, $user['password_hash'])) return ['success'=>false,'errors'=>['Current password incorrect']];
        $this->db->update('users', ['password_hash' => password_hash($new, PASSWORD_BCRYPT, ['cost'=>12])], 'id=?', [$userId]);
        return ['success' => true];
    }

    private function generateUUID() {
        $d = random_bytes(16); $d[6] = chr(ord($d[6]) & 0x0f | 0x40); $d[8] = chr(ord($d[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }
    private function logActivity($uid, $action, $etype=null, $eid=null, $desc=null) {
        try { $this->db->insert('activity_logs', ['user_id'=>$uid,'action'=>$action,'entity_type'=>$etype,'entity_id'=>$eid,'description'=>$desc,'ip_address'=>$_SERVER['REMOTE_ADDR']??null,'user_agent'=>$_SERVER['HTTP_USER_AGENT']??null]); } catch(Exception $e) {}
    }
}
