<?php
class Escrow {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function create($data) {
        $errors = [];
        if (empty($data['title'])) $errors[] = 'Title required';
        if (empty($data['description'])) $errors[] = 'Description required';
        if (empty($data['amount']) || $data['amount'] <= 0) $errors[] = 'Valid amount required';
        if (!empty($errors)) return ['success'=>false,'errors'=>$errors];

        $uuid = $this->uuid(); $eid = 'ESC-'.strtoupper(substr(md5(uniqid()),0,8));
        $fee = $this->calculateFee($data['amount']); $total = $data['amount'] + $fee;

        $this->db->beginTransaction();
        try {
            $id = $this->db->insert('escrows', [
                'uuid'=>$uuid, 'escrow_id'=>$eid, 'title'=>$data['title'],
                'description'=>$data['description'], 'category'=>$data['category']??'other',
                'buyer_id'=>$data['buyer_id'], 'seller_id'=>$data['seller_id']??null,
                'amount'=>$data['amount'], 'currency'=>$data['currency']??'KES',
                'escrow_fee'=>$fee, 'fee_paid_by'=>$data['fee_paid_by']??'buyer', 'total_amount'=>$total,
                'status'=>($data['seller_id'] ?? null) ? 'pending' : 'draft',
                'inspection_period_days'=>$data['inspection_days']??3,
                'delivery_deadline'=>$data['deadline']??null, 'terms'=>$data['terms']??'',
                'is_milestone'=>$data['is_milestone']??0,
                'invitation_email'=>$data['invitation_email']??null,
                'invitation_token'=>($data['invitation_email']??null) ? bin2hex(random_bytes(16)) : null
            ]);
            $cid = $this->db->insert('conversations', ['escrow_id'=>$id,'type'=>'escrow']);
            $this->db->insert('conversation_participants', ['conversation_id'=>$cid,'user_id'=>$data['buyer_id']]);
            if ($data['seller_id'] ?? null) {
                $this->db->insert('conversation_participants', ['conversation_id'=>$cid,'user_id'=>$data['seller_id']]);
                $this->notify($data['seller_id'], 'escrow.created', 'New Escrow Invitation', "You've been invited to escrow {$eid}", "/pages/escrow/view.php?id={$id}");
            }
            $this->generateContract($id);
            $this->log($data['buyer_id'], 'escrow.created', 'escrows', $id, "Created escrow {$eid}");
            $this->db->commit();
            return ['success'=>true,'escrow_id'=>$id,'escrow_ref'=>$eid];
        } catch (Exception $e) { $this->db->rollback(); return ['success'=>false,'errors'=>['Failed: '.$e->getMessage()]]; }
    }

    public function fund($escrowId, $userId) {
        $e = $this->getById($escrowId);
        if (!$e) return ['success'=>false,'errors'=>['Not found']];
        if ($e['buyer_id'] != $userId) return ['success'=>false,'errors'=>['Only buyer can fund']];
        if (!in_array($e['status'], ['pending','draft'])) return ['success'=>false,'errors'=>['Cannot fund in current status']];
        $this->db->beginTransaction();
        try {
            $amt = $e['total_amount'];
            $w = $this->db->fetch("SELECT * FROM wallets WHERE user_id=? AND currency=?", [$userId, $e['currency']]);
            if (!$w || $w['balance'] < $amt) { $this->db->rollback(); return ['success'=>false,'errors'=>['Insufficient balance']]; }
            $this->db->update('wallets', ['balance'=>$w['balance']-$amt, 'escrow_balance'=>$w['escrow_balance']+$e['amount']], 'id=?', [$w['id']]);
            $this->db->update('escrows', ['status'=>'funded','funded_at'=>date('Y-m-d H:i:s')], 'id=?', [$escrowId]);
            $this->txn($userId, $escrowId, 'escrow_fund', $amt, $e['currency'], $e['escrow_fee'], $e['amount'], "Funded escrow {$e['escrow_id']}");
            if ($e['seller_id']) $this->notify($e['seller_id'], 'escrow.funded', 'Escrow Funded', "Escrow {$e['escrow_id']} has been funded", "/pages/escrow/view.php?id={$escrowId}");
            $this->log($userId, 'escrow.funded', 'escrows', $escrowId, "Funded escrow");
            $this->db->commit(); return ['success'=>true];
        } catch (Exception $ex) { $this->db->rollback(); return ['success'=>false,'errors'=>['Fund failed']]; }
    }

