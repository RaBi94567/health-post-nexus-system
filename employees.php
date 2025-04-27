
<?php
require_once 'header.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get all employees with their details
$stmt = $conn->prepare("
    SELECT e.*, u.full_name, u.email, u.role, u.profile_image, d.name as department_name
    FROM employees e
    JOIN users u ON e.user_id = u.id
    LEFT JOIN departments d ON e.department_id = d.id
    ORDER BY e.id DESC
");
$stmt->execute();
$employees = $stmt->fetchAll();

// Get counts
$total_staff = get_employee_count(null, 'staff');
$active_staff = get_employee_count('active', 'staff');
$total_doctors = get_employee_count(null, 'doctor');
$active_doctors = get_employee_count('active', 'doctor');
?>

<!-- Employees page content -->
<div class="employees-page">
    <!-- Stats Cards -->
    <div class="stats-cards">
        <div class="stat-card purple">
            <div class="stat-info">
                <h3>Total Staff</h3>
                <h2><?= $total_staff ?></h2>
            </div>
            <div class="stat-icon">
                <i class="fas fa-user-nurse"></i>
            </div>
        </div>
        
        <div class="stat-card green">
            <div class="stat-info">
                <h3>Active Staff</h3>
                <h2><?= $active_staff ?></h2>
            </div>
            <div class="stat-icon">
                <i class="fas fa-user-check"></i>
            </div>
        </div>
        
        <div class="stat-card blue">
            <div class="stat-info">
                <h3>Total Doctor</h3>
                <h2><?= $total_doctors ?></h2>
            </div>
            <div class="stat-icon">
                <i class="fas fa-user-md"></i>
            </div>
        </div>
        
        <div class="stat-card light-blue">
            <div class="stat-info">
                <h3>Active Doctor</h3>
                <h2><?= $active_doctors ?></h2>
            </div>
            <div class="stat-icon">
                <i class="fas fa-stethoscope"></i>
            </div>
        </div>
    </div>
    
    <!-- Search and Action Bar -->
    <div class="action-bar">
        <div class="search-container">
            <input type="text" id="employeeSearch" placeholder="Search employees..." class="search-input">
            <button class="search-btn"><i class="fas fa-search"></i></button>
        </div>
        <div class="action-buttons">
            <a href="add_employee.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Employee
            </a>
            <button class="btn btn-danger">
                <i class="fas fa-trash-alt"></i> Remove Employee
            </button>
        </div>
    </div>
    
    <!-- Tab Navigation -->
    <div class="tab-navigation">
        <button class="tab-btn active" data-tab="doctors">
            <i class="fas fa-user-md"></i> Doctor
        </button>
        <button class="tab-btn" data-tab="staff">
            <i class="fas fa-user-nurse"></i> Staff
        </button>
    </div>
    
    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Doctors Tab -->
        <div class="tab-pane active" id="doctors">
            <div class="employee-cards">
                <?php
                $doctorCount = 0;
                foreach ($employees as $employee):
                    if ($employee['role'] === 'doctor'):
                        $doctorCount++;
                ?>
                <div class="employee-card">
                    <div class="employee-image">
                        <img src="assets/images/<?= !empty($employee['profile_image']) ? $employee['profile_image'] : 'default.jpg' ?>" alt="<?= $employee['full_name'] ?>">
                    </div>
                    <div class="employee-id">EMP ID: <?= $employee['emp_id'] ?></div>
                    <div class="employee-name"><?= $employee['full_name'] ?></div>
                    <div class="employee-position"><?= $employee['position'] ?></div>
                    <div class="employee-details">
                        <div>Joined: <?= date('d F Y', strtotime($employee['join_date'])) ?></div>
                        <div>Dept: <?= $employee['department_name'] ?></div>
                    </div>
                    <div class="employee-actions">
                        <a href="edit_employee.php?id=<?= $employee['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i> Edit</a>
                        <a href="view_employee.php?id=<?= $employee['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> View</a>
                    </div>
                </div>
                <?php
                    endif;
                endforeach;
                
                if ($doctorCount === 0):
                ?>
                <div class="empty-state">
                    <i class="fas fa-user-md"></i>
                    <p>No doctors found</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Staff Tab -->
        <div class="tab-pane" id="staff">
            <div class="employee-cards">
                <?php
                $staffCount = 0;
                foreach ($employees as $employee):
                    if ($employee['role'] === 'staff' || $employee['role'] === 'nurse'):
                        $staffCount++;
                ?>
                <div class="employee-card">
                    <div class="employee-image">
                        <img src="assets/images/<?= !empty($employee['profile_image']) ? $employee['profile_image'] : 'default.jpg' ?>" alt="<?= $employee['full_name'] ?>">
                    </div>
                    <div class="employee-id">EMP ID: <?= $employee['emp_id'] ?></div>
                    <div class="employee-name"><?= $employee['full_name'] ?></div>
                    <div class="employee-position"><?= $employee['position'] ?></div>
                    <div class="employee-details">
                        <div>Joined: <?= date('d F Y', strtotime($employee['join_date'])) ?></div>
                        <div>Dept: <?= $employee['department_name'] ?></div>
                    </div>
                    <div class="employee-actions">
                        <a href="edit_employee.php?id=<?= $employee['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i> Edit</a>
                        <a href="view_employee.php?id=<?= $employee['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> View</a>
                    </div>
                </div>
                <?php
                    endif;
                endforeach;
                
                if ($staffCount === 0):
                ?>
                <div class="empty-state">
                    <i class="fas fa-user-nurse"></i>
                    <p>No staff found</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons and panes
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Add active class to clicked button and corresponding pane
            this.classList.add('active');
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Search functionality
    const searchInput = document.getElementById('employeeSearch');
    const employeeCards = document.querySelectorAll('.employee-card');
    
    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        
        employeeCards.forEach(card => {
            const name = card.querySelector('.employee-name').textContent.toLowerCase();
            const id = card.querySelector('.employee-id').textContent.toLowerCase();
            const position = card.querySelector('.employee-position').textContent.toLowerCase();
            
            if (name.includes(searchTerm) || id.includes(searchTerm) || position.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
});
</script>

<?php require_once 'footer.php'; ?>
