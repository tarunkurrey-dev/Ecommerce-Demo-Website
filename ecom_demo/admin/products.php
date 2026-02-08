<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireAdminLogin();

$title = "Product Management";

// Fetch all products
$sql = "SELECT p.*, 
        (SELECT image_url FROM product_images 
         WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM products p 
        ORDER BY p.created_at DESC";

$products = $conn->query($sql);

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="fas fa-box me-2"></i> Product Management</h4>
    <a href="product_add.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i> Add New Product
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        switch($_GET['msg']) {
            case 'deleted': echo 'Product deleted successfully!'; break;
            case 'updated': echo 'Product updated successfully!'; break;
            case 'added': echo 'Product added successfully!'; break;
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="productsTable">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>

                <?php if ($products->num_rows > 0): ?>
                    <?php while($product = $products->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="../<?= $product['primary_image'] ?? 'https://via.placeholder.com/50'; ?>" 
                                     style="width:50px;height:50px;object-fit:cover;border-radius:5px;">
                            </td>

                            <td>
                                <strong><?= htmlspecialchars($product['name']); ?></strong><br>
                                <small class="text-muted">
                                    <?= substr($product['description'],0,50); ?>...
                                </small>
                            </td>

                            <td>
                                <span class="badge bg-info">
                                    <?= htmlspecialchars($product['category']); ?>
                                </span>
                            </td>

                            <td>$<?= number_format($product['price'],2); ?></td>

                            <td><?= $product['stock']; ?></td>

                            <td><?= date('d M, Y', strtotime($product['created_at'])); ?></td>

                            <td>
                                <!-- Edit Button -->
                                <a href="edit_product.php?id=<?= $product['id']; ?>" 
                                   class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <!-- Delete Button -->
                                <a href="delete_product.php?id=<?= $product['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this product?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No products found</td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {
    $('#productsTable').DataTable({
        pageLength: 10,
        order: [[5, "desc"]]
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