    public function markDelivered($id, $uid) {
        $e = $this->getById($id);
        if (!$e || $e['seller_id'] != $uid) return ['success'=>false,'errors'=>['Unauthorized']];
        if (!in_array($e['status'], ['funded','in_progress'])) return ['success'=>false,'errors'=>['Wrong status']];
        $auto = date('Y-m-d H:i:s', strtotime("+{$e['inspection_period_days']} days"));
        $this->db->update('escrows', ['status'=>'delivered','delivered_at'=>date('Y-m-d H:i:s'),'auto_complete_at'=>$auto], 'id=?', [$id]);
        $this->notify($e['buyer_id'], 'escrow.delivered', 'Delivery Notification', "Seller marked escrow {$e['escrow_id']} as delivered", "/pages/escrow/view.php?id={$id}");
        return ['success'=>true];
    }

    public function confirmDelivery($id, $uid) {
        $e = $this->getById($id);
        if (!$e || $e['buyer_id'] != $uid || $e['status'] !== 'delivered') return ['success'=>false,'errors'=>['Cannot confirm']];
        return $this->releaseFunds($id, $uid);
    }

    public function releaseFunds($id, $by) {
        $e = $this->getById($id);
        if (!$e || !$e['seller_id']) return ['success'=>false,'errors'=>['Invalid']];
        $this->db->beginTransaction();
        try {
            $release = $e['amount'];
            if ($e['fee_paid_by']==='seller') $release -= $e['escrow_fee'];
            elseif ($e['fee_paid_by']==='split') $release -= ($e['escrow_fee']/2);
            $sw = $this->db->fetch("SELECT * FROM wallets WHERE user_id=? AND currency=?", [$e['seller_id'],$e['currency']]);
            if ($sw) $this->db->update('wallets', ['balance'=>$sw['balance']+$release,'total_earned'=>$sw['total_earned']+$release], 'id=?', [$sw['id']]);
            else $this->db->insert('wallets', ['user_id'=>$e['seller_id'],'currency'=>$e['currency'],'balance'=>$release,'total_earned'=>$release]);
            $bw = $this->db->fetch("SELECT * FROM wallets WHERE user_id=? AND currency=?", [$e['buyer_id'],$e['currency']]);
            if ($bw) $this->db->update('wallets', ['escrow_balance'=>max(0,$bw['escrow_balance']-$e['amount'])], 'id=?', [$bw['id']]);
            $this->db->update('escrows', ['status'=>'completed','completed_at'=>date('Y-m-d H:i:s')], 'id=?', [$id]);
            $this->txn($e['seller_id'], $id, 'escrow_release', $release, $e['currency'], 0, $release, "Funds released from {$e['escrow_id']}");
            $this->updateTrust($e['buyer_id']); $this->updateTrust($e['seller_id']);
            $this->notify($e['seller_id'], 'escrow.completed', 'Funds Released!', "Funds for {$e['escrow_id']} released to wallet", "/pages/wallet/index.php");
            $this->notify($e['buyer_id'], 'escrow.completed', 'Escrow Completed', "Escrow {$e['escrow_id']} completed", "/pages/escrow/view.php?id={$id}");
            $this->db->commit(); return ['success'=>true];
        } catch (Exception $ex) { $this->db->rollback(); return ['success'=>false,'errors'=>['Release failed']]; }
    }

    public function refundBuyer($id, $by) {
        $e = $this->getById($id);
        if (!$e) return ['success'=>false,'errors'=>['Not found']];
        $this->db->beginTransaction();
        try {
            $bw = $this->db->fetch("SELECT * FROM wallets WHERE user_id=? AND currency=?", [$e['buyer_id'],$e['currency']]);
            $refund = $e['total_amount'];
            if ($bw) $this->db->update('wallets', ['balance'=>$bw['balance']+$refund,'escrow_balance'=>max(0,$bw['escrow_balance']-$e['amount'])], 'id=?', [$bw['id']]);
            $this->db->update('escrows', ['status'=>'refunded','cancelled_at'=>date('Y-m-d H:i:s')], 'id=?', [$id]);
            $this->txn($e['buyer_id'], $id, 'escrow_refund', $refund, $e['currency'], 0, $refund, "Refund for {$e['escrow_id']}");
            $this->notify($e['buyer_id'], 'escrow.refunded', 'Escrow Refunded', "Refunded for {$e['escrow_id']}", "/pages/wallet/index.php");
            $this->db->commit(); return ['success'=>true];
        } catch (Exception $ex) { $this->db->rollback(); return ['success'=>false,'errors'=>['Refund failed']]; }
    }

