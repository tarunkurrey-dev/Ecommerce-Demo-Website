<?php
require_once 'config/database.php';
$db = new Database();

if (!isLoggedIn() || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$order_id = intval($_GET['id']);
?>

<?php include 'includes/header.php'; ?>

<div class="text-center py-5">
    <div class="mb-4">
        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
    </div>
    <h2 class="mb-3">Order Placed Successfully!</h2>
    <p class="lead mb-4">Thank you for your order. Your order ID is: <strong>#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></strong></p>
    <p class="text-muted mb-4">You will receive a confirmation email shortly.</p>
    <div class="mt-4">
        <a href="orders.php" class="btn btn-primary btn-lg me-2">View My Orders</a>
        <a href="products.php" class="btn btn-outline-primary btn-lg">Continue Shopping</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>