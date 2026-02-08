<?php
require_once 'config/database.php';
$db = new Database();

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = intval($_GET['id']);
$result = $db->conn->query("SELECT * FROM products WHERE id = $product_id");
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: products.php");
    exit();
}

// Fetch all images for this product
$images_result = $db->conn->query("SELECT * FROM product_images WHERE product_id = $product_id ORDER BY is_primary DESC, display_order ASC");
$product_images = [];
while ($img = $images_result->fetch_assoc()) {
    $product_images[] = $img;
}

// If no images in product_images table, use the old image_url from products table
if (empty($product_images) && !empty($product['image_url'])) {
    $product_images[] = [
        'image_url' => $product['image_url'],
        'is_primary' => true
    ];
}
?>

<?php include 'includes/header.php'; ?>

<style>
.product-gallery {
    position: relative;
}

.main-image-container {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    background: #f8f9fa;
    aspect-ratio: 1/1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.main-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    cursor: zoom-in;
}

.thumbnail-container {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    overflow-x: auto;
    padding: 5px 0;
}

.thumbnail {
    width: 80px;
    height: 80px;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    object-fit: cover;
    flex-shrink: 0;
}

.thumbnail:hover {
    border-color: #0d6efd;
    transform: scale(1.05);
}

.thumbnail.active {
    border-color: #0d6efd;
    box-shadow: 0 0 10px rgba(13, 110, 253, 0.3);
}

.image-count-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 14px;
}

.zoom-icon {
    position: absolute;
    bottom: 15px;
    right: 15px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 8px 12px;
    border-radius: 5px;
    font-size: 12px;
    pointer-events: none;
}
</style>

<div class="container my-5">
    <div class="row">
        <!-- Product Images Section -->
        <div class="col-md-6">
            <div class="product-gallery">
                <div class="main-image-container">
                    <?php if (!empty($product_images)): ?>
                        <img id="mainImage" 
                             src="<?php echo $product_images[0]['image_url']; ?>" 
                             class="main-image" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                        
                        <?php if (count($product_images) > 1): ?>
                        <span class="image-count-badge">
                            <i class="fas fa-images"></i> <?php echo count($product_images); ?> Photos
                        </span>
                        <?php endif; ?>
                        
                        <span class="zoom-icon">
                            <i class="fas fa-search-plus"></i> Click to zoom
                        </span>
                    <?php else: ?>
                        <img src="https://via.placeholder.com/500x500?text=No+Image" 
                             class="main-image" 
                             alt="No image available">
                    <?php endif; ?>
                </div>

                <!-- Thumbnails -->
                <?php if (count($product_images) > 1): ?>
                <div class="thumbnail-container">
                    <?php foreach ($product_images as $index => $img): ?>
                        <img src="<?php echo $img['image_url']; ?>" 
                             class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                             alt="Thumbnail <?php echo $index + 1; ?>"
                             onclick="changeImage('<?php echo $img['image_url']; ?>', this)">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Details Section -->
        <div class="col-md-6">
            <h2 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h2>
            <span class="badge bg-secondary mb-3"><?php echo htmlspecialchars($product['category']); ?></span>
            
            <div class="mb-3">
                <h3 class="text-primary mb-0">$<?php echo number_format($product['price'], 2); ?></h3>
            </div>

            <p class="lead"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            
            <div class="mb-4">
                <p class="text-muted mb-2">
                    <i class="fas fa-box"></i> 
                    <strong>Stock:</strong> 
                    <?php if ($product['stock'] > 0): ?>
                        <span class="text-success"><?php echo $product['stock']; ?> items available</span>
                    <?php else: ?>
                        <span class="text-danger">Out of stock</span>
                    <?php endif; ?>
                </p>
                <p class="text-muted">
                    <i class="fas fa-truck"></i> 
                    <strong>Delivery:</strong> Free shipping on orders over $50
                </p>
            </div>
            
            <?php if (isLoggedIn()): ?>
            <div class="d-grid gap-2">
                <?php if ($product['stock'] > 0): ?>
                <form method="POST" action="add_to_cart.php" class="mb-2">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <div class="input-group mb-3">
                        <span class="input-group-text">Quantity</span>
                        <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?php echo $product['stock']; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                </form>
                <?php else: ?>
                <button class="btn btn-secondary btn-lg w-100" disabled>
                    <i class="fas fa-times"></i> Out of Stock
                </button>
                <?php endif; ?>
                
                <form method="POST" action="add_to_wishlist.php">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <button type="submit" class="btn btn-outline-danger btn-lg w-100">
                        <i class="fas fa-heart"></i> Add to Wishlist
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Please <a href="login.php" class="alert-link">login</a> to add items to cart or wishlist.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Additional Product Information -->
    <div class="row mt-5">
        <div class="col-12">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#description">Description</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#specifications">Specifications</a>
                </li>
            </ul>
            
            <div class="tab-content p-4 border border-top-0">
                <div id="description" class="tab-pane fade show active">
                    <h5>Product Description</h5>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                <div id="specifications" class="tab-pane fade">
                    <h5>Specifications</h5>
                    <table class="table">
                        <tr>
                            <th>Category</th>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                        </tr>
                        <tr>
                            <th>Product ID</th>
                            <td>#<?php echo $product['id']; ?></td>
                        </tr>
                        <tr>
                            <th>Stock Status</th>
                            <td><?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?></td>
                        </tr>
                        <tr>
                            <th>Added Date</th>
                            <td><?php echo date('F d, Y', strtotime($product['created_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Change main image when thumbnail is clicked
function changeImage(imageUrl, element) {
    const mainImage = document.getElementById('mainImage');
    mainImage.src = imageUrl;
    
    // Remove active class from all thumbnails
    document.querySelectorAll('.thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
    });
    
    // Add active class to clicked thumbnail
    element.classList.add('active');
}

// Image zoom functionality
document.addEventListener('DOMContentLoaded', function() {
    const mainImage = document.getElementById('mainImage');
    if (mainImage) {
        mainImage.addEventListener('click', function() {
            // Create modal for zoomed image
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                cursor: zoom-out;
            `;
            
            const zoomedImg = document.createElement('img');
            zoomedImg.src = this.src;
            zoomedImg.style.cssText = `
                max-width: 90%;
                max-height: 90%;
                object-fit: contain;
            `;
            
            modal.appendChild(zoomedImg);
            document.body.appendChild(modal);
            
            modal.addEventListener('click', function() {
                document.body.removeChild(modal);
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>