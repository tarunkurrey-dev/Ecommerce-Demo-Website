<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireAdminLogin();

$title = "Add Product";
$error = '';
$success = '';

if (isset($_POST['add_product'])) {

    // Validate inputs
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $stock = intval($_POST['stock']);

    // Basic validation
    if (empty($name) || empty($description) || $price <= 0 || $stock < 0) {
        $error = "Please fill all fields with valid data.";
    } else {

        // Insert product
        $stmt = $conn->prepare("
            INSERT INTO products (name, description, price, category, stock, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("ssdsi", $name, $description, $price, $category, $stock);

        if ($stmt->execute()) {

            $product_id = $stmt->insert_id;

            // Handle Image Upload
            if (!empty($_FILES['images']['name'][0])) {

                // Define upload directory (relative to this file)
                $uploadDir = __DIR__ . "/../assets/product-images/";
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        $error = "Failed to create upload directory.";
                    }
                }

                if (empty($error)) {
                    
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $maxFileSize = 5 * 1024 * 1024; // 5MB
                    $uploadSuccess = 0;
                    $uploadErrors = [];

                    foreach ($_FILES['images']['name'] as $key => $imageName) {

                        $tmpName = $_FILES['images']['tmp_name'][$key];
                        $error_code = $_FILES['images']['error'][$key];
                        $fileSize = $_FILES['images']['size'][$key];

                        // Check for upload errors
                        if ($error_code !== UPLOAD_ERR_OK) {
                            $uploadErrors[] = "Error uploading $imageName";
                            continue;
                        }

                        // Validate file extension
                        $ext = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
                        if (!in_array($ext, $allowedExtensions)) {
                            $uploadErrors[] = "$imageName has invalid extension. Allowed: " . implode(', ', $allowedExtensions);
                            continue;
                        }

                        // Validate file size
                        if ($fileSize > $maxFileSize) {
                            $uploadErrors[] = "$imageName is too large. Max size: 5MB";
                            continue;
                        }

                        // Validate that it's actually an image
                        $imageInfo = getimagesize($tmpName);
                        if ($imageInfo === false) {
                            $uploadErrors[] = "$imageName is not a valid image file";
                            continue;
                        }

                        // Generate unique filename
                        $newName = "product_" . $product_id . "_" . uniqid() . "_" . $key . "." . $ext;
                        $destination = $uploadDir . $newName;

                        if (move_uploaded_file($tmpName, $destination)) {

                            // Store relative path for database
                            $dbPath = "assets/product-images/" . $newName;
                            $is_primary = ($key === 0) ? 1 : 0;
                            $display_order = $key + 1;

                            $imgStmt = $conn->prepare("
                                INSERT INTO product_images (product_id, image_url, is_primary, display_order, created_at)
                                VALUES (?, ?, ?, ?, NOW())
                            ");
                            $imgStmt->bind_param("isii", $product_id, $dbPath, $is_primary, $display_order);
                            
                            if ($imgStmt->execute()) {
                                $uploadSuccess++;
                            } else {
                                $uploadErrors[] = "Database error for $imageName";
                            }
                        } else {
                            $uploadErrors[] = "Failed to move $imageName to upload directory";
                        }
                    }

                    // Set success or error message
                    if ($uploadSuccess > 0) {
                        $success = "Product added successfully with $uploadSuccess image(s)!";
                        if (!empty($uploadErrors)) {
                            $error = "Some images failed: " . implode(", ", $uploadErrors);
                        }
                        // Redirect after 2 seconds
                        header("refresh:2;url=products.php?msg=added");
                    } else {
                        $error = "Product added but no images uploaded: " . implode(", ", $uploadErrors);
                    }
                }
            } else {
                $success = "Product added successfully without images!";
                header("refresh:2;url=products.php?msg=added");
            }

        } else {
            $error = "Database error: " . $stmt->error;
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <h4><i class="fas fa-plus me-2"></i> Add New Product</h4>
    <hr>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="mb-3">
            <label class="form-label">Product Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" 
                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Description <span class="text-danger">*</span></label>
            <textarea name="description" class="form-control" rows="4" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Price ($) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0.01" name="price" class="form-control" 
                       value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" 
                       required>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Category <span class="text-danger">*</span></label>
                <select name="category" class="form-control" required>
                    <option value="">-- Select Category --</option>
                    <option value="Electronics" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Electronics') ? 'selected' : ''; ?>>Electronics</option>
                    <option value="Clothing" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Clothing') ? 'selected' : ''; ?>>Clothing</option>
                    <option value="Footwear" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Footwear') ? 'selected' : ''; ?>>Footwear</option>
                    <option value="Home" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Home') ? 'selected' : ''; ?>>Home</option>
                    <option value="Books" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Books') ? 'selected' : ''; ?>>Books</option>
                    <option value="Sports" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Sports') ? 'selected' : ''; ?>>Sports</option>
                    <option value="Other" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
            <input type="number" name="stock" min="0" class="form-control" 
                   value="<?php echo isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : '100'; ?>" 
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Upload Product Images</label>
            <input type="file" name="images[]" class="form-control" multiple accept="image/jpeg,image/png,image/gif,image/webp">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                First selected image will be the primary image. 
                Allowed formats: JPG, PNG, GIF, WEBP. Max size: 5MB per image.
            </small>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" name="add_product" class="btn btn-success">
                <i class="fas fa-save me-2"></i> Save Product
            </button>

            <a href="products.php" class="btn btn-secondary">
                <i class="fas fa-times me-2"></i> Cancel
            </a>
        </div>

    </form>
</div>

<?php require_once 'includes/footer.php'; ?>