<?php
require_once 'config/database.php';
$db = new Database();

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle remove from cart
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    $db->conn->query("DELETE FROM cart WHERE id = $remove_id AND user_id = $user_id");
}

// Handle quantity update
if (isset($_POST['update_quantity'])) {
    $cart_id = intval($_POST['cart_id']);
    $quantity = intval($_POST['quantity']);
    if ($quantity > 0) {
        $db->conn->query("UPDATE cart SET quantity = $quantity WHERE id = $cart_id AND user_id = $user_id");
    }
}

$cart_items = $db->conn->query("
    SELECT 
        c.*, 
        p.name, 
        p.price, 
        p.stock,
        (SELECT image_url 
         FROM product_images 
         WHERE product_id = p.id 
         LIMIT 1) AS image_url
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = $user_id
");


$total = 0;
?>

<?php include 'includes/header.php'; ?>

<h2>Shopping Cart</h2>

<?php if ($cart_items->num_rows > 0): ?>
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <?php while ($item = $cart_items->fetch_assoc()): 
                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal;
                ?>
                <div class="row mb-3 border-bottom pb-3">
                    <div class="col-md-2">
                        <img src="<?php echo $item['image_url']; ?>" class="img-fluid" alt="<?php echo $item['name']; ?>">
                    </div>
                    <div class="col-md-6">
                        <h5><?php echo $item['name']; ?></h5>
                        <p class="text-muted">Price: $<?php echo $item['price']; ?></p>
                    </div>
                    <div class="col-md-2">
                        <form method="POST" class="d-flex align-items-center">
                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                   min="1" max="<?php echo $item['stock']; ?>" class="form-control form-control-sm">
                            <button type="submit" name="update_quantity" class="btn btn-sm btn-outline-primary ms-2">Update</button>
                        </form>
                    </div>
                    <div class="col-md-2">
                        <h5>$<?php echo number_format($subtotal, 2); ?></h5>
                        <a href="?remove=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger">Remove</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Order Summary</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>Shipping:</span>
                    <span>$10.00</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <strong>Total:</strong>
                    <strong>$<?php echo number_format($total + 10, 2); ?></strong>
                </div>
                <a href="checkout.php" class="btn btn-primary w-100">Proceed to Checkout</a>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">
    Your cart is empty. <a href="products.php">Start shopping</a>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>