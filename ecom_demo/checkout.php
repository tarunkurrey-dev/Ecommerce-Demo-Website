<?php
require_once 'config/database.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db = new Database();

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ===============================
   GET CART ITEMS (SAFE)
================================= */
$stmt = $db->conn->prepare("
    SELECT c.*, p.name, p.price 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();

if ($cart_result->num_rows == 0) {
    header("Location: cart.php");
    exit();
}

/* ===============================
   CALCULATE TOTAL
================================= */
$total = 0;
while ($item = $cart_result->fetch_assoc()) {
    $total += $item['price'] * $item['quantity'];
}

$shipping = 10.00;
$grand_total = $total + $shipping;

/* ===============================
   HANDLE CHECKOUT
================================= */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $shipping_address = trim($_POST['shipping_address']);

    $db->conn->begin_transaction();

    try {

        // 1️⃣ Create Order
        $stmt = $db->conn->prepare("
            INSERT INTO order_history (user_id, total_amount, shipping_address)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("ids", $user_id, $grand_total, $shipping_address);
        $stmt->execute();

        $order_id = $db->conn->insert_id;

        // 2️⃣ Get Cart Items Again
        $stmt = $db->conn->prepare("
            SELECT c.*, p.price 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_result = $stmt->get_result();

        while ($item = $cart_result->fetch_assoc()) {

            // Insert order items
            $stmt_item = $db->conn->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            $stmt_item->bind_param(
                "iiid",
                $order_id,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            );
            $stmt_item->execute();

            // 3️⃣ Update stock safely
            $stmt_stock = $db->conn->prepare("
                UPDATE products
                SET stock = stock - ?
                WHERE id = ? AND stock >= ?
            ");
            $stmt_stock->bind_param(
                "iii",
                $item['quantity'],
                $item['product_id'],
                $item['quantity']
            );
            $stmt_stock->execute();

            if ($stmt_stock->affected_rows == 0) {
                throw new Exception("Insufficient stock for product ID: " . $item['product_id']);
            }
        }

        // 4️⃣ Clear cart
        $stmt = $db->conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $db->conn->commit();

        header("Location: order_success.php?id=$order_id");
        exit();

    } catch (Exception $e) {

        $db->conn->rollback();
        $error = "Order failed: " . $e->getMessage();
    }
}
?>

<?php include 'includes/header.php'; ?>

<h2>Checkout</h2>

<?php if (isset($error)): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST">

<div class="row">

    <!-- LEFT SIDE -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Shipping Address</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Full Address</label>
                    <textarea 
                        class="form-control" 
                        name="shipping_address" 
                        rows="3" 
                        required
                    ></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT SIDE -->
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

                <div class="d-flex justify-content-between mb-2">
                    <span>Shipping:</span>
                    <span>$<?php echo number_format($shipping, 2); ?></span>
                </div>

                <hr>

                <div class="d-flex justify-content-between mb-4">
                    <strong>Total:</strong>
                    <strong>$<?php echo number_format($grand_total, 2); ?></strong>
                </div>

                <button type="submit" class="btn btn-success w-100">
                    Place Order
                </button>

            </div>
        </div>
    </div>

</div>

</form>

<?php include 'includes/footer.php'; ?>
