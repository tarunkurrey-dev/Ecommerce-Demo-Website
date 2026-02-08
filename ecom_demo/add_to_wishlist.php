<?php
require_once 'config/database.php';
$db = new Database();

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['product_id'])) {
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    
    // Check if already in wishlist
    $result = $db->conn->query("SELECT * FROM wishlist WHERE user_id = $user_id AND product_id = $product_id");
    
    if ($result->num_rows == 0) {
        $db->conn->query("INSERT INTO wishlist (user_id, product_id) VALUES ($user_id, $product_id)");
    }
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'wishlist.php'));
exit();
?>