<?php
include_once("../config.php");
require_once("../auth.php");
require_admin();

if(isset($_GET['id'])){
    $id = (int)$_GET['id'];

    $delete = $connection->prepare("DELETE FROM `product` WHERE id = ?");
    $delete->bind_param("i", $id);

    if($delete->execute()){
        header("Location: product.php");
        exit();
    }
}
header("Location: product.php");
exit();
?>