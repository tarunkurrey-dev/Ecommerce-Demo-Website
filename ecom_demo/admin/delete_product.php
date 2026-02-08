<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireAdminLogin();

if(isset($_GET['id'])) {

    $id = intval($_GET['id']);

    // Delete product images first
    $conn->query("DELETE FROM product_images WHERE product_id = $id");

    // Delete product
    $conn->query("DELETE FROM products WHERE id = $id");

    header("Location: products.php?msg=deleted");
    exit();
}

header("Location: products.php");
exit();
?>
