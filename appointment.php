
<?php
require_once 'header.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get all doctors for dropdown
$stmt = $conn->prepare("
    SELECT e.id, u.full_name
    FROM employees e
    JOIN users u ON e.user_id = u.id
    WHERE u.role = 'doctor' AND e.status = 'active'
    ORDER BY u.full_name
");
$stmt->execute();
$doctors = $stmt->fetchAll();

// Get all appointments
$stmt = $conn->prepare("
    SELECT a.*, p.first_name, p.last_name, u.full_name as doctor_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN employees e ON a.doctor_id = e.id
    JOIN users u ON e.user_id = u.id
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$stmt->execute();
$appointments = $stmt->fetchAll();

// Process add appointment form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    // Check if patient exists or create new
    $patient_id = null;
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $dob = sanitize_input($_POST['dob']);
    $gender = sanitize_input($_POST['gender']);
    $contact_number = sanitize_input($_POST['contact_number']);
    $email = sanitize_input($_POST['email']);
    
    if (empty($first_name) || empty($last_name) || empty($contact_number)) {
        set_flash_message('error', 'Patient first name, last name, and contact are required');
    } else {
        // Check if patient already exists
        $stmt = $conn->prepare("SELECT id FROM patients WHERE contact_number = :contact_number OR email = :email");
        $stmt->bindParam(':contact_number', $contact_number);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $patient = $stmt->fetch();
            $patient_id = $patient['id'];
            
            // Update patient info
            $stmt = $conn->prepare("UPDATE patients SET first_name = :first_name, last_name = :last_name, dob = :dob, gender = :gender, email = :email WHERE id = :id");
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':dob', $dob);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $patient_id);
            $stmt->execute();
        } else {
            // Generate patient ID
            $patient_code = 'PT' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Add new patient
            $stmt = $conn->prepare("INSERT INTO patients (patient_id, first_name, last_name, dob, gender, contact_number, email) VALUES (:patient_id, :first_name, :last_name, :dob, :gender, :contact_number, :email)");
            $stmt->bindParam(':patient_id', $patient_code);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':dob', $dob);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':contact_number', $contact_number);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $patient_id = $conn->lastInsertId();
        }
        
        // Add appointment
        $doctor_id = sanitize_input($_POST['doctor_id']);
        $appointment_date = sanitize_input($_POST['appointment_date']);
        $appointment_time = sanitize_input($_POST['appointment_time']);
        $notes = sanitize_input($_POST['notes']);
        
        if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time)) {
            set_flash_message('error', 'Doctor, appointment date, and time are required');
        } else {
            // Check for conflicting appointments
            $stmt = $conn->prepare("
                SELECT id FROM appointments 
                WHERE doctor_id = :doctor_id 
                AND appointment_date = :appointment_date 
                AND appointment_time = :appointment_time 
                AND status != 'cancelled'
            ");
            $stmt->bindParam(':doctor_id', $doctor_id);
            $stmt->bindParam(':appointment_date', $appointment_date);
            $stmt->bindParam(':appointment_time', $appointment_time);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                set_flash_message('error', 'This time slot is already booked');
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, notes) 
                    VALUES (:patient_id, :doctor_id, :appointment_date, :appointment_time, :notes)
                ");
                $stmt->bindParam(':patient_id', $patient_id);
                $stmt->bindParam(':doctor_id', $doctor_id);
                $stmt->bindParam(':appointment_date', $appointment_date);
                $stmt->bindParam(':appointment_time', $appointment_time);
                $stmt->bindParam(':notes', $notes);
                
                if ($stmt->execute()) {
                    set_flash_message('success', 'Appointment scheduled successfully');
                    redirect('appointment.php');
                } else {
                    set_flash_message('error', 'Failed to schedule appointment');
                }
            }
        }
    }
}

