<?php
require_once '../config/database.php';

// If already logged in, redirect to dashboard
if(isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
global $pdo;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $plainPassword = $_POST['password'] ?? '';

    if(!empty($username) && !empty($plainPassword)) {
        // Fetch user by username, then verify password hash
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if($user && isset($user['password'])) {
            if (isset($user['is_active']) && !$user['is_active']) {
                $error = 'Your account is blocked by admin. Please contact an administrator.';
            } else {
                $stored = (string)$user['password'];

                // New users should be password_hash() values (bcrypt/argon)
                if (password_verify($plainPassword, $stored)) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'] ?? '';
                    header('Location: dashboard.php');
                    exit();
                }

                // Backward compatibility: if old password was stored as MD5
                // (md5 is 32 hex chars)
                if (preg_match('/^[a-f0-9]{32}$/i', $stored) && md5($plainPassword) === $stored) {
                    // Optionally upgrade password hash immediately
                    $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
                    $up = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $up->execute([$newHash, $user['id']]);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'] ?? '';
                    header('Location: dashboard.php');
                    exit();
                }
            }
        }

        if ($error === '') {
            $error = 'Invalid username or password!';
        }
    } else {
        $error = 'Please enter both username and password!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="login-card">
                    <div class="login-header">
                        <i class="fas fa-user-shield fa-3x text-white mb-3"></i>
                        <h3>Admin Login</h3>
                        <p class="text-white-50 mb-0">Shipment System</p>
                    </div>
                    <div class="login-body">
                        <?php if($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-login w-100">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </form>
                        <hr>
                        <div class="text-center">
                            <a href="../index.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left"></i> Back to Website
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>