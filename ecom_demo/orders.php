<?php
require_once 'config/database.php';
$db = new Database();

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$orders = $db->conn->query("
    SELECT * FROM order_history 
    WHERE user_id = $user_id 
    ORDER BY order_date DESC
");
?>

<?php include 'includes/header.php'; ?>

<h2>My Orders</h2>

<?php if ($orders->num_rows > 0): ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($order = $orders->fetch_assoc()): ?>
            <tr>
                <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                <td>
                    <span class="badge bg-<?php 
                        switch($order['status']) {
                            case 'pending': echo 'warning'; break;
                            case 'shipped': echo 'info'; break;
                            case 'delivered': echo 'success'; break;
                            default: echo 'secondary';
                        }
                    ?>"><?php echo ucfirst($order['status']); ?></span>
                </td>
                <td>
                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="alert alert-info">
    You have no orders yet. <a href="products.php">Start shopping</a>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>