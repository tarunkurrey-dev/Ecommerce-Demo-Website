<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireAdminLogin();

$title = "Order Management";

// Handle order status update
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $conn->real_escape_string($_POST['status']);
    
    $stmt = $conn->prepare("UPDATE order_history SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
    
    header('Location: orders.php?msg=updated');
    exit();
}

// Handle order deletion
if (isset($_GET['delete_id'])) {
    $order_id = intval($_GET['delete_id']);
    
    // Delete order items first (foreign key constraint)
    $conn->query("DELETE FROM order_items WHERE order_id = $order_id");
    // Then delete the order
    $conn->query("DELETE FROM order_history WHERE id = $order_id");
    
    header('Location: orders.php?msg=deleted');
    exit();
}

// Fetch all orders with user info
$sql = "SELECT o.*, u.username, u.email,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM order_history o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.order_date DESC";
$orders = $conn->query($sql);

require_once 'includes/header.php';
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-shopping-cart me-2"></i> Order Management</h4>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                switch($_GET['msg']) {
                    case 'deleted': echo 'Order deleted successfully!'; break;
                    case 'updated': echo 'Order status updated successfully!'; break;
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="ordersTable">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($orders->num_rows > 0): ?>
                                <?php while($order = $orders->fetch_assoc()): ?>
                                    <?php
                                    $status_class = '';
                                    switch($order['status']) {
                                        case 'pending': $status_class = 'warning'; break;
                                        case 'processing': $status_class = 'info'; break;
                                        case 'shipped': $status_class = 'primary'; break;
                                        case 'delivered': 
                                        case 'completed': $status_class = 'success'; break;
                                        case 'cancelled': $status_class = 'danger'; break;
                                        default: $status_class = 'secondary';
                                    }
                                    ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($order['username']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                        </td>
                                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $order['item_count']; ?> items</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M, Y', strtotime($order['order_date'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info btn-action" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewOrderModal"
                                                    data-id="<?php echo $order['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning btn-action" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editOrderModal"
                                                    data-id="<?php echo $order['id']; ?>"
                                                    data-status="<?php echo $order['status']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete_id=<?php echo $order['id']; ?>" 
                                               class="btn btn-sm btn-danger btn-action"
                                               onclick="return confirm('Are you sure you want to delete this order?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No orders found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- View Order Modal -->
        <div class="modal fade" id="viewOrderModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Order Details #<span id="orderId"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="orderDetails">
                            <!-- Order details loaded via AJAX -->
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Order Modal -->
        <div class="modal fade" id="editOrderModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Order Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="">
                        <div class="modal-body">
                            <input type="hidden" name="order_id" id="editOrderId">
                            <div class="mb-3">
                                <label>Order Status</label>
                                <select name="status" id="editOrderStatus" class="form-control" required>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script>
        // View Order Modal
        document.addEventListener('DOMContentLoaded', function() {
            const viewModal = document.getElementById('viewOrderModal');
            if (viewModal) {
                viewModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const orderId = button.getAttribute('data-id');
                    
                    document.getElementById('orderId').textContent = orderId;
                    
                    // Load order details via AJAX
                    fetch(`get_order_details.php?id=${orderId}`)
                        .then(response => response.json())
                        .then(data => {
                            const orderDetails = document.getElementById('orderDetails');
                            
                            if (data.error) {
                                orderDetails.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                                return;
                            }
                            
                            let statusClass = '';
                            switch(data.order.status) {
                                case 'pending': statusClass = 'warning'; break;
                                case 'processing': statusClass = 'info'; break;
                                case 'shipped': statusClass = 'primary'; break;
                                case 'delivered': 
                                case 'completed': statusClass = 'success'; break;
                                case 'cancelled': statusClass = 'danger'; break;
                                default: statusClass = 'secondary';
                            }
                            
                            let itemsHtml = '';
                            data.items.forEach(item => {
                                itemsHtml += `
                                    <tr>
                                        <td>${item.product_name}</td>
                                        <td class="text-center">${item.quantity}</td>
                                        <td class="text-end">$${parseFloat(item.price).toFixed(2)}</td>
                                        <td class="text-end">$${parseFloat(item.price * item.quantity).toFixed(2)}</td>
                                    </tr>
                                `;
                            });
                            
                            orderDetails.innerHTML = `
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h6>Customer Information</h6>
                                        <p><strong>Name:</strong> ${data.order.username}<br>
                                        <strong>Email:</strong> ${data.order.email}<br>
                                        <strong>Order Date:</strong> ${new Date(data.order.order_date).toLocaleDateString()}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Order Status</h6>
                                        <span class="badge bg-${statusClass} fs-6">${data.order.status.toUpperCase()}</span>
                                        <h6 class="mt-3">Shipping Address</h6>
                                        <p>${data.order.shipping_address || 'No address provided'}</p>
                                    </div>
                                </div>
                                
                                <h6>Order Items</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th width="100" class="text-center">Qty</th>
                                                <th width="120" class="text-end">Price</th>
                                                <th width="120" class="text-end">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${itemsHtml}
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-end">Total Amount:</th>
                                                <th class="text-end">$${parseFloat(data.order.total_amount).toFixed(2)}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            `;
                        })
                        .catch(error => {
                            document.getElementById('orderDetails').innerHTML = 
                                `<div class="alert alert-danger">Failed to load order details</div>`;
                        });
                });
            }
            
            // Edit Order Modal
            const editModal = document.getElementById('editOrderModal');
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    document.getElementById('editOrderId').value = button.getAttribute('data-id');
                    document.getElementById('editOrderStatus').value = button.getAttribute('data-status');
                });
            }
            
            // Initialize DataTable
            $('#ordersTable').DataTable({
                "pageLength": 10,
                "order": [[5, "desc"]]
            });
        });
    </script>

<?php
require_once 'includes/footer.php';
?>