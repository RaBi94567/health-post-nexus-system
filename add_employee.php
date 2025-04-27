
<?php
require_once 'header.php';

// Redirect if not logged in or not admin
if (!is_logged_in() || !is_admin()) {
    set_flash_message('error', 'You do not have permission to access this page');
    redirect('index.php');
}

// Get departments for dropdown
$stmt = $conn->prepare("SELECT id, name FROM departments ORDER BY name");
$stmt->execute();
$departments = $stmt->fetchAll();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // User data
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $email = sanitize_input($_POST['email']);
    $full_name = sanitize_input($_POST['full_name']);
    $role = sanitize_input($_POST['role']);
    
    // Employee data
    $emp_id = sanitize_input($_POST['emp_id']);
    $department_id = sanitize_input($_POST['department_id']);
    $position = sanitize_input($_POST['position']);
    $specialization = isset($_POST['specialization']) ? sanitize_input($_POST['specialization']) : null;
    $join_date = sanitize_input($_POST['join_date']);
    $salary = sanitize_input($_POST['salary']);
    $contact_number = sanitize_input($_POST['contact_number']);
    $address = sanitize_input($_POST['address']);
    $status = sanitize_input($_POST['status']);
    
    // Validate input
    $errors = [];
    
    // Check required fields
    if (empty($username) || empty($password) || empty($email) || empty($full_name) || empty($role) ||
        empty($emp_id) || empty($department_id) || empty($position) || empty($join_date)) {
        $errors[] = 'Please fill in all required fields';
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
    
    // Check if employee ID already exists
    $stmt = $conn->prepare("SELECT id FROM employees WHERE emp_id = :emp_id");
    $stmt->bindParam(':emp_id', $emp_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $errors[] = 'Employee ID already exists';
    }
    
    // Handle file upload
    $profile_image = 'default.jpg'; // Default image
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'assets/images/';
        $file_name = time() . '_' . basename($_FILES['profile_image']['name']);
        $upload_path = $upload_dir . $file_name;
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['profile_image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = 'Only JPG, JPEG, and PNG files are allowed';
        } elseif ($_FILES['profile_image']['size'] > 2097152) { // 2MB
            $errors[] = 'File size must be less than 2MB';
        } elseif (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            $profile_image = $file_name;
        } else {
            $errors[] = 'Failed to upload image';
        }
    }
    
    // If no errors, add user and employee
    if (empty($errors)) {
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role, profile_image) VALUES (:username, :password, :email, :full_name, :role, :profile_image)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':profile_image', $profile_image);
            $stmt->execute();
            
            // Get the inserted user ID
            $user_id = $conn->lastInsertId();
            
            // Insert employee
            $stmt = $conn->prepare("INSERT INTO employees (user_id, emp_id, department_id, position, specialization, join_date, salary, contact_number, address, status) VALUES (:user_id, :emp_id, :department_id, :position, :specialization, :join_date, :salary, :contact_number, :address, :status)");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':emp_id', $emp_id);
            $stmt->bindParam(':department_id', $department_id);
            $stmt->bindParam(':position', $position);
            $stmt->bindParam(':specialization', $specialization);
            $stmt->bindParam(':join_date', $join_date);
            $stmt->bindParam(':salary', $salary);
            $stmt->bindParam(':contact_number', $contact_number);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            set_flash_message('success', 'Employee added successfully');
            redirect('employees.php');
        } catch (PDOException $e) {
            // Rollback transaction if error occurs
            $conn->rollBack();
            set_flash_message('error', 'An error occurred: ' . $e->getMessage());
        }
    } else {
        $error_message = implode('<br>', $errors);
        set_flash_message('error', $error_message);
    }
}
?>

<div class="add-employee-page">
    <div class="page-header">
        <h1><i class="fas fa-user-plus"></i> Add New Employee</h1>
        <div class="page-actions">
            <a href="employees.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Employees
            </a>
        </div>
    </div>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?>">
            <?= $_SESSION['flash_message']['message'] ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
    
    <div class="form-container">
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data" class="form">
            <div class="form-sections">
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h2>Personal Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_number">Contact Number</label>
                            <input type="tel" id="contact_number" name="contact_number">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="profile_image">Profile Image</label>
                            <input type="file" id="profile_image" name="profile_image" accept="image/jpeg, image/png, image/jpg">
                        </div>
                    </div>
                </div>
                
                <!-- Account Information Section -->
                <div class="form-section">
                    <h2>Account Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Role *</label>
                            <select id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="doctor">Doctor</option>
                                <option value="nurse">Nurse</option>
                                <option value="staff">Staff</option>
                                <?php if (is_admin()): ?>
                                <option value="admin">Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Employment Information Section -->
                <div class="form-section">
                    <h2>Employment Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="emp_id">Employee ID *</label>
                            <input type="text" id="emp_id" name="emp_id" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="department_id">Department *</label>
                            <select id="department_id" name="department_id" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?= $department['id'] ?>"><?= $department['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="position">Position *</label>
                            <input type="text" id="position" name="position" required>
                        </div>
                        
                        <div class="form-group doctor-field" style="display: none;">
                            <label for="specialization">Specialization</label>
                            <input type="text" id="specialization" name="specialization">
                        </div>
                        
                        <div class="form-group">
                            <label for="join_date">Join Date *</label>
                            <input type="date" id="join_date" name="join_date" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="salary">Salary</label>
                            <input type="number" id="salary" name="salary" step="0.01">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="reset" class="btn btn-outline">Reset</button>
                <button type="submit" class="btn btn-primary">Add Employee</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show/hide specialization field based on role selection
    const roleSelect = document.getElementById('role');
    const specialization = document.querySelector('.doctor-field');
    
    roleSelect.addEventListener('change', function() {
        if (this.value === 'doctor') {
            specialization.style.display = 'block';
        } else {
            specialization.style.display = 'none';
            document.getElementById('specialization').value = '';
        }
    });
    
    // Generate employee ID based on role and department
    const generateEmpId = function() {
        const role = roleSelect.value;
        const departmentId = document.getElementById('department_id').value;
        
        if (role && departmentId) {
            let prefix = '';
            
            switch(role) {
                case 'doctor':
                    prefix = 'DOC';
                    break;
                case 'nurse':
                    prefix = 'NRS';
                    break;
                case 'staff':
                    prefix = 'STF';
                    break;
                case 'admin':
                    prefix = 'ADM';
                    break;
            }
            
            // Generate a random 3-digit number
            const random = Math.floor(Math.random() * 900) + 100;
            
            document.getElementById('emp_id').value = prefix + random;
        }
    };
    
    roleSelect.addEventListener('change', generateEmpId);
    document.getElementById('department_id').addEventListener('change', generateEmpId);
});
</script>

<?php require_once 'footer.php'; ?>