    public function cancel($id, $uid) {
        $e = $this->getById($id);
        if (!$e) return ['success'=>false,'errors'=>['Not found']];
        if (in_array($e['status'], ['funded','delivered'])) return $this->refundBuyer($id, $uid);
        $this->db->update('escrows', ['status'=>'cancelled','cancelled_at'=>date('Y-m-d H:i:s')], 'id=?', [$id]);
        return ['success'=>true];
    }

    public function getById($id) {
        return $this->db->fetch("SELECT e.*, CONCAT(b.first_name,' ',b.last_name) as buyer_name, b.email as buyer_email, b.avatar as buyer_avatar, b.trust_score as buyer_trust, CONCAT(s.first_name,' ',s.last_name) as seller_name, s.email as seller_email, s.avatar as seller_avatar, s.trust_score as seller_trust, CONCAT(a.first_name,' ',a.last_name) as agent_name FROM escrows e LEFT JOIN users b ON b.id=e.buyer_id LEFT JOIN users s ON s.id=e.seller_id LEFT JOIN users a ON a.id=e.agent_id WHERE e.id=?", [$id]);
    }

    public function list($filters=[], $page=1, $perPage=15) {
        $w=['1=1']; $p=[];
        if (!empty($filters['user_id'])) { $w[]="(e.buyer_id=? OR e.seller_id=?)"; $p[]=$filters['user_id']; $p[]=$filters['user_id']; }
        if (!empty($filters['status'])) { $w[]="e.status=?"; $p[]=$filters['status']; }
        if (!empty($filters['category'])) { $w[]="e.category=?"; $p[]=$filters['category']; }
        if (!empty($filters['search'])) { $w[]="(e.escrow_id LIKE ? OR e.title LIKE ?)"; $s="%{$filters['search']}%"; $p[]=$s; $p[]=$s; }
        if (!empty($filters['agent_id'])) { $w[]="e.agent_id=?"; $p[]=$filters['agent_id']; }
        $ws=implode(' AND ', $w); $off=($page-1)*$perPage;
        $total = $this->db->fetchColumn("SELECT COUNT(*) FROM escrows e WHERE {$ws}", $p);
        $data = $this->db->fetchAll("SELECT e.*, CONCAT(b.first_name,' ',b.last_name) as buyer_name, CONCAT(s.first_name,' ',s.last_name) as seller_name FROM escrows e LEFT JOIN users b ON b.id=e.buyer_id LEFT JOIN users s ON s.id=e.seller_id WHERE {$ws} ORDER BY e.created_at DESC LIMIT {$perPage} OFFSET {$off}", $p);
        return ['data'=>$data, 'total'=>(int)$total, 'page'=>$page, 'per_page'=>$perPage, 'total_pages'=>ceil($total/$perPage)];
    }

    public function calculateFee($amt) {
        $fp = $this->db->fetchColumn("SELECT setting_value FROM settings WHERE setting_key='escrow_fee_percentage'") ?: 2.5;
        return round($amt * ($fp/100), 2);
    }

    public function getUserStats($uid) {
        $s=[]; $db=$this->db;
        $s['total_escrows'] = $db->fetchColumn("SELECT COUNT(*) FROM escrows WHERE buyer_id=? OR seller_id=?", [$uid,$uid]) ?: 0;
        $s['active_escrows'] = $db->fetchColumn("SELECT COUNT(*) FROM escrows WHERE (buyer_id=? OR seller_id=?) AND status IN('funded','in_progress','delivered')", [$uid,$uid]) ?: 0;
        $s['completed_escrows'] = $db->fetchColumn("SELECT COUNT(*) FROM escrows WHERE (buyer_id=? OR seller_id=?) AND status='completed'", [$uid,$uid]) ?: 0;
        $s['total_volume'] = $db->fetchColumn("SELECT COALESCE(SUM(amount),0) FROM escrows WHERE (buyer_id=? OR seller_id=?) AND status='completed'", [$uid,$uid]) ?: 0;
        $s['open_disputes'] = $db->fetchColumn("SELECT COUNT(*) FROM disputes d JOIN escrows e ON d.escrow_id=e.id WHERE (e.buyer_id=? OR e.seller_id=?) AND d.status IN('open','under_review')", [$uid,$uid]) ?: 0;
        $s['pending_withdrawals'] = $db->fetchColumn("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE user_id=? AND status='pending'", [$uid]) ?: 0;
        return $s;
    }

