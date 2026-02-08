<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

$db = new Database();
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search   = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT p.*, pi.image_url 
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE 1";
$params = [];
$types = "";

if (!empty($category)) {
    $sql .= " AND p.category = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$stmt = $db->conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - E-Commerce</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-card {
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>Products <?php echo $category ? " - " . htmlspecialchars($category) : ''; ?></h2>
                <small class="text-muted">Total products found: <?php echo $result->num_rows; ?></small>
            </div>
            <div class="col-md-4">
                <form method="GET" action="products.php" class="d-flex">
                    <input type="text" 
                           name="search" 
                           class="form-control me-2" 
                           placeholder="Search products..."
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-outline-primary">Search</button>
                </form>
            </div>
        </div>

        <div class="row">
        <?php if ($result->num_rows > 0): ?>
            <?php 
            $count = 0;
            while ($product = $result->fetch_assoc()): 
                $count++;
            ?>
                <div class="col-md-4 mb-4">
                    <div class="card product-card h-100">
                        <!-- Product Image -->
                        <img src="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'https://via.placeholder.com/400x300?text=No+Image'; ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             style="height: 250px; object-fit: cover;"
                             onerror="this.src='https://via.placeholder.com/400x300?text=Image+Not+Found';">

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h5>

                            <span class="badge bg-secondary mb-2 align-self-start">
                                <?php echo htmlspecialchars($product['category']); ?>
                            </span>

                            <p class="card-text">
                                <?php 
                                $desc = htmlspecialchars($product['description']);
                                echo strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc; 
                                ?>
                            </p>

                            <p class="card-text">
                                <strong class="text-success">$<?php echo number_format($product['price'], 2); ?></strong>
                            </p>

                            <p class="card-text text-muted small">
                                <i class="fas fa-box"></i> Stock: <?php echo htmlspecialchars($product['stock']); ?> available
                            </p>

                            <div class="mt-auto">
                                <div class="d-grid gap-2">
                                    <a href="product_detail.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>

                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <?php if ($product['stock'] > 0): ?>
                                            <form method="POST" action="add_to_cart.php">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-secondary w-100" disabled>
                                                <i class="fas fa-times"></i> Out of Stock
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-sign-in-alt"></i> Login to Add to Cart
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php 
                // Add debug info
                echo "<!-- Product #$count: ID=" . $product['id'] . ", Name=" . $product['name'] . " -->\n";
                ?>
                
            <?php endwhile; ?>
            
            <?php 
            echo "<!-- Total products rendered: $count -->\n";
            ?>
            
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <h4><i class="fas fa-info-circle"></i> No products found</h4>
                    <p>Category: <?php echo $category ? htmlspecialchars($category) : 'All'; ?></p>
                    <p>Search: <?php echo $search ? htmlspecialchars($search) : 'None'; ?></p>
                    <?php if (!empty($search) || !empty($category)): ?>
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-redo"></i> View All Products
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        </div>

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>