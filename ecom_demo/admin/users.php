<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireAdminLogin();

$title = "Users Management";

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    
    // Prevent deleting yourself
    if ($user_id == $_SESSION['user_id']) {
        header("Location: users.php?error=cannot_delete_self");
        exit();
    }
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        header("Location: users.php?msg=deleted");
    } else {
        header("Location: users.php?error=delete_failed");
    }
    exit();
}

// Handle user status toggle (if you want to add active/inactive feature)
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $user_id = intval($_GET['toggle_status']);
    
    $stmt = $conn->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        header("Location: users.php?msg=status_updated");
    }
    exit();
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';
$params = [];
$types = '';

if (!empty($search)) {
    $whereClause = "WHERE username LIKE ? OR email LIKE ?";
    $searchParam = "%{$search}%";
    $params = [$searchParam, $searchParam];
    $types = 'ss';
}

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM users $whereClause";
if (!empty($params)) {
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $totalResult = $countStmt->get_result();
} else {
    $totalResult = $conn->query($countQuery);
}
$totalUsers = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);

// Get users with statistics
$query = "
    SELECT 
        u.id,
        u.username,
        u.email,
        u.created_at,
        COUNT(DISTINCT c.id) as cart_items,
        COUNT(DISTINCT w.id) as wishlist_items,
        COUNT(DISTINCT oh.id) as total_orders,
        COALESCE(SUM(oh.total_amount), 0) as total_spent
    FROM users u
    LEFT JOIN cart c ON u.id = c.user_id
    LEFT JOIN wishlist w ON u.id = w.user_id
    LEFT JOIN order_history oh ON u.id = oh.user_id
    $whereClause
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

require_once 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h4><i class="fas fa-users me-2"></i> Users Management</h4>
        </div>
        <div class="col-md-6 text-end">
            <a href="add_user.php" class="btn btn-primary">
                <i class="fas fa-user-plus me-2"></i> Add New User
            </a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php
            switch ($_GET['msg']) {
                case 'added':
                    echo 'User added successfully!';
                    break;
                case 'updated':
                    echo 'User updated successfully!';
                    break;
                case 'deleted':
                    echo 'User deleted successfully!';
                    break;
                case 'status_updated':
                    echo 'User status updated successfully!';
                    break;
                default:
                    echo 'Operation completed successfully!';
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php
            switch ($_GET['error']) {
                case 'cannot_delete_self':
                    echo 'You cannot delete your own account!';
                    break;
                case 'delete_failed':
                    echo 'Failed to delete user. Please try again.';
                    break;
                default:
                    echo 'An error occurred. Please try again.';
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Search Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by username or email..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i> Search
                    </button>
                </div>
                <?php if (!empty($search)): ?>
                    <div class="col-12">
                        <a href="users.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-times me-2"></i> Clear Search
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i> All Users 
                <span class="badge bg-light text-dark"><?php echo $totalUsers; ?></span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Registered On</th>
                            <th class="text-center">Cart Items</th>
                            <th class="text-center">Wishlist</th>
                            <th class="text-center">Orders</th>
                            <th class="text-end">Total Spent</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($user = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $user['id']; ?></strong></td>
                                    <td>
                                        <i class="fas fa-user me-2 text-primary"></i>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                        <!-- <?php if ($user['id'] == $_SESSION['user_id']): ?> -->
                                            <span class="badge bg-info ms-2">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-envelope me-2 text-secondary"></i>
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">
                                            <?php echo $user['cart_items']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark">
                                            <?php echo $user['wishlist_items']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">
                                            <?php echo $user['total_orders']; ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-success">
                                            $<?php echo number_format($user['total_spent'], 2); ?>
                                        </strong>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="view_user.php?id=<?php echo $user['id']; ?>" 
                                               class="btn btn-info btn-sm" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" 
                                               class="btn btn-warning btn-sm" 
                                               title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="users.php?delete=<?php echo $user['id']; ?>" 
                                                   class="btn btn-danger btn-sm" 
                                                   title="Delete User"
                                                   onclick="return confirm('Are you sure you want to delete this user? This will also delete all their cart items, wishlist, and orders.');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">
                                        <?php echo !empty($search) ? 'No users found matching your search.' : 'No users found.'; ?>
                                    </p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <nav aria-label="User pagination">
                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                        <!-- Previous Button -->
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" 
                               href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        </li>

                        <!-- Page Numbers -->
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        if ($startPage > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . '">1</a></li>';
                            if ($startPage > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        for ($i = $startPage; $i <= $endPage; $i++) {
                            $active = ($i == $page) ? 'active' : '';
                            echo '<li class="page-item ' . $active . '">
                                    <a class="page-link" href="?page=' . $i . (!empty($search) ? '&search=' . urlencode($search) : '') . '">' . $i . '</a>
                                  </li>';
                        }

                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . (!empty($search) ? '&search=' . urlencode($search) : '') . '">' . $totalPages . '</a></li>';
                        }
                        ?>

                        <!-- Next Button -->
                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" 
                               href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <div class="text-center mt-2">
                    <small class="text-muted">
                        Showing page <?php echo $page; ?> of <?php echo $totalPages; ?> 
                        (<?php echo $totalUsers; ?> total users)
                    </small>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Statistics Cards -->
    <div class="row mt-4">
        <?php
        // Get overall statistics
        $statsQuery = "
            SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users_30days,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_users_7days
            FROM users
        ";
        $statsResult = $conn->query($statsQuery);
        $stats = $statsResult->fetch_assoc();
        ?>

        <div class="col-md-4">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Users</h6>
                            <h3 class="mb-0"><?php echo $stats['total_users']; ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">New (Last 7 Days)</h6>
                            <h3 class="mb-0"><?php echo $stats['new_users_7days']; ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-user-plus fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">New (Last 30 Days)</h6>
                            <h3 class="mb-0"><?php echo $stats['new_users_30days']; ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-calendar fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table th {
    font-weight: 600;
    font-size: 0.9rem;
}

.opacity-50 {
    opacity: 0.5;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>

<?php require_once 'includes/footer.php'; ?>