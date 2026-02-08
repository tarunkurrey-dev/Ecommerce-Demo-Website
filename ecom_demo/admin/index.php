<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    if (adminLogin($email, $password)) {
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Ecommerce</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f1f3f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .login-card {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .login-header {
            background: #343a40;
            color: #fff;
            padding: 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }

        .login-body {
            padding: 30px;
        }

        .btn-login {
            background-color: #343a40;
            color: white;
            font-weight: 500;
        }

        .btn-login:hover {
            background-color: #23272b;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="login-card">
                
                <div class="login-header">
                    <h4 class="mb-0">Admin Login</h4>
                </div>

                <div class="login-body">
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" 
                                   class="form-control" 
                                   placeholder="Enter email" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" 
                                   class="form-control" 
                                   placeholder="Enter password" required>
                        </div>

                        <button type="submit" name="login" 
                                class="btn btn-login w-100">
                            Login
                        </button>

                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
