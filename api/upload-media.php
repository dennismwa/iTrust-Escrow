<?php
require_once __DIR__.'/../includes/init.php';
header('Content-Type: application/json');
if(!Auth::isAdmin()){echo json_encode(['success'=>false,'error'=>'Unauthorized']);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){echo json_encode(['success'=>false]);exit;}
Auth::verifyCSRF();

$db=Database::getInstance();
$type=$_POST['type']??'media'; // media, setting, logo, favicon, pwa
$key=$_POST['key']??'';

if(!isset($_FILES['file'])||$_FILES['file']['error']!==UPLOAD_ERR_OK){
    echo json_encode(['success'=>false,'error'=>'No file']);exit;
}
$f=$_FILES['file'];
$ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
$allowed=['jpg','jpeg','png','gif','webp','svg','ico'];
if(!in_array($ext,$allowed)){echo json_encode(['success'=>false,'error'=>'Invalid type']);exit;}
if($f['size']>MAX_FILE_SIZE){echo json_encode(['success'=>false,'error'=>'Too large']);exit;}

$fn=$type.'_'.($key?$key.'_':'').time().'.'.$ext;
$path='/uploads/site/'.$fn;
if(!move_uploaded_file($f['tmp_name'],APP_ROOT.$path)){
    echo json_encode(['success'=>false,'error'=>'Move failed']);exit;
}

if($type==='media'&&$key){
    $db->update('site_media',['file_path'=>$path],'media_key=?',[$key]);
}elseif($type==='setting'&&$key){
    Settings::set($key,$path);Settings::clearCache();
}
echo json_encode(['success'=>true,'path'=>$path,'url'=>APP_URL.$path]);
