<?php 
require '../vendor/autoload.php';
if(isset($_POST['username'])){
    $Username = filter_var($_POST['username'],FILTER_SANITIZE_STRING);
    //Get Room Type and Id
    $Room_Type = MyApp\Db::getInst()->select(array('entered'),'users',array('username'=>$Username))->entered;
    $Room_id = MyApp\Db::getInst()->select(array('room_id'),'users_rooms',array('username'=>$Username))->room_id;
    //Adjust Type 
    if($Room_Type == "Duo"){
        $Type = 1;
    }else{
        $Type = 2;
    }
    //Get Messages
    $Messages = MyApp\Db::getInst()->select(array('text','message_type','sender_username','color','time'),'messages',array('room_type'=>$Type,'room_id'=>$Room_id));
    echo json_encode($Messages);
}