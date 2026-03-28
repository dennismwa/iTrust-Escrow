<?php
require_once __DIR__.'/../includes/init.php';
header('Content-Type: application/json');
if(!Auth::check()){echo json_encode(['success'=>false]);exit;}
$db=Database::getInstance();$uid=$_SESSION['user_id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    Auth::verifyCSRF();
    $cid=intval($_POST['conversation_id']??0);$content=trim($_POST['content']??'');
    if(!$cid||!$content){echo json_encode(['success'=>false]);exit;}
    $p=$db->fetch("SELECT * FROM conversation_participants WHERE conversation_id=? AND user_id=?",[$cid,$uid]);
    if(!$p){echo json_encode(['success'=>false]);exit;}
    $mid=$db->insert('messages',['conversation_id'=>$cid,'sender_id'=>$uid,'content'=>$content,'type'=>'text']);
    echo json_encode(['success'=>true,'message_id'=>$mid]);
}elseif($_SERVER['REQUEST_METHOD']==='GET'){
    $cid=intval($_GET['conversation_id']??0);
    $msgs=$db->fetchAll("SELECT m.*,CONCAT(u.first_name,' ',u.last_name) as sender_name FROM messages m JOIN users u ON u.id=m.sender_id WHERE m.conversation_id=? ORDER BY m.created_at ASC",[$cid]);
    echo json_encode(['success'=>true,'data'=>$msgs]);
}
