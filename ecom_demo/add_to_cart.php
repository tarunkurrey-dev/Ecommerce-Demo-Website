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
    
    // Check if product already in cart
    $result = $db->conn->query("SELECT * FROM cart WHERE user_id = $user_id AND product_id = $product_id");
    
    if ($result->num_rows > 0) {
        // Update quantity
        $db->conn->query("UPDATE cart SET quantity = quantity + 1 WHERE user_id = $user_id AND product_id = $product_id");
    } else {
        // Add to cart
        $db->conn->query("INSERT INTO cart (user_id, product_id) VALUES ($user_id, $product_id)");
    }
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'cart.php'));
exit();
?>