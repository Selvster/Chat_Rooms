<?php 
require '../vendor/autoload.php';
if(isset($_POST['username'])){
    $Username = filter_var($_POST['username'],FILTER_SANITIZE_STRING);
    $color = MyApp\Db::getInst()->select(array('color'),'users',array('username'=>$Username))->color;
    echo $color;
}