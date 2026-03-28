<?php
require_once __DIR__.'/../includes/init.php';
header('Content-Type: application/json');
if(!Auth::check()){echo json_encode(['success'=>false]);exit;}
$db=Database::getInstance();$uid=$_SESSION['user_id'];
if($_SERVER['REQUEST_METHOD']==='GET'){
    $n=$db->fetchAll("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT ?",[$uid,min(20,intval($_GET['limit']??10))]);
    echo json_encode(['success'=>true,'data'=>$n]);
}elseif($_SERVER['REQUEST_METHOD']==='POST'){
    $db->update('notifications',['is_read'=>1],'user_id=? AND is_read=0',[$uid]);
    echo json_encode(['success'=>true]);
}
