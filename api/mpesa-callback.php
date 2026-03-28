<?php
/**
 * M-Pesa STK Push Callback
 * Safaricom calls this URL after payment is completed/failed
 */
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

$payload = json_decode(file_get_contents('php://input'), true);
$callback = $payload['Body']['stkCallback'] ?? null;

if (!$callback) {
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'No callback data']);
    exit;
}

$resultCode = $callback['ResultCode'] ?? 1;
$checkoutId = $callback['CheckoutRequestID'] ?? '';

// Log callback
error_log('M-Pesa Callback: ' . json_encode($callback));

$db = Database::getInstance();

// Find pending transaction by gateway_ref
$txn = $db->fetch("SELECT * FROM transactions WHERE gateway_ref = ? AND status = 'pending' AND payment_method = 'mpesa'", [$checkoutId]);

if (!$txn) {
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    exit;
}

if ($resultCode == 0) {
    // Payment successful — extract M-Pesa receipt
    $receipt = '';
    $items = $callback['CallbackMetadata']['Item'] ?? [];
    foreach ($items as $item) {
        if ($item['Name'] === 'MpesaReceiptNumber') $receipt = $item['Value'];
    }
    
    // Credit wallet
    $wallet = $db->fetch("SELECT * FROM wallets WHERE user_id = ? AND currency = 'KES'", [$txn['user_id']]);
    if ($wallet) {
        $db->update('wallets', ['balance' => $wallet['balance'] + $txn['amount']], 'id = ?', [$wallet['id']]);
    } else {
        $db->insert('wallets', ['user_id' => $txn['user_id'], 'currency' => 'KES', 'balance' => $txn['amount']]);
    }
    
    $db->update('transactions', [
        'status' => 'completed',
        'gateway_reference' => $receipt ?: $checkoutId,
        'completed_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$txn['id']]);
    
    // Notify user
    $db->insert('notifications', [
        'user_id' => $txn['user_id'],
        'type' => 'deposit.completed',
        'title' => 'Deposit Successful',
        'message' => 'M-Pesa deposit of KES ' . number_format($txn['amount'], 2) . ' confirmed. Ref: ' . $receipt,
        'link' => '/pages/wallet/index.php'
    ]);
} else {
    // Payment failed
    $db->update('transactions', ['status' => 'failed', 'completed_at' => date('Y-m-d H:i:s')], 'id = ?', [$txn['id']]);
}

echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
