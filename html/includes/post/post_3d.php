<?php
if ($_POST["tags"] !== ""){
    $f3->set('query',$_POST["tags"]);
}else{
    $f3->set('query','');
}
?>