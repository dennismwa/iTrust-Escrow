<?php
/**
 * PaymentGateway — Production payment processing
 * Supports: M-Pesa STK Push, Stripe, PayPal, Crypto, Bank Transfer, Manual
 */
class PaymentGateway {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get gateway config
     */
    public function getGateway($name) {
        $gw = $this->db->fetch("SELECT * FROM payment_gateways WHERE name = ? AND is_active = 1", [$name]);
        if ($gw) $gw['config'] = json_decode($gw['config'], true) ?: [];
        return $gw;
    }
    
    /**
     * Get all active gateways for deposits
     */
    public function getActiveGateways() {
        $gws = $this->db->fetchAll("SELECT * FROM payment_gateways WHERE is_active = 1 ORDER BY sort_order");
        foreach ($gws as &$g) $g['config'] = json_decode($g['config'], true) ?: [];
        return $gws;
    }
    
    /**
     * Get active withdrawal methods
     */
    public function getWithdrawalMethods() {
        return $this->db->fetchAll("SELECT * FROM payment_gateways WHERE is_active = 1 AND name IN ('mpesa','bank_transfer','crypto') ORDER BY sort_order");
    }
    
    /**
     * Initiate M-Pesa STK Push
     */
    public function mpesaStkPush($phone, $amount, $reference, $userId) {
        $gw = $this->getGateway('mpesa');
        if (!$gw) return ['success' => false, 'error' => 'M-Pesa not configured'];
        
        $c = $gw['config'];
        $env = $gw['environment'];
        $baseUrl = $env === 'live' 
            ? 'https://api.safaricom.co.ke' 
            : 'https://sandbox.safaricom.co.ke';
        
        // Get access token
        $token = $this->getMpesaToken($c['consumer_key'], $c['consumer_secret'], $baseUrl);
        if (!$token) return ['success' => false, 'error' => 'Failed to get M-Pesa token'];
        
        // Format phone
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 1) === '0') $phone = '254' . substr($phone, 1);
        if (substr($phone, 0, 1) !== '2') $phone = '254' . $phone;
        
        $timestamp = date('YmdHis');
        $password = base64_encode($c['shortcode'] . $c['passkey'] . $timestamp);
        $callbackUrl = $c['callback_url'] ?: (APP_URL . '/api/mpesa-callback.php');
        
        $payload = [
            'BusinessShortCode' => $c['shortcode'],
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => intval($amount),
            'PartyA' => $phone,
            'PartyB' => $c['shortcode'],
            'PhoneNumber' => $phone,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => $reference,
            'TransactionDesc' => 'Escrow Deposit'
        ];
        
        $response = $this->curlPost($baseUrl . '/mpesa/stkpush/v1/processrequest', $payload, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        
        if ($response && isset($response['ResponseCode']) && $response['ResponseCode'] === '0') {
            // Create pending transaction
            $txnRef = 'MPE-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
            $this->db->insert('transactions', [
                'uuid' => bin2hex(random_bytes(16)),
                'transaction_ref' => $txnRef,
                'user_id' => $userId,
                'type' => 'deposit',
                'amount' => $amount,
                'currency' => 'KES',
                'fee' => 0,
                'net_amount' => $amount,
                'status' => 'pending',
                'payment_method' => 'mpesa',
                'gateway_reference' => $response['CheckoutRequestID'] ?? '',
                'description' => 'M-Pesa deposit via STK Push',
            ]);
            return ['success' => true, 'checkout_id' => $response['CheckoutRequestID'] ?? '', 'message' => 'STK Push sent. Check your phone.'];
        }
        
        return ['success' => false, 'error' => $response['errorMessage'] ?? $response['ResponseDescription'] ?? 'STK Push failed'];
    }
    
    /**
     * Process Stripe payment intent
     */
    public function stripeCreateIntent($amount, $currency, $userId) {
        $gw = $this->getGateway('stripe');
        if (!$gw) return ['success' => false, 'error' => 'Stripe not configured'];
        
        $c = $gw['config'];
        $response = $this->curlPost('https://api.stripe.com/v1/payment_intents', [
            'amount' => intval($amount * 100),
            'currency' => strtolower($currency),
            'metadata[user_id]' => $userId,
            'metadata[platform]' => Settings::get('platform_name', 'Amani Escrow'),
        ], ['Authorization: Basic ' . base64_encode($c['secret_key'] . ':')], 'form');
        
        if ($response && isset($response['client_secret'])) {
            $txnRef = 'STR-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
            $this->db->insert('transactions', [
                'uuid' => bin2hex(random_bytes(16)),
                'transaction_ref' => $txnRef,
                'user_id' => $userId,
                'type' => 'deposit',
                'amount' => $amount,
                'currency' => strtoupper($currency),
                'fee' => 0,
                'net_amount' => $amount,
                'status' => 'pending',
                'payment_method' => 'stripe',
                'gateway_reference' => $response['id'],
                'description' => 'Card deposit via Stripe',
            ]);
            return ['success' => true, 'client_secret' => $response['client_secret'], 'public_key' => $c['public_key']];
        }
        
        return ['success' => false, 'error' => $response['error']['message'] ?? 'Stripe error'];
    }
    
    /**
     * Create manual/bank deposit record (pending admin confirmation)
     */
    public function createManualDeposit($userId, $amount, $currency, $method, $proofPath = null, $details = []) {
        $txnRef = 'MAN-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        $desc = $method === 'bank_transfer' ? 'Bank transfer deposit' : 'Manual deposit';
        
        $this->db->insert('transactions', [
            'uuid' => bin2hex(random_bytes(16)),
            'transaction_ref' => $txnRef,
            'user_id' => $userId,
            'type' => 'deposit',
            'amount' => $amount,
            'currency' => $currency,
            'fee' => 0,
            'net_amount' => $amount,
            'status' => 'pending',
            'payment_method' => $method,
            'description' => $desc,
            'metadata' => json_encode(array_merge($details, ['proof' => $proofPath])),
        ]);
        
        // Notify admin
        $admins = $this->db->fetchAll("SELECT id FROM users WHERE role IN ('admin','superadmin')");
        foreach ($admins as $a) {
            $this->db->insert('notifications', [
                'user_id' => $a['id'],
                'type' => 'payment.pending',
                'title' => 'Manual Deposit Pending',
                'message' => "New {$method} deposit of {$currency} " . number_format($amount, 2) . " requires confirmation.",
                'link' => '/pages/admin/payments.php'
            ]);
        }
        
        return ['success' => true, 'ref' => $txnRef, 'message' => 'Deposit submitted. Awaiting admin confirmation.'];
    }
    
    /**
     * Admin confirm manual deposit
     */
    public function confirmDeposit($txnId, $adminId) {
        $txn = $this->db->fetch("SELECT * FROM transactions WHERE id = ? AND status = 'pending' AND type = 'deposit'", [$txnId]);
        if (!$txn) return ['success' => false, 'error' => 'Transaction not found'];
        
        $this->db->beginTransaction();
        try {
            $wallet = $this->db->fetch("SELECT * FROM wallets WHERE user_id = ? AND currency = ?", [$txn['user_id'], $txn['currency']]);
            if ($wallet) {
                $this->db->update('wallets', ['balance' => $wallet['balance'] + $txn['amount']], 'id = ?', [$wallet['id']]);
            } else {
                $this->db->insert('wallets', ['user_id' => $txn['user_id'], 'currency' => $txn['currency'], 'balance' => $txn['amount']]);
            }
            $this->db->update('transactions', ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')], 'id = ?', [$txnId]);
            $this->db->insert('notifications', [
                'user_id' => $txn['user_id'], 'type' => 'deposit.confirmed',
                'title' => 'Deposit Confirmed', 'message' => 'Your deposit of ' . $txn['currency'] . ' ' . number_format($txn['amount'], 2) . ' has been confirmed.',
                'link' => '/pages/wallet/index.php'
            ]);
            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) { $this->db->rollback(); return ['success' => false, 'error' => 'Failed']; }
    }
    
    /**
     * Admin reject deposit
     */
    public function rejectDeposit($txnId, $adminId, $reason = '') {
        $txn = $this->db->fetch("SELECT * FROM transactions WHERE id = ? AND status = 'pending' AND type = 'deposit'", [$txnId]);
        if (!$txn) return ['success' => false, 'error' => 'Not found'];
        $this->db->update('transactions', ['status' => 'failed', 'completed_at' => date('Y-m-d H:i:s')], 'id = ?', [$txnId]);
        $this->db->insert('notifications', [
            'user_id' => $txn['user_id'], 'type' => 'deposit.rejected',
            'title' => 'Deposit Rejected', 'message' => 'Your deposit was rejected. ' . $reason,
            'link' => '/pages/wallet/index.php'
        ]);
        return ['success' => true];
    }
    
    // ── Helpers ──
    private function getMpesaToken($key, $secret, $baseUrl) {
        $ch = curl_init($baseUrl . '/oauth/v1/generate?grant_type=client_credentials');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Authorization: Basic ' . base64_encode($key . ':' . $secret)]]);
        $r = json_decode(curl_exec($ch), true); curl_close($ch);
        return $r['access_token'] ?? null;
    }
    
    private function curlPost($url, $data, $headers = [], $type = 'json') {
        $ch = curl_init($url);
        $body = $type === 'json' ? json_encode($data) : http_build_query($data);
        if ($type === 'json' && !in_array('Content-Type: application/json', $headers)) $headers[] = 'Content-Type: application/json';
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 30]);
        $r = json_decode(curl_exec($ch), true); curl_close($ch);
        return $r;
    }
}
