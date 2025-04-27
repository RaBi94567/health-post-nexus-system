
<?php
session_start();
require_once 'db.php';

// Check if there's a flash message
$flash_message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : null;
// Clear the flash message after retrieving it
if (isset($_SESSION['flash_message'])) {
    unset($_SESSION['flash_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Post Management System</title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico" type="image/x-icon">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php if (!is_logged_in() && basename($_SERVER['PHP_SELF']) != 'login.php' && basename($_SERVER['PHP_SELF']) != 'register.php'): ?>
        <?php redirect('login.php'); ?>
    <?php endif; ?>

    <?php if (is_logged_in()): ?>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-hospital-alt"></i>
                    <h2>HPMS</h2>
                </div>
                <div class="sidebar-toggle" id="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
            <div class="sidebar-menu">
                <ul>
                    <li class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                        <a href="index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : '' ?>">
                        <a href="employees.php">
                            <i class="fas fa-user-md"></i>
                            <span>Employees</span>
                        </a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) == 'department.php' ? 'active' : '' ?>">
                        <a href="department.php">
                            <i class="fas fa-hospital"></i>
                            <span>Department</span>
                        </a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) == 'appointment.php' ? 'active' : '' ?>">
                        <a href="appointment.php">
                            <i class="fas fa-calendar-check"></i>
                            <span>Appointment</span>
                        </a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) == 'shifts.php' ? 'active' : '' ?>">
                        <a href="shifts.php">
                            <i class="fas fa-clock"></i>
                            <span>Shifts</span>
                        </a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : '' ?>">
                        <a href="attendance.php">
                            <i class="fas fa-clipboard-check"></i>
                            <span>Attendance</span>
                        </a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) == 'payroll.php' ? 'active' : '' ?>">
                        <a href="payroll.php">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Payroll</span>
                        </a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) == 'medicine.php' ? 'active' : '' ?>">
                        <a href="medicine.php">
                            <i class="fas fa-pills"></i>
                            <span>Medicine</span>
                        </a>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navigation Bar -->
            <nav class="top-nav">
                <div class="top-nav-left">
                    <h2 class="page-title">
                        <?php 
                        $current_page = basename($_SERVER['PHP_SELF'], '.php');
                        echo ucfirst($current_page == 'index' ? 'Dashboard' : $current_page);
                        ?>
                    </h2>
                    <div class="search-wrapper">
                        <input type="text" placeholder="Search..." class="search-input">
                        <button class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="top-nav-right">
                    <div class="nav-item">
                        <a href="#" class="icon-btn">
                            <i class="fas fa-bell"></i>
                            <span class="badge">3</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="icon-btn">
                            <i class="fas fa-envelope"></i>
                            <span class="badge">5</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="icon-btn">
                            <i class="fas fa-cog"></i>
                        </a>
                    </div>
                    <div class="user-profile dropdown">
                        <div class="dropdown-toggle">
                            <?php if(isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image'])): ?>
                                <img src="assets/images/<?= $_SESSION['profile_image'] ?>" alt="Profile" class="avatar">
                            <?php else: ?>
                                <img src="assets/images/default.jpg" alt="Profile" class="avatar">
                            <?php endif; ?>
                            <span class="username"><?= $_SESSION['full_name'] ?? 'User' ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="dropdown-menu">
                            <a href="profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Flash Message -->
            <?php if ($flash_message): ?>
                <div class="alert alert-<?= $flash_message['type'] ?>">
                    <i class="fas fa-info-circle"></i>
                    <span><?= $flash_message['message'] ?></span>
                    <button class="close-btn"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>

            <!-- Page Content Container -->
            <div class="content-container">
    <?php endif; ?>
