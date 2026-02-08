<?php
require_once 'config/database.php';
$db = new Database();

// Get active banners
$banners = $db->conn->query("
    SELECT * FROM banners 
    WHERE is_active = 1 
    ORDER BY created_at DESC
");

// Get featured products
$featured_products = $db->conn->query("
    SELECT 
        p.id,
        p.name,
        p.description,
        p.price,
        p.stock,
        p.category,
        (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image_url
    FROM products p
    WHERE p.stock > 0
    ORDER BY p.created_at DESC
    LIMIT 8
");
?>

<?php include 'includes/header.php'; ?>

<!-- Banner Slider Section -->
<?php if ($banners->num_rows > 0): ?>
<div id="bannerCarousel" class="carousel slide mb-5" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <?php 
        $banners->data_seek(0);
        $indicator_index = 0;
        while ($banner = $banners->fetch_assoc()): 
        ?>
            <button type="button" 
                    data-bs-target="#bannerCarousel" 
                    data-bs-slide-to="<?php echo $indicator_index; ?>" 
                    class="<?php echo $indicator_index === 0 ? 'active' : ''; ?>" 
                    aria-current="<?php echo $indicator_index === 0 ? 'true' : 'false'; ?>" 
                    aria-label="Slide <?php echo $indicator_index + 1; ?>">
            </button>
        <?php 
            $indicator_index++;
        endwhile; 
        ?>
    </div>

    <div class="carousel-inner">
        <?php 
        $banners->data_seek(0);
        $slide_index = 0;
        while ($banner = $banners->fetch_assoc()): 
        ?>
            <div class="carousel-item <?php echo $slide_index === 0 ? 'active' : ''; ?>">
                <img src="<?php echo htmlspecialchars($banner['image_url']); ?>" 
                     class="d-block w-100 banner-image" 
                     alt="Banner <?php echo $slide_index + 1; ?>">
            </div>
        <?php 
            $slide_index++;
        endwhile; 
        ?>
    </div>

    <?php if ($banners->num_rows > 1): ?>
    <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Categories Section -->
<div class="container mb-5">
    <div class="section-header text-center mb-5">
        <h2 class="display-5 fw-bold mb-3">Shop by Category</h2>
        <p class="text-muted">Browse our wide range of product categories</p>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-6 mb-4">
            <a href="products.php?category=Electronics" class="text-decoration-none">
                <div class="card category-card text-center p-4 h-100 shadow-sm">
                    <div class="category-icon mb-3">
                        <i class="fas fa-laptop fa-4x text-primary"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Electronics</h5>
                    <p class="text-muted small mb-0">Latest gadgets & tech</p>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <a href="products.php?category=Clothing" class="text-decoration-none">
                <div class="card category-card text-center p-4 h-100 shadow-sm">
                    <div class="category-icon mb-3">
                        <i class="fas fa-tshirt fa-4x text-success"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Clothing</h5>
                    <p class="text-muted small mb-0">Fashion & apparel</p>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <a href="products.php?category=Footwear" class="text-decoration-none">
                <div class="card category-card text-center p-4 h-100 shadow-sm">
                    <div class="category-icon mb-3">
                        <i class="fas fa-shoe-prints fa-4x text-warning"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Footwear</h5>
                    <p class="text-muted small mb-0">Shoes & sneakers</p>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <a href="products.php?category=Home" class="text-decoration-none">
                <div class="card category-card text-center p-4 h-100 shadow-sm">
                    <div class="category-icon mb-3">
                        <i class="fas fa-home fa-4x text-info"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Home</h5>
                    <p class="text-muted small mb-0">Decor & essentials</p>
                </div>
            </a>
        </div>
    </div>
</div>


<!-- Featured Products Section -->
<div class="container mb-5">
    <div class="section-header text-center mb-5">
        <h2 class="display-5 fw-bold mb-3">Featured Products</h2>
        <p class="text-muted">Discover our handpicked selection of premium products</p>
    </div>

    <div class="row">
        <?php while ($product = $featured_products->fetch_assoc()): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card product-card h-100 shadow-sm">
                    <div class="product-image-wrapper">
                        <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'assets/images/no-image.png'); ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                        
                        <!-- Quick Actions Overlay -->
                        <div class="product-overlay">
                            <a href="product_detail.php?id=<?php echo $product['id']; ?>" 
                               class="btn btn-light btn-sm mb-2">
                                <i class="fas fa-eye me-1"></i> Quick View
                            </a>
                        </div>

                        <!-- Stock Badge -->
                        <?php if ($product['stock'] < 10): ?>
                            <span class="badge bg-warning position-absolute top-0 end-0 m-2">
                                Only <?php echo $product['stock']; ?> left
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="card-body d-flex flex-column">
                        <span class="badge bg-secondary mb-2 align-self-start">
                            <?php echo htmlspecialchars($product['category']); ?>
                        </span>
                        
                        <h5 class="card-title">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h5>
                        
                        <p class="card-text text-muted small">
                            <?php 
                            $description = htmlspecialchars($product['description']);
                            echo strlen($description) > 60 ? substr($description, 0, 60) . '...' : $description; 
                            ?>
                        </p>
                        
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="text-primary mb-0">
                                    $<?php echo number_format($product['price'], 2); ?>
                                </h4>
                                <div class="rating">
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="far fa-star text-warning"></i>
                                </div>
                            </div>
                            
                            <a href="product_detail.php?id=<?php echo $product['id']; ?>" 
                               class="btn btn-outline-primary w-100">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <div class="text-center mt-4">
        <a href="products.php" class="btn btn-primary btn-lg px-5">
            View All Products <i class="fas fa-arrow-right ms-2"></i>
        </a>
    </div>
</div>

<!-- Features Section -->
<div class="bg-light py-5 mb-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 mb-4 mb-md-0">
                <div class="feature-box">
                    <i class="fas fa-shipping-fast fa-3x text-primary mb-3"></i>
                    <h5 class="fw-bold">Free Shipping</h5>
                    <p class="text-muted small mb-0">On orders over $50</p>
                </div>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <div class="feature-box">
                    <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                    <h5 class="fw-bold">Secure Payment</h5>
                    <p class="text-muted small mb-0">100% secure transactions</p>
                </div>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <div class="feature-box">
                    <i class="fas fa-undo fa-3x text-warning mb-3"></i>
                    <h5 class="fw-bold">Easy Returns</h5>
                    <p class="text-muted small mb-0">30-day return policy</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="feature-box">
                    <i class="fas fa-headset fa-3x text-info mb-3"></i>
                    <h5 class="fw-bold">24/7 Support</h5>
                    <p class="text-muted small mb-0">Dedicated customer care</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Banner Slider Styles */
.banner-image {
    height: 450px;
    object-fit: cover;
}

.carousel-control-prev-icon,
.carousel-control-next-icon {
    width: 3rem;
    height: 3rem;
    background-color: rgba(0,0,0,0.5);
    border-radius: 50%;
}

.carousel-indicators button {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin: 0 5px;
}

/* Product Card Styles */
.product-card {
    transition: all 0.3s ease;
    border: 1px solid #e0e0e0;
    overflow: hidden;
}

.product-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
}

.product-image-wrapper {
    position: relative;
    overflow: hidden;
    height: 250px;
}

.product-image-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-card:hover .product-image-wrapper img {
    transform: scale(1.1);
}

.product-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.product-card:hover .product-overlay {
    opacity: 1;
}

.card-title {
    font-size: 1rem;
    font-weight: 600;
    min-height: 2.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Category Card Styles */
.category-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
    cursor: pointer;
}

.category-card:hover {
    transform: translateY(-5px);
    border-color: #007bff;
    box-shadow: 0 5px 20px rgba(0,123,255,0.2) !important;
}

.category-icon {
    transition: transform 0.3s ease;
}

.category-card:hover .category-icon {
    transform: scale(1.1);
}

/* Section Header */
.section-header h2 {
    position: relative;
    display: inline-block;
}

.section-header h2::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: #007bff;
}

/* Responsive */
@media (max-width: 768px) {
    .banner-image {
        height: 300px;
    }
}

@media (max-width: 576px) {
    .banner-image {
        height: 250px;
    }
}
</style>

<script>
// Auto-slide carousel every 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    var carouselElement = document.getElementById('bannerCarousel');
    if (carouselElement) {
        var carousel = new bootstrap.Carousel(carouselElement, {
            interval: 5000, // 5 seconds
            wrap: true,
            keyboard: true,
            pause: 'hover'
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>