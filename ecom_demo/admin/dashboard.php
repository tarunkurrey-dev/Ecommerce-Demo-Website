<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Check authentication
requireAdminLogin();

$title = "Dashboard";
?>
        <!-- Dashboard Stats -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card product">
                    <div class="stats-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <h5>Total Products</h5>
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as total FROM products");
                    $row = $result->fetch_assoc();
                    ?>
                    <h2><?php echo $row['total']; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card order">
                    <div class="stats-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h5>Total Orders</h5>
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as total FROM order_history");
                    $row = $result->fetch_assoc();
                    ?>
                    <h2><?php echo $row['total']; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card user">
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h5>Total Users</h5>
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as total FROM users");
                    $row = $result->fetch_assoc();
                    ?>
                    <h2><?php echo $row['total']; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card revenue">
                    <div class="stats-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h5>Total Revenue</h5>
                    <?php
                    $result = $conn->query("SELECT SUM(total_amount) as total FROM order_history WHERE status = 'completed'");
                    $row = $result->fetch_assoc();
                    $revenue = $row['total'] ?? 0;
                    ?>
                    <h2>$<?php echo number_format($revenue, 2); ?></h2>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i> Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT o.*, u.username 
                                            FROM order_history o 
                                            JOIN users u ON o.user_id = u.id 
                                            ORDER BY o.order_date DESC 
                                            LIMIT 5";
                                    $result = $conn->query($sql);
                                    
                                    if ($result->num_rows > 0) {
                                        while($row = $result->fetch_assoc()) {
                                            $status_class = '';
                                            switch($row['status']) {
                                                case 'completed': $status_class = 'success'; break;
                                                case 'pending': $status_class = 'warning'; break;
                                                case 'cancelled': $status_class = 'danger'; break;
                                                default: $status_class = 'secondary';
                                            }
                                            ?>
                                            <tr>
                                                <td>#<?php echo $row['id']; ?></td>
                                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                                <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst($row['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d M, Y', strtotime($row['order_date'])); ?></td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="5" class="text-center">No orders found</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="orders.php" class="btn btn-outline-primary btn-sm">View All Orders</a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Products -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-star me-2"></i> Top Products</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $sql = "SELECT p.*, COUNT(c.product_id) as in_carts 
                                FROM products p 
                                LEFT JOIN cart c ON p.id = c.product_id 
                                GROUP BY p.id 
                                ORDER BY in_carts DESC, p.created_at DESC 
                                LIMIT 5";
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <?php
                                        $img_sql = "SELECT image_url FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1";
                                        $stmt = $conn->prepare($img_sql);
                                        $stmt->bind_param("i", $row['id']);
                                        $stmt->execute();
                                        $img_result = $stmt->get_result();
                                        $image = $img_result->fetch_assoc();
                                        ?>
                                        <img src="../<?php echo $image['image_url'] ?? 'https://via.placeholder.com/50'; ?>" 
                                             alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($row['name']); ?></h6>
                                        <small class="text-muted">$<?php echo $row['price']; ?></small>
                                        <span class="badge bg-info float-end"><?php echo $row['stock']; ?> in stock</span>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<p class="text-center">No products found</p>';
                        }
                        ?>
                        <a href="products.php" class="btn btn-outline-primary btn-sm w-100">Manage Products</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
require_once 'includes/footer.php';
?>