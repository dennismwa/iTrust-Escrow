<?php
require_once __DIR__.'/../includes/init.php';
if(!Auth::check())redirect(APP_URL.'/pages/auth/login.php');
$db=Database::getInstance();$uid=$_SESSION['user_id'];
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_FILES['attachment'])){
    Auth::verifyCSRF();$eid=intval($_POST['escrow_id']??0);$f=$_FILES['attachment'];
    if($f['error']===UPLOAD_ERR_OK&&$f['size']<=MAX_FILE_SIZE){
        $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
        if(in_array($ext,['pdf','jpg','jpeg','png','gif','doc','docx','txt','zip'])){
            $fn='att_'.$eid.'_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
            if(move_uploaded_file($f['tmp_name'],APP_ROOT.'/uploads/attachments/'.$fn)){
                $db->insert('escrow_attachments',['escrow_id'=>$eid,'user_id'=>$uid,'file_path'=>'/uploads/attachments/'.$fn,'file_name'=>$f['name'],'file_type'=>$f['type'],'file_size'=>$f['size'],'type'=>'other']);
                setFlash('success','File uploaded');
            }else setFlash('error','Upload failed');
        }else setFlash('error','File type not allowed');
    }else setFlash('error','File error');
    redirect(APP_URL.'/pages/escrow/view.php?id='.$eid);
}
