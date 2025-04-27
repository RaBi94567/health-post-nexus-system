
<?php
require_once 'header.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get dashboard statistics
$total_staff = get_employee_count(null, 'staff');
$active_staff = get_employee_count('active', 'staff');
$total_doctors = get_employee_count(null, 'doctor');
$active_doctors = get_employee_count('active', 'doctor');

// Get recent appointments
$stmt = $conn->prepare("
    SELECT a.*, p.first_name as patient_first_name, p.last_name as patient_last_name,
    CONCAT(u.full_name) as doctor_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN employees e ON a.doctor_id = e.id
    JOIN users u ON e.user_id = u.id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 5
");
$stmt->execute();
$recent_appointments = $stmt->fetchAll();
?>

<!-- Dashboard content -->
<div class="dashboard">
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
    
    <!-- Dashboard Widgets -->
    <div class="dashboard-widgets">
        <div class="widget">
            <div class="widget-header">
                <h3><i class="fas fa-calendar-check"></i> Today's Appointments</h3>
                <a href="appointment.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="widget-content">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_appointments) > 0): ?>
                            <?php foreach ($recent_appointments as $appointment): ?>
                                <tr>
                                    <td><?= $appointment['patient_first_name'] . ' ' . $appointment['patient_last_name'] ?></td>
                                    <td><?= $appointment['doctor_name'] ?></td>
                                    <td><?= date('H:i', strtotime($appointment['appointment_time'])) ?></td>
                                    <td><span class="status-badge <?= $appointment['status'] ?>"><?= ucfirst($appointment['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-data">No appointments for today</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="widget">
            <div class="widget-header">
                <h3><i class="fas fa-chart-line"></i> Patient Statistics</h3>
                <div class="widget-actions">
                    <select class="form-control">
                        <option value="week">This Week</option>
                        <option value="month" selected>This Month</option>
                        <option value="year">This Year</option>
                    </select>
                </div>
            </div>
            <div class="widget-content">
                <canvas id="patientChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="dashboard-widgets">
        <div class="widget">
            <div class="widget-header">
                <h3><i class="fas fa-user-md"></i> Doctors On Duty</h3>
                <a href="employees.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="widget-content">
                <div class="doctor-cards">
                    <?php
                    // Get doctors on duty today
                    $stmt = $conn->prepare("
                        SELECT u.full_name, u.profile_image, e.position, d.name as department_name
                        FROM users u 
                        JOIN employees e ON u.id = e.user_id 
                        JOIN departments d ON e.department_id = d.id
                        WHERE u.role = 'doctor' AND e.status = 'active'
                        LIMIT 4
                    ");
                    $stmt->execute();
                    $doctors = $stmt->fetchAll();
                    
                    foreach ($doctors as $doctor):
                    ?>
                    <div class="doctor-card">
                        <img src="assets/images/<?= !empty($doctor['profile_image']) ? $doctor['profile_image'] : 'default.jpg' ?>" alt="Doctor" class="doctor-img">
                        <div class="doctor-info">
                            <h4><?= $doctor['full_name'] ?></h4>
                            <p><?= $doctor['position'] ?></p>
                            <span class="department"><?= $doctor['department_name'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="widget">
            <div class="widget-header">
                <h3><i class="fas fa-bell"></i> Recent Notifications</h3>
                <a href="#" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="widget-content">
                <ul class="notification-list">
                    <li class="notification-item unread">
                        <div class="notification-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="notification-content">
                            <p>New patient registered: <strong>John Smith</strong></p>
                            <span class="notification-time">30 minutes ago</span>
                        </div>
                        <div class="notification-actions">
                            <button class="btn btn-sm btn-icon"><i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-icon"><i class="fas fa-times"></i></button>
                        </div>
                    </li>
                    <li class="notification-item">
                        <div class="notification-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="notification-content">
                            <p>Appointment rescheduled: <strong>Sarah Johnson</strong></p>
                            <span class="notification-time">2 hours ago</span>
                        </div>
                        <div class="notification-actions">
                            <button class="btn btn-sm btn-icon"><i class="fas fa-check"></i></button>
                        </div>
                    </li>
                    <li class="notification-item">
                        <div class="notification-icon">
                            <i class="fas fa-pills"></i>
                        </div>
                        <div class="notification-content">
                            <p><strong>Amoxicillin</strong> stock is low. Please reorder.</p>
                            <span class="notification-time">5 hours ago</span>
                        </div>
                        <div class="notification-actions">
                            <button class="btn btn-sm btn-icon"><i class="fas fa-check"></i></button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Add Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Patient statistics chart
    const ctx = document.getElementById('patientChart').getContext('2d');
    const patientChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
                label: 'New Patients',
                data: [12, 19, 15, 17],
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                tension: 0.3
            }, {
                label: 'Appointments',
                data: [24, 38, 29, 35],
                backgroundColor: 'rgba(153, 102, 255, 0.2)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 2,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<?php require_once 'footer.php'; ?>
