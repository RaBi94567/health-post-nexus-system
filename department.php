
<?php
require_once 'header.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get all departments
$stmt = $conn->prepare("SELECT * FROM departments ORDER BY name");
$stmt->execute();
$departments = $stmt->fetchAll();

// Process add department form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    
    if (empty($name)) {
        set_flash_message('error', 'Department name is required');
    } else {
        // Check if department already exists
        $stmt = $conn->prepare("SELECT id FROM departments WHERE name = :name");
        $stmt->bindParam(':name', $name);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            set_flash_message('error', 'Department already exists');
        } else {
            // Add department
            $stmt = $conn->prepare("INSERT INTO departments (name, description) VALUES (:name, :description)");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            
            if ($stmt->execute()) {
                set_flash_message('success', 'Department added successfully');
                redirect('department.php');
            } else {
                set_flash_message('error', 'Failed to add department');
            }
        }
    }
}

// Process delete department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = sanitize_input($_POST['id']);
    
    // Check if department is used by any employee
    $stmt = $conn->prepare("SELECT id FROM employees WHERE department_id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        set_flash_message('error', 'Cannot delete department as it is associated with employees');
    } else {
        // Delete department
        $stmt = $conn->prepare("DELETE FROM departments WHERE id = :id");
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            set_flash_message('success', 'Department deleted successfully');
            redirect('department.php');
        } else {
            set_flash_message('error', 'Failed to delete department');
        }
    }
}
?>

<div class="department-page">
    <div class="page-header">
        <h1><i class="fas fa-hospital"></i> Department Management</h1>
        <div class="page-actions">
            <button class="btn btn-primary" data-toggle="modal" data-target="addDepartmentModal">
                <i class="fas fa-plus"></i> Add Department
            </button>
        </div>
    </div>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?>">
            <?= $_SESSION['flash_message']['message'] ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
    
    <!-- Department Cards -->
    <div class="department-cards">
        <?php foreach ($departments as $department): ?>
            <div class="department-card">
                <div class="department-header">
                    <h3><?= $department['name'] ?></h3>
                    <div class="department-actions">
                        <button class="btn btn-sm btn-icon edit-department" data-id="<?= $department['id'] ?>" data-name="<?= $department['name'] ?>" data-description="<?= $department['description'] ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-icon delete-department" data-id="<?= $department['id'] ?>" data-name="<?= $department['name'] ?>">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="department-body">
                    <p><?= !empty($department['description']) ? $department['description'] : 'No description available' ?></p>
                </div>
                <div class="department-footer">
                    <?php
                    // Count employees in this department
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) as count, u.role 
                        FROM employees e 
                        JOIN users u ON e.user_id = u.id 
                        WHERE e.department_id = :id 
                        GROUP BY u.role
                    ");
                    $stmt->bindParam(':id', $department['id']);
                    $stmt->execute();
                    $counts = $stmt->fetchAll();
                    
                    $doctorCount = 0;
                    $staffCount = 0;
                    
                    foreach ($counts as $count) {
                        if ($count['role'] === 'doctor') {
                            $doctorCount = $count['count'];
                        } else {
                            $staffCount += $count['count'];
                        }
                    }
                    ?>
                    <span><i class="fas fa-user-md"></i> <?= $doctorCount ?> Doctors</span>
                    <span><i class="fas fa-user-nurse"></i> <?= $staffCount ?> Staff</span>
                    <a href="department_detail.php?id=<?= $department['id'] ?>" class="btn btn-sm btn-outline">View Details</a>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (count($departments) === 0): ?>
            <div class="empty-state">
                <i class="fas fa-hospital"></i>
                <p>No departments found</p>
                <button class="btn btn-primary" data-toggle="modal" data-target="addDepartmentModal">
                    Add Department
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Add Department Modal -->
    <div id="addDepartmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Add Department</h2>
                <button class="close-modal">&times;</button>
            </div>
            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Department Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline close-modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Department</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Department Modal -->
    <div id="editDepartmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Department</h2>
                <button class="close-modal">&times;</button>
            </div>
            <form method="POST" action="edit_department.php">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name">Department Name *</label>
                        <input type="text" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline close-modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Department Modal -->
    <div id="deleteDepartmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-trash-alt"></i> Delete Department</h2>
                <button class="close-modal">&times;</button>
            </div>
            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-body">
                    <p>Are you sure you want to delete the department: <strong id="delete_name"></strong>?</p>
                    <p class="text-danger">This action cannot be undone and may affect related data.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline close-modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal functionality
    const modals = document.querySelectorAll('.modal');
    const modalToggles = document.querySelectorAll('[data-toggle="modal"]');
    const closeButtons = document.querySelectorAll('.close-modal');
    
    modalToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetModal = document.getElementById(this.getAttribute('data-target'));
            targetModal.classList.add('show');
        });
    });
    
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            modal.classList.remove('show');
        });
    });
    
    modals.forEach(modal => {
        modal.addEventListener('click', function(event) {
            if (event.target === this) {
                this.classList.remove('show');
            }
        });
    });
    
    // Edit department
    const editButtons = document.querySelectorAll('.edit-department');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const description = this.getAttribute('data-description');
            
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            
            document.getElementById('editDepartmentModal').classList.add('show');
        });
    });
    
    // Delete department
    const deleteButtons = document.querySelectorAll('.delete-department');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = name;
            
            document.getElementById('deleteDepartmentModal').classList.add('show');
        });
    });
    
    // Alert auto-close
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('fade-out');
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
        
        const closeBtn = alert.querySelector('.close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                alert.classList.add('fade-out');
                setTimeout(() => {
                    alert.remove();
                }, 500);
            });
        }
    });
});
</script>

<?php require_once 'footer.php'; ?>
