<?php
include_once("../config.php");
require_once("../auth.php");
require_admin();

if(isset($_GET['id'])){
    $id = (int)$_GET['id'];

    // 1. Find product image filename
    $stmt = $connection->prepare("SELECT `product_image` FROM `product` WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if($row = $res->fetch_assoc()){
        $image_name = $row['product_image'];
        if(!empty($image_name)){
            $file_path = __DIR__ . "/../product_uploads/" . $image_name;
            if(file_exists($file_path) && is_file($file_path)){
                @unlink($file_path);
            }
        }
    }
    $stmt->close();

    // 2. Delete product record from database
    $delete = $connection->prepare("DELETE FROM `product` WHERE id = ?");
    $delete->bind_param("i", $id);
    $delete->execute();
    $delete->close();
}

header("Location: product.php");
exit();
?>