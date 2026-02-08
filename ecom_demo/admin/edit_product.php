<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireAdminLogin();

$title = "Edit Product";

// Check ID
if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = intval($_GET['id']);

// Fetch Product
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: products.php");
    exit();
}

$product = $result->fetch_assoc();

// Fetch existing images
$imageQuery = $conn->prepare("SELECT * FROM product_images WHERE product_id = ?");
$imageQuery->bind_param("i", $product_id);
$imageQuery->execute();
$images = $imageQuery->get_result();


// Handle Update
if (isset($_POST['update_product'])) {

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $stock = intval($_POST['stock']);

    // Update product table
    $updateStmt = $conn->prepare("
        UPDATE products 
        SET name=?, description=?, price=?, category=?, stock=? 
        WHERE id=?
    ");

    $updateStmt->bind_param(
        "ssdsii",
        $name,
        $description,
        $price,
        $category,
        $stock,
        $product_id
    );

    $updateStmt->execute();

    // ===============================
    // IMAGE UPLOAD HANDLING
    // ===============================

    if (!empty($_FILES['product_images']['name'][0])) {

        // 1️⃣ Delete old images from server + DB
        $oldImages = $conn->query("SELECT image_url FROM product_images WHERE product_id = $product_id");

        while ($old = $oldImages->fetch_assoc()) {
            if (file_exists($old['image_url'])) {
                unlink($old['image_url']);
            }
        }

        $conn->query("DELETE FROM product_images WHERE product_id = $product_id");

        $uploadDir = "uploads/products/";

        // Create folder if not exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {

            $fileName  = $_FILES['product_images']['name'][$key];
            $fileTmp   = $_FILES['product_images']['tmp_name'][$key];
            $fileSize  = $_FILES['product_images']['size'][$key];
            $fileError = $_FILES['product_images']['error'][$key];

            if ($fileError === 0) {

                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','webp'];

                if (in_array($fileExt, $allowed)) {

                    if ($fileSize <= 5 * 1024 * 1024) { // 5MB

                        $newName = uniqid() . "." . $fileExt;
                        $destination = $uploadDir . $newName;

                        if (move_uploaded_file($fileTmp, $destination)) {

                            $is_primary = ($key === 0) ? 1 : 0;

                            $imgStmt = $conn->prepare("
                                INSERT INTO product_images (product_id, image_url, is_primary)
                                VALUES (?, ?, ?)
                            ");

                            $imgStmt->bind_param(
                                "isi",
                                $product_id,
                                $destination,
                                $is_primary
                            );

                            $imgStmt->execute();
                        }
                    }
                }
            }
        }
    }

    header("Location: products.php?msg=updated");
    exit();
}

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <h4><i class="fas fa-edit me-2"></i> Edit Product</h4>
    <hr>

    <form method="POST" enctype="multipart/form-data">

        <div class="mb-3">
            <label>Product Name</label>
            <input type="text" name="name" class="form-control"
                value="<?= htmlspecialchars($product['name']); ?>" required>
        </div>

        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($product['description']); ?></textarea>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Price ($)</label>
                <input type="number" step="0.01" name="price"
                    class="form-control"
                    value="<?= $product['price']; ?>" required>
            </div>

            <div class="col-md-6 mb-3">
                <label>Category</label>
                <select name="category" class="form-control" required>
                    <?php
                    $categories = ["Electronics","Clothing","Footwear","Home","Books","Other"];
                    foreach ($categories as $cat) {
                        $selected = ($product['category'] == $cat) ? "selected" : "";
                        echo "<option value='$cat' $selected>$cat</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label>Stock Quantity</label>
            <input type="number" name="stock"
                class="form-control"
                value="<?= $product['stock']; ?>" required>
        </div>

        <!-- Existing Images Preview -->
        <div class="mb-3">
            <label>Existing Images</label><br>
            <?php while ($img = $images->fetch_assoc()): ?>
                <img src="<?= $img['image_url']; ?>"
                     width="100"
                     class="me-2 mb-2 border">
            <?php endwhile; ?>
        </div>

        <!-- Media Picker -->
        <div class="mb-3">
            <label>Upload New Images</label>
            <input type="file"
                   name="product_images[]"
                   class="form-control"
                   multiple
                   accept="image/*">
            <small class="text-muted">
                Leave blank to keep existing images.
            </small>
        </div>

        <button type="submit" name="update_product" class="btn btn-primary">
            <i class="fas fa-save me-2"></i> Update Product
        </button>

        <a href="products.php" class="btn btn-secondary">
            Cancel
        </a>

    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
