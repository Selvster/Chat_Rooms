<?php
require '../vendor/autoload.php';
if(isset($_POST['username'])){
    $Username = filter_var($_POST['username'],FILTER_SANITIZE_STRING);
    $rows = MyApp\Db::getInst()->get_count('users',array('username'=>$Username));
    if($rows > 0){
        echo "Username is Taken";
    }else{
        if(!preg_match('#\s#',$Username)){
            echo "valid";
        }else{
            echo "No Spaces is allowed";
        }
    }
}