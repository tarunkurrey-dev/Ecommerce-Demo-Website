<?php
require_once 'config/database.php';
$db = new Database();

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle remove from wishlist
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    $stmt = $db->conn->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $remove_id, $user_id);
    $stmt->execute();
    header("Location: wishlist.php?msg=removed");
    exit();
}

// Handle add to cart from wishlist
if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $wishlist_id = intval($_POST['wishlist_id']);
    
    // Check if product already in cart
    $checkStmt = $db->conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $checkStmt->bind_param("ii", $user_id, $product_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        // Update quantity if already in cart
        $cartItem = $checkResult->fetch_assoc();
        $newQuantity = $cartItem['quantity'] + 1;
        $updateStmt = $db->conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $updateStmt->bind_param("ii", $newQuantity, $cartItem['id']);
        $updateStmt->execute();
    } else {
        // Add new item to cart
        $insertStmt = $db->conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
        $insertStmt->bind_param("ii", $user_id, $product_id);
        $insertStmt->execute();
    }
    
    // Remove from wishlist after adding to cart
    $removeStmt = $db->conn->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
    $removeStmt->bind_param("ii", $wishlist_id, $user_id);
    $removeStmt->execute();
    
    header("Location: wishlist.php?msg=added_to_cart");
    exit();
}

// Get wishlist items (CORRECTED: using wishlist table, not cart table)
$wishlist_items = $db->conn->query("
    SELECT 
        w.id as wishlist_id,
        w.added_at,
        p.id as product_id,
        p.name, 
        p.description,
        p.price, 
        p.stock,
        p.category,
        (SELECT image_url 
         FROM product_images 
         WHERE product_id = p.id AND is_primary = 1
         LIMIT 1) AS image_url
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = $user_id
    ORDER BY w.added_at DESC
");

?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-heart text-danger me-2"></i> My Wishlist</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="products.php" class="btn btn-outline-primary">
                <i class="fas fa-shopping-bag me-2"></i> Continue Shopping
            </a>
        </div>
    </div>

    <!-- Success Messages -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php
            switch ($_GET['msg']) {
                case 'added_to_cart':
                    echo 'Item moved to cart successfully!';
                    break;
                case 'removed':
                    echo 'Item removed from wishlist!';
                    break;
                case 'added':
                    echo 'Item added to wishlist!';
                    break;
                default:
                    echo 'Action completed successfully!';
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($wishlist_items->num_rows > 0): ?>
        <div class="row">
            <?php while ($item = $wishlist_items->fetch_assoc()): ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card product-card h-100 shadow-sm">
                        <div class="position-relative">
                            <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'assets/images/no-image.png'); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 style="height: 250px; object-fit: cover;">
                            
                            <!-- Stock Badge -->
                            <?php if ($item['stock'] <= 0): ?>
                                <span class="position-absolute top-0 end-0 badge bg-danger m-2">
                                    Out of Stock
                                </span>
                            <?php elseif ($item['stock'] < 10): ?>
                                <span class="position-absolute top-0 end-0 badge bg-warning m-2">
                                    Low Stock
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body d-flex flex-column">
                            <!-- Category Badge -->
                            <span class="badge bg-secondary mb-2 align-self-start">
                                <?php echo htmlspecialchars($item['category']); ?>
                            </span>

                            <h5 class="card-title">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h5>

                            <p class="card-text text-muted small">
                                <?php 
                                $description = htmlspecialchars($item['description']);
                                echo strlen($description) > 80 ? substr($description, 0, 80) . '...' : $description; 
                                ?>
                            </p>

                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="text-primary mb-0">
                                        $<?php echo number_format($item['price'], 2); ?>
                                    </h4>
                                    <small class="text-muted">
                                        <i class="fas fa-box me-1"></i>
                                        Stock: <?php echo $item['stock']; ?>
                                    </small>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-grid gap-2">
                                    <?php if ($item['stock'] > 0): ?>
                                        <form method="POST" action="wishlist.php" class="d-inline">
                                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                            <input type="hidden" name="wishlist_id" value="<?php echo $item['wishlist_id']; ?>">
                                            <button type="submit" name="add_to_cart" class="btn btn-primary w-100">
                                                <i class="fas fa-cart-plus me-2"></i> Add to Cart
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary w-100" disabled>
                                            <i class="fas fa-times-circle me-2"></i> Out of Stock
                                        </button>
                                    <?php endif; ?>

                                    <div class="btn-group w-100" role="group">
                                        <a href="product_detail.php?id=<?php echo $item['product_id']; ?>" 
                                           class="btn btn-outline-info">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                        <a href="?remove=<?php echo $item['wishlist_id']; ?>" 
                                           class="btn btn-outline-danger"
                                           onclick="return confirm('Remove this item from wishlist?');">
                                            <i class="fas fa-trash me-1"></i> Remove
                                        </a>
                                    </div>
                                </div>

                                <small class="text-muted d-block mt-2 text-center">
                                    <i class="fas fa-clock me-1"></i>
                                    Added <?php echo date('M d, Y', strtotime($item['added_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Wishlist Summary -->
        <div class="card mt-4 bg-light">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-0">
                            <i class="fas fa-heart text-danger me-2"></i>
                            You have <?php echo $wishlist_items->num_rows; ?> item(s) in your wishlist
                        </h5>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="cart.php" class="btn btn-success">
                            <i class="fas fa-shopping-cart me-2"></i> View Cart
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-heart-broken fa-5x text-muted mb-4"></i>
            <h3 class="text-muted mb-3">Your Wishlist is Empty</h3>
            <p class="text-muted mb-4">
                Save your favorite products here and add them to cart when you're ready!
            </p>
            <a href="products.php" class="btn btn-primary btn-lg">
                <i class="fas fa-shopping-bag me-2"></i> Browse Products
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
.product-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #e0e0e0;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
}

.product-card img {
    transition: transform 0.3s;
}

.product-card:hover img {
    transform: scale(1.05);
}

.card-title {
    font-size: 1.1rem;
    font-weight: 600;
    min-height: 2.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.btn-group .btn {
    flex: 1;
}
</style>

<?php include 'includes/footer.php'; ?>