    public function getAdminStats() {
        $s=[]; $db=$this->db;
        $s['total_users'] = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role='user'") ?: 0;
        $s['total_agents'] = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role='agent'") ?: 0;
        $s['total_escrows'] = $db->fetchColumn("SELECT COUNT(*) FROM escrows") ?: 0;
        $s['active_escrows'] = $db->fetchColumn("SELECT COUNT(*) FROM escrows WHERE status IN('funded','in_progress','delivered')") ?: 0;
        $s['completed_escrows'] = $db->fetchColumn("SELECT COUNT(*) FROM escrows WHERE status='completed'") ?: 0;
        $s['total_volume'] = $db->fetchColumn("SELECT COALESCE(SUM(amount),0) FROM escrows WHERE status='completed'") ?: 0;
        $s['platform_revenue'] = $db->fetchColumn("SELECT COALESCE(SUM(escrow_fee),0) FROM escrows WHERE status='completed'") ?: 0;
        $s['open_disputes'] = $db->fetchColumn("SELECT COUNT(*) FROM disputes WHERE status IN('open','under_review')") ?: 0;
        $s['pending_withdrawals'] = $db->fetchColumn("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status='pending'") ?: 0;
        $s['pending_kyc'] = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE kyc_status='pending'") ?: 0;
        return $s;
    }

    public function getMonthlyStats($months=6) {
        return $this->db->fetchAll("SELECT DATE_FORMAT(created_at,'%Y-%m') as month, COUNT(*) as total_escrows, SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed, COALESCE(SUM(CASE WHEN status='completed' THEN amount ELSE 0 END),0) as volume, COALESCE(SUM(CASE WHEN status='completed' THEN escrow_fee ELSE 0 END),0) as revenue FROM escrows WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH) GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY month", [$months]);
    }

    // Helper methods
    private function txn($uid, $eid, $type, $amt, $cur, $fee, $net, $desc) {
        $ref = 'TXN-'.strtoupper(substr(md5(uniqid(mt_rand(),true)),0,10));
        $this->db->insert('transactions', ['uuid'=>$this->uuid(),'transaction_ref'=>$ref,'user_id'=>$uid,'escrow_id'=>$eid,'type'=>$type,'amount'=>$amt,'currency'=>$cur,'fee'=>$fee,'net_amount'=>$net,'status'=>'completed','description'=>$desc,'completed_at'=>date('Y-m-d H:i:s')]);
    }
    private function notify($uid,$type,$title,$msg,$link=null) { $this->db->insert('notifications', ['user_id'=>$uid,'type'=>$type,'title'=>$title,'message'=>$msg,'link'=>$link]); }
    private function log($uid,$action,$et,$eid,$desc) { try{$this->db->insert('activity_logs',['user_id'=>$uid,'action'=>$action,'entity_type'=>$et,'entity_id'=>$eid,'description'=>$desc,'ip_address'=>$_SERVER['REMOTE_ADDR']??null]);}catch(Exception $e){} }
    private function updateTrust($uid) {
        $c = $this->db->fetchColumn("SELECT COUNT(*) FROM escrows WHERE (buyer_id=? OR seller_id=?) AND status='completed'", [$uid,$uid]);
        $d = $this->db->fetchColumn("SELECT COUNT(*) FROM disputes d JOIN escrows e ON d.escrow_id=e.id WHERE d.raised_by!=? AND (e.buyer_id=? OR e.seller_id=?)", [$uid,$uid,$uid]);
        $this->db->update('users', ['trust_score'=>min(100,max(0,($c*5)-($d*15)+10)),'total_transactions'=>$c], 'id=?', [$uid]);
    }
    private function generateContract($id) {
        $e = $this->getById($id); if(!$e) return;
        $c = "ESCROW AGREEMENT - {$e['escrow_id']}\nDate: ".date('F j, Y')."\nBuyer: {$e['buyer_name']}\nSeller: ".($e['seller_name']??'Pending')."\nAmount: {$e['currency']} ".number_format($e['amount'],2)."\nFee: {$e['currency']} ".number_format($e['escrow_fee'],2)."\n\n{$e['description']}\n\nTerms: {$e['terms']}\nInspection: {$e['inspection_period_days']} days";
        $h = hash('sha256', $c.$id.time());
        $this->db->insert('escrow_contracts', ['escrow_id'=>$id,'content'=>$c,'hash'=>$h]);
        $this->db->update('escrows', ['contract_hash'=>$h], 'id=?', [$id]);
    }
    private function uuid() { $d=random_bytes(16); $d[6]=chr(ord($d[6])&0x0f|0x40); $d[8]=chr(ord($d[8])&0x3f|0x80); return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4)); }
}
