
<?php
require_once 'header.php';

// Check if user is already logged in
if (is_logged_in()) {
    redirect('index.php');
}

// Process register form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = sanitize_input($_POST['email']);
    $full_name = sanitize_input($_POST['full_name']);
    $role = 'staff'; // Default role for new registrations
    
    // Validate input
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Username is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $errors[] = 'Username already exists';
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $errors[] = 'Email already exists';
    }
    
    // If no errors, insert user into database
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (:username, :password, :email, :full_name, :role)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':role', $role);
        
        if ($stmt->execute()) {
            set_flash_message('success', 'Registration successful! You can now login.');
            redirect('login.php');
        } else {
            set_flash_message('error', 'An error occurred. Please try again.');
        }
    } else {
        $error_message = implode('<br>', $errors);
        set_flash_message('error', $error_message);
    }
}
?>

<div class="auth-container">
    <div class="auth-card register-card">
        <div class="auth-header">
            <div class="logo">
                <i class="fas fa-hospital-alt"></i>
                <h2>Health Post Management System</h2>
            </div>
            <h1>Create Account</h1>
            <p>Join us to manage health services efficiently</p>
        </div>
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?>">
                <?= $_SESSION['flash_message']['message'] ?>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>
        
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="auth-form">
            <div class="form-grid">
                <div class="form-group">
                    <label for="full_name">
                        <i class="fas fa-user"></i>
                        <span>Full Name</span>
                    </label>
                    <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
                </div>
                
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user-tag"></i>
                        <span>Username</span>
                    </label>
                    <input type="text" id="username" name="username" placeholder="Choose a username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        <span>Email Address</span>
                    </label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        <span>Password</span>
                    </label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                        <i class="fas fa-eye toggle-password"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i>
                        <span>Confirm Password</span>
                    </label>
                    <div class="password-input">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                        <i class="fas fa-eye toggle-password"></i>
                    </div>
                </div>
            </div>
            
            <div class="form-group terms">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Sign In</a></p>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
