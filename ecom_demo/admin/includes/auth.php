<?php
// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Redirect to login if not authenticated
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

// Admin login function
function adminLogin($email, $password) {
    global $conn;
    
    // For demo, use fixed admin credentials
    // In production, you should have an admin table
    $admin_email = 'admin@ecommerce.com';
    $admin_password = 'admin123'; // You should hash this
    
    if ($email === $admin_email && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $email;
        return true;
    }
    
    return false;
}
?>