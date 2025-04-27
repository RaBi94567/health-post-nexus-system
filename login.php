
<?php
require_once 'header.php';

// Check if user is already logged in
if (is_logged_in()) {
    redirect('index.php');
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($password)) {
        set_flash_message('error', 'Please enter both username and password');
    } else {
        // Check credentials against database
        $stmt = $conn->prepare("SELECT id, username, password, full_name, email, role, profile_image FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Login successful - set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['profile_image'] = $user['profile_image'];
                
                // Update last login time
                $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                $update_stmt->bindParam(':id', $user['id']);
                $update_stmt->execute();
                
                // Redirect to dashboard
                set_flash_message('success', 'Login successful! Welcome back, ' . $user['full_name']);
                redirect('index.php');
            } else {
                set_flash_message('error', 'Invalid password');
            }
        } else {
            set_flash_message('error', 'User not found');
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card login-card">
        <div class="auth-header">
            <div class="logo">
                <i class="fas fa-hospital-alt"></i>
                <h2>HPMS</h2>
            </div>
            <h1>Welcome Back!</h1>
            <p>Enter your credentials to access your account</p>
        </div>
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?>">
                <?= $_SESSION['flash_message']['message'] ?>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>
        
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="auth-form">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <div class="password-input">
                    <input type="password" id="password" name="password" required>
                    <i class="fas fa-eye toggle-password"></i>
                </div>
            </div>
            
            <div class="form-group remember-forgot">
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                <a href="#" class="forgot-password">Forgot Password?</a>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="register.php">Register</a></p>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
