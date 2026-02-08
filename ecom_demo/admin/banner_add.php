<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireAdminLogin();

$title = "Add Banner";
$error = '';
$success = '';

// Handle Delete Banner
if (isset($_GET['delete'])) {
    $bannerId = intval($_GET['delete']);
    
    // Get banner image path before deleting
    $stmt = $conn->prepare("SELECT image_url FROM banners WHERE id = ?");
    $stmt->bind_param("i", $bannerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($banner = $result->fetch_assoc()) {
        $imagePath = __DIR__ . "/../" . $banner['image_url'];
        
        // Delete from database
        $deleteStmt = $conn->prepare("DELETE FROM banners WHERE id = ?");
        $deleteStmt->bind_param("i", $bannerId);
        
        if ($deleteStmt->execute()) {
            // Delete file from server
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            $success = "Banner deleted successfully!";
        } else {
            $error = "Failed to delete banner from database.";
        }
    }
}

// Handle Toggle Active Status
if (isset($_GET['toggle'])) {
    $bannerId = intval($_GET['toggle']);
    
    $stmt = $conn->prepare("UPDATE banners SET is_active = NOT is_active WHERE id = ?");
    $stmt->bind_param("i", $bannerId);
    
    if ($stmt->execute()) {
        $success = "Banner status updated!";
    } else {
        $error = "Failed to update banner status.";
    }
}

// Handle Add Banner
if (isset($_POST['add_banner'])) {

    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validate image upload
    if (empty($_FILES['banner_image']['name'])) {
        $error = "Please select a banner image.";
    } else {

        // Define upload directory
        $uploadDir = __DIR__ . "/../assets/banners/";
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $error = "Failed to create upload directory.";
            }
        }

        if (empty($error)) {
            
            $imageName = $_FILES['banner_image']['name'];
            $tmpName = $_FILES['banner_image']['tmp_name'];
            $error_code = $_FILES['banner_image']['error'];
            $fileSize = $_FILES['banner_image']['size'];

            // Check for upload errors
            if ($error_code !== UPLOAD_ERR_OK) {
                $error = "Error uploading banner image.";
            } else {

                // Validate file extension
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
                
                if (!in_array($ext, $allowedExtensions)) {
                    $error = "Invalid file format. Allowed: JPG, JPEG, PNG, GIF, WEBP";
                } else {

                    // Validate file size (max 5MB)
                    $maxFileSize = 5 * 1024 * 1024;
                    if ($fileSize > $maxFileSize) {
                        $error = "File is too large. Maximum size: 5MB";
                    } else {

                        // Validate that it's actually an image
                        $imageInfo = getimagesize($tmpName);
                        if ($imageInfo === false) {
                            $error = "File is not a valid image.";
                        } else {

                            // Validate image dimensions (recommended: width >= 1200px)
                            $width = $imageInfo[0];
                            $height = $imageInfo[1];
                            
                            if ($width < 800) {
                                $error = "Image width should be at least 800px. Current: {$width}px";
                            } else {

                                // Generate unique filename
                                $newName = "banner_" . time() . "_" . uniqid() . "." . $ext;
                                $destination = $uploadDir . $newName;

                                if (move_uploaded_file($tmpName, $destination)) {

                                    // Store relative path for database
                                    $dbPath = "assets/banners/" . $newName;

                                    // Insert into database
                                    $stmt = $conn->prepare("
                                        INSERT INTO banners (image_url, is_active, created_at)
                                        VALUES (?, ?, NOW())
                                    ");
                                    $stmt->bind_param("si", $dbPath, $is_active);
                                    
                                    if ($stmt->execute()) {
                                        $success = "Banner added successfully!";
                                        // Refresh to show new banner
                                        header("refresh:1");
                                    } else {
                                        $error = "Database error: " . $stmt->error;
                                        // Delete uploaded file if database insert fails
                                        unlink($destination);
                                    }
                                } else {
                                    $error = "Failed to upload image.";
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// Handle Edit Banner (Image Replacement)
if (isset($_POST['edit_banner'])) {
    $bannerId = intval($_POST['banner_id']);
    $is_active = isset($_POST['is_active_edit']) ? 1 : 0;
    
    // Check if new image is uploaded
    if (!empty($_FILES['banner_image_edit']['name'])) {
        
        $uploadDir = __DIR__ . "/../assets/banners/";
        $imageName = $_FILES['banner_image_edit']['name'];
        $tmpName = $_FILES['banner_image_edit']['tmp_name'];
        $error_code = $_FILES['banner_image_edit']['error'];
        $fileSize = $_FILES['banner_image_edit']['size'];

        if ($error_code !== UPLOAD_ERR_OK) {
            $error = "Error uploading banner image.";
        } else {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowedExtensions)) {
                $error = "Invalid file format. Allowed: JPG, JPEG, PNG, GIF, WEBP";
            } else {
                $maxFileSize = 5 * 1024 * 1024;
                if ($fileSize > $maxFileSize) {
                    $error = "File is too large. Maximum size: 5MB";
                } else {
                    $imageInfo = getimagesize($tmpName);
                    if ($imageInfo === false) {
                        $error = "File is not a valid image.";
                    } else {
                        $width = $imageInfo[0];
                        
                        if ($width < 800) {
                            $error = "Image width should be at least 800px. Current: {$width}px";
                        } else {
                            // Get old image path
                            $stmt = $conn->prepare("SELECT image_url FROM banners WHERE id = ?");
                            $stmt->bind_param("i", $bannerId);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $oldBanner = $result->fetch_assoc();
                            $oldImagePath = __DIR__ . "/../" . $oldBanner['image_url'];
                            
                            // Generate new filename
                            $newName = "banner_" . time() . "_" . uniqid() . "." . $ext;
                            $destination = $uploadDir . $newName;
                            
                            if (move_uploaded_file($tmpName, $destination)) {
                                $dbPath = "assets/banners/" . $newName;
                                
                                // Update database
                                $updateStmt = $conn->prepare("UPDATE banners SET image_url = ?, is_active = ? WHERE id = ?");
                                $updateStmt->bind_param("sii", $dbPath, $is_active, $bannerId);
                                
                                if ($updateStmt->execute()) {
                                    // Delete old image
                                    if (file_exists($oldImagePath)) {
                                        unlink($oldImagePath);
                                    }
                                    $success = "Banner updated successfully!";
                                    header("refresh:1");
                                } else {
                                    $error = "Database error: " . $updateStmt->error;
                                    unlink($destination);
                                }
                            } else {
                                $error = "Failed to upload new image.";
                            }
                        }
                    }
                }
            }
        }
    } else {
        // Just update status without changing image
        $updateStmt = $conn->prepare("UPDATE banners SET is_active = ? WHERE id = ?");
        $updateStmt->bind_param("ii", $is_active, $bannerId);
        
        if ($updateStmt->execute()) {
            $success = "Banner status updated successfully!";
            header("refresh:1");
        } else {
            $error = "Failed to update banner status.";
        }
    }
}

// Fetch all banners
$bannersQuery = "SELECT * FROM banners ORDER BY created_at DESC";
$bannersResult = $conn->query($bannersQuery);

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h4><i class="fas fa-image me-2"></i> Add New Banner</h4>
        </div>
        <div class="col-md-6 text-end">
            <a href="banners.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Banners
            </a>
        </div>
    </div>

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

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i> Banner Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="bannerForm">

                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                Banner Image <span class="text-danger">*</span>
                            </label>
                            <input type="file" 
                                   name="banner_image" 
                                   class="form-control" 
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   required
                                   id="bannerImageInput">
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i> 
                                Recommended size: 1200x450px or wider. Maximum file size: 5MB.
                                <br>
                                Allowed formats: JPG, PNG, GIF, WEBP
                            </div>
                        </div>

                        <!-- Image Preview -->
                        <div class="mb-4" id="imagePreviewContainer" style="display: none;">
                            <label class="form-label fw-bold">Preview</label>
                            <div class="border rounded p-2">
                                <img id="imagePreview" src="" alt="Preview" class="img-fluid" style="max-height: 300px;">
                            </div>
                            <div id="imageDimensions" class="form-text mt-2"></div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       name="is_active" 
                                       id="isActive"
                                       checked>
                                <label class="form-check-label fw-bold" for="isActive">
                                    Active
                                </label>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i> 
                                    Toggle to show/hide this banner on the homepage
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex gap-2">
                            <button type="submit" name="add_banner" class="btn btn-success">
                                <i class="fas fa-save me-2"></i> Save Banner
                            </button>
                            <a href="banners.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i> Cancel
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- Guidelines Card -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i> Banner Guidelines</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Dimensions:</strong> 1200x450px or higher
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Aspect Ratio:</strong> 8:3 (recommended)
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>File Size:</strong> Maximum 5MB
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Format:</strong> JPG, PNG, GIF, WEBP
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Content:</strong> High quality, clear images
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Important Notes</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled small mb-0">
                        <li class="mb-2">
                            <i class="fas fa-arrow-right text-warning me-2"></i>
                            Banners will auto-slide every 5 seconds
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-arrow-right text-warning me-2"></i>
                            Only active banners appear on homepage
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-arrow-right text-warning me-2"></i>
                            Newer banners appear first in slider
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-arrow-right text-warning me-2"></i>
                            Use high-quality images for best results
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Existing Banners Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-images me-2"></i> All Banners
                        <span class="badge bg-light text-dark ms-2"><?php echo $bannersResult->num_rows; ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($bannersResult->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="80">ID</th>
                                        <th width="200">Preview</th>
                                        <th>Image URL</th>
                                        <th width="100" class="text-center">Status</th>
                                        <th width="150">Created At</th>
                                        <th width="200" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($banner = $bannersResult->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo $banner['id']; ?></strong></td>
                                            <td>
                                                <img src="../<?php echo htmlspecialchars($banner['image_url']); ?>" 
                                                     alt="Banner" 
                                                     class="img-thumbnail" 
                                                     style="max-height: 80px; cursor: pointer;"
                                                     onclick="showImageModal('<?php echo htmlspecialchars($banner['image_url']); ?>')">
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($banner['image_url']); ?>
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($banner['is_active']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle me-1"></i> Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-times-circle me-1"></i> Inactive
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php echo date('M d, Y', strtotime($banner['created_at'])); ?>
                                                    <br>
                                                    <span class="text-muted">
                                                        <?php echo date('h:i A', strtotime($banner['created_at'])); ?>
                                                    </span>
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <!-- Toggle Status -->
                                                    <a href="?toggle=<?php echo $banner['id']; ?>" 
                                                       class="btn btn-outline-primary"
                                                       title="Toggle Status"
                                                       onclick="return confirm('Toggle banner status?');">
                                                        <i class="fas fa-toggle-on"></i>
                                                    </a>
                                                    
                                                    <!-- Edit Button -->
                                                    <button type="button" 
                                                            class="btn btn-outline-success" 
                                                            title="Edit Banner"
                                                            onclick="openEditModal(<?php echo $banner['id']; ?>, '<?php echo htmlspecialchars($banner['image_url']); ?>', <?php echo $banner['is_active']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <!-- Delete Button -->
                                                    <a href="?delete=<?php echo $banner['id']; ?>" 
                                                       class="btn btn-outline-danger"
                                                       title="Delete Banner"
                                                       onclick="return confirm('Are you sure you want to delete this banner? This action cannot be undone.');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-image fa-3x mb-3"></i>
                            <p>No banners found. Add your first banner above.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Banner Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Banner" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- Edit Banner Modal -->
<div class="modal fade" id="editBannerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i> Edit Banner
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="editBannerForm">
                <div class="modal-body">
                    <input type="hidden" name="banner_id" id="edit_banner_id">
                    
                    <!-- Current Image Preview -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Image</label>
                        <div class="border rounded p-2 text-center">
                            <img id="edit_current_image" src="" alt="Current Banner" class="img-fluid" style="max-height: 200px;">
                        </div>
                    </div>
                    
                    <!-- New Image Upload -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Change Image <span class="text-muted">(Optional)</span>
                        </label>
                        <input type="file" 
                               name="banner_image_edit" 
                               class="form-control" 
                               accept="image/jpeg,image/png,image/gif,image/webp"
                               id="bannerImageInputEdit">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i> 
                            Leave empty to keep current image
                        </div>
                    </div>
                    
                    <!-- New Image Preview -->
                    <div class="mb-3" id="imagePreviewContainerEdit" style="display: none;">
                        <label class="form-label fw-bold">New Image Preview</label>
                        <div class="border rounded p-2">
                            <img id="imagePreviewEdit" src="" alt="Preview" class="img-fluid" style="max-height: 200px;">
                        </div>
                        <div id="imageDimensionsEdit" class="form-text mt-2"></div>
                    </div>
                    
                    <!-- Status Toggle -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="is_active_edit" 
                                   id="isActiveEdit">
                            <label class="form-check-label fw-bold" for="isActiveEdit">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i> Cancel
                    </button>
                    <button type="submit" name="edit_banner" class="btn btn-success">
                        <i class="fas fa-save me-2"></i> Update Banner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Image preview functionality for add banner
document.getElementById('bannerImageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            preview.src = e.target.result;
            document.getElementById('imagePreviewContainer').style.display = 'block';
            
            // Get image dimensions
            const img = new Image();
            img.onload = function() {
                const dimensions = document.getElementById('imageDimensions');
                dimensions.innerHTML = `
                    <i class="fas fa-ruler-combined me-1"></i> 
                    Image Dimensions: <strong>${this.width} x ${this.height}px</strong>
                    ${this.width < 1200 ? '<br><span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i> Recommended width is 1200px or higher</span>' : ''}
                    ${this.width < 800 ? '<br><span class="text-danger"><i class="fas fa-times-circle me-1"></i> Minimum width is 800px!</span>' : ''}
                `;
            };
            img.src = e.target.result;
        };
        
        reader.readAsDataURL(file);
    }
});

// Image preview functionality for edit banner
document.getElementById('bannerImageInputEdit').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreviewEdit');
            preview.src = e.target.result;
            document.getElementById('imagePreviewContainerEdit').style.display = 'block';
            
            // Get image dimensions
            const img = new Image();
            img.onload = function() {
                const dimensions = document.getElementById('imageDimensionsEdit');
                dimensions.innerHTML = `
                    <i class="fas fa-ruler-combined me-1"></i> 
                    Image Dimensions: <strong>${this.width} x ${this.height}px</strong>
                    ${this.width < 1200 ? '<br><span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i> Recommended width is 1200px or higher</span>' : ''}
                    ${this.width < 800 ? '<br><span class="text-danger"><i class="fas fa-times-circle me-1"></i> Minimum width is 800px!</span>' : ''}
                `;
            };
            img.src = e.target.result;
        };
        
        reader.readAsDataURL(file);
    }
});

// Form validation
document.getElementById('bannerForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('bannerImageInput');
    if (!fileInput.files || !fileInput.files[0]) {
        e.preventDefault();
        alert('Please select a banner image.');
        return false;
    }
    
    const fileSize = fileInput.files[0].size;
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    if (fileSize > maxSize) {
        e.preventDefault();
        alert('File size exceeds 5MB. Please choose a smaller image.');
        return false;
    }
});

// Show image in modal
function showImageModal(imagePath) {
    document.getElementById('modalImage').src = '../' + imagePath;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
}

// Open edit modal
function openEditModal(bannerId, imageUrl, isActive) {
    document.getElementById('edit_banner_id').value = bannerId;
    document.getElementById('edit_current_image').src = '../' + imageUrl;
    document.getElementById('isActiveEdit').checked = isActive == 1;
    document.getElementById('imagePreviewContainerEdit').style.display = 'none';
    document.getElementById('bannerImageInputEdit').value = '';
    
    new bootstrap.Modal(document.getElementById('editBannerModal')).show();
}
</script>

<style>
.form-label {
    font-size: 0.95rem;
}

.card {
    border: none;
}

.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}

#imagePreview, #imagePreviewEdit {
    border-radius: 8px;
}

.list-unstyled li {
    padding: 5px 0;
}

.img-thumbnail {
    transition: transform 0.2s;
}

.img-thumbnail:hover {
    transform: scale(1.05);
}

.table td {
    vertical-align: middle;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}
</style>

<?php require_once 'includes/footer.php'; ?>