// Process update appointment status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $appointment_id = sanitize_input($_POST['appointment_id']);
    $status = sanitize_input($_POST['status']);
    
    $stmt = $conn->prepare("UPDATE appointments SET status = :status WHERE id = :id");
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $appointment_id);
    
    if ($stmt->execute()) {
        set_flash_message('success', 'Appointment status updated');
        redirect('appointment.php');
    } else {
        set_flash_message('error', 'Failed to update appointment status');
    }
}
?>

<div class="appointment-page">
    <div class="page-header">
        <h1><i class="fas fa-calendar-check"></i> Appointment Management</h1>
        <div class="page-actions">
            <button class="btn btn-primary" data-toggle="modal" data-target="addAppointmentModal">
                <i class="fas fa-plus"></i> New Appointment
            </button>
        </div>
    </div>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?>">
            <?= $_SESSION['flash_message']['message'] ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
    
    <!-- Tab Navigation -->
    <div class="tab-navigation">
        <button class="tab-btn active" data-tab="upcoming">
            <i class="fas fa-clock"></i> Upcoming
        </button>
        <button class="tab-btn" data-tab="today">
            <i class="fas fa-calendar-day"></i> Today
        </button>
        <button class="tab-btn" data-tab="past">
            <i class="fas fa-history"></i> Past
        </button>
        <button class="tab-btn" data-tab="all">
            <i class="fas fa-calendar-alt"></i> All
        </button>
    </div>
    
    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Appointments Table -->
        <div class="table-container">
            <table class="data-table appointments-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="tab-pane active" id="upcoming">
                    <?php
                    $today = date('Y-m-d');
                    $upcoming_count = 0;
                    
                    foreach ($appointments as $appointment):
                        if ($appointment['appointment_date'] >= $today && $appointment['status'] !== 'completed' && $appointment['status'] !== 'cancelled'):
                            $upcoming_count++;
                    ?>
                    <tr>
                        <td><?= $appointment['first_name'] . ' ' . $appointment['last_name'] ?></td>
                        <td><?= $appointment['doctor_name'] ?></td>
                        <td><?= date('M d, Y', strtotime($appointment['appointment_date'])) ?></td>
                        <td><?= date('h:i A', strtotime($appointment['appointment_time'])) ?></td>
                        <td><span class="status-badge <?= $appointment['status'] ?>"><?= ucfirst($appointment['status']) ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-icon" title="View Details" data-toggle="modal" data-target="viewAppointmentModal" data-id="<?= $appointment['id'] ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-icon update-status" title="Update Status" data-id="<?= $appointment['id'] ?>">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php
                        endif;
                    endforeach;
                    
                    if ($upcoming_count === 0):
                    ?>
                    <tr>
                        <td colspan="6" class="empty-data">No upcoming appointments</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                
                <tbody class="tab-pane" id="today">
                    <?php
                    $today = date('Y-m-d');
                    $today_count = 0;
                    
                    foreach ($appointments as $appointment):
                        if ($appointment['appointment_date'] === $today):
                            $today_count++;
                    ?>
                    <tr>
                        <td><?= $appointment['first_name'] . ' ' . $appointment['last_name'] ?></td>
                        <td><?= $appointment['doctor_name'] ?></td>
                        <td><?= date('M d, Y', strtotime($appointment['appointment_date'])) ?></td>
                        <td><?= date('h:i A', strtotime($appointment['appointment_time'])) ?></td>
                        <td><span class="status-badge <?= $appointment['status'] ?>"><?= ucfirst($appointment['status']) ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-icon" title="View Details" data-toggle="modal" data-target="viewAppointmentModal" data-id="<?= $appointment['id'] ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-icon update-status" title="Update Status" data-id="<?= $appointment['id'] ?>">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php
                        endif;
                    endforeach;
                    
                    if ($today_count === 0):
                    ?>
                    <tr>
                        <td colspan="6" class="empty-data">No appointments scheduled for today</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                
                <tbody class="tab-pane" id="past">
                    <?php
                    $today = date('Y-m-d');
                    $past_count = 0;
                    
                    foreach ($appointments as $appointment):
                        if ($appointment['appointment_date'] < $today || $appointment['status'] === 'completed' || $appointment['status'] === 'cancelled'):
                            $past_count++;
                    ?>
                    <tr>
                        <td><?= $appointment['first_name'] . ' ' . $appointment['last_name'] ?></td>
                        <td><?= $appointment['doctor_name'] ?></td>
                        <td><?= date('M d, Y', strtotime($appointment['appointment_date'])) ?></td>
                        <td><?= date('h:i A', strtotime($appointment['appointment_time'])) ?></td>
                        <td><span class="status-badge <?= $appointment['status'] ?>"><?= ucfirst($appointment['status']) ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-icon" title="View Details" data-toggle="modal" data-target="viewAppointmentModal" data-id="<?= $appointment['id'] ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php
                        endif;
                    endforeach;
                    
                    if ($past_count === 0):
                    ?>
                    <tr>
                        <td colspan="6" class="empty-data">No past appointments</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                
                <tbody class="tab-pane" id="all">
                    <?php if (count($appointments) > 0): ?>
                        <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td><?= $appointment['first_name'] . ' ' . $appointment['last_name'] ?></td>
                            <td><?= $appointment['doctor_name'] ?></td>
                            <td><?= date('M d, Y', strtotime($appointment['appointment_date'])) ?></td>
                            <td><?= date('h:i A', strtotime($appointment['appointment_time'])) ?></td>
                            <td><span class="status-badge <?= $appointment['status'] ?>"><?= ucfirst($appointment['status']) ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-icon" title="View Details" data-toggle="modal" data-target="viewAppointmentModal" data-id="<?= $appointment['id'] ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($appointment['status'] !== 'completed' && $appointment['status'] !== 'cancelled'): ?>
                                <button class="btn btn-sm btn-icon update-status" title="Update Status" data-id="<?= $appointment['id'] ?>">
                                    <i class="fas fa-exchange-alt"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-data">No appointments found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add Appointment Modal -->
    <div id="addAppointmentModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2><i class="fas fa-calendar-plus"></i> New Appointment</h2>
                <button class="close-modal">&times;</button>
            </div>
            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-section">
                        <h3>Patient Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" required>
                            </div>
                            <div class="form-group">
                                <label for="dob">Date of Birth</label>
                                <input type="date" id="dob" name="dob">
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="contact_number">Contact Number *</label>
                                <input type="tel" id="contact_number" name="contact_number" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Appointment Details</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="doctor_id">Doctor *</label>
                                <select id="doctor_id" name="doctor_id" required>
                                    <option value="">Select Doctor</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <option value="<?= $doctor['id'] ?>"><?= $doctor['full_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="appointment_date">Appointment Date *</label>
                                <input type="date" id="appointment_date" name="appointment_date" required min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="form-group">
                                <label for="appointment_time">Appointment Time *</label>
                                <input type="time" id="appointment_time" name="appointment_time" required>
                            </div>
                            <div class="form-group">
                                <label for="notes">Notes</label>
                                <textarea id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline close-modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule Appointment</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div id="updateStatusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-exchange-alt"></i> Update Appointment Status</h2>
                <button class="close-modal">&times;</button>
            </div>
            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="appointment_id" id="update_appointment_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="no-show">No Show</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline close-modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
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
    
    // Update status functionality
    const updateStatusButtons = document.querySelectorAll('.update-status');
    updateStatusButtons.forEach(button => {
        button.addEventListener('click', function() {
            const appointmentId = this.getAttribute('data-id');
            document.getElementById('update_appointment_id').value = appointmentId;
            document.getElementById('updateStatusModal').classList.add('show');
        });
    });
    
    // Set min date for appointment date
    document.getElementById('appointment_date').min = new Date().toISOString().split('T')[0];
});
</script>

<?php require_once 'footer.php'; ?>
