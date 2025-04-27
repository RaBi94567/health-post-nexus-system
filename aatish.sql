
-- Create database (if needed)
-- CREATE DATABASE aatish;
-- USE aatish;

-- Users table for login/signup
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'doctor', 'staff', 'nurse') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    profile_image VARCHAR(255) DEFAULT 'default.jpg',
    last_login DATETIME
);

-- Departments table
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Employee table - extends users with employment details
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    emp_id VARCHAR(20) NOT NULL UNIQUE,
    department_id INT,
    position VARCHAR(100) NOT NULL,
    specialization VARCHAR(100),
    join_date DATE NOT NULL,
    salary DECIMAL(10, 2),
    contact_number VARCHAR(20),
    address TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- Patients table
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    dob DATE,
    gender ENUM('male', 'female', 'other'),
    contact_number VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    blood_group VARCHAR(5),
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Appointments table
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled', 'no-show') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES employees(id)
);

-- Shifts table
CREATE TABLE shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    description TEXT
);

-- Employee shifts assignment
CREATE TABLE employee_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    shift_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('assigned', 'completed', 'absent') DEFAULT 'assigned',
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (shift_id) REFERENCES shifts(id)
);

-- Attendance table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    time_in DATETIME,
    time_out DATETIME,
    status ENUM('present', 'absent', 'late', 'half-day', 'leave') NOT NULL,
    notes TEXT,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- Payroll table
CREATE TABLE payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    pay_period_start DATE NOT NULL,
    pay_period_end DATE NOT NULL,
    basic_salary DECIMAL(10, 2) NOT NULL,
    allowances DECIMAL(10, 2) DEFAULT 0,
    deductions DECIMAL(10, 2) DEFAULT 0,
    net_salary DECIMAL(10, 2) NOT NULL,
    payment_date DATE,
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- Medicine inventory
CREATE TABLE medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    generic_name VARCHAR(100),
    category VARCHAR(50),
    supplier VARCHAR(100),
    quantity INT NOT NULL DEFAULT 0,
    unit VARCHAR(20),
    price_per_unit DECIMAL(10, 2),
    expiry_date DATE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample departments
INSERT INTO departments (name, description) VALUES 
('Cardiology', 'Deals with disorders of the heart and circulatory system'),
('Dermatology', 'Focuses on diseases and conditions of the skin'),
('Orthopedics', 'Concentrates on diseases and injuries of the musculoskeletal system'),
('Pediatrics', 'Deals with medical care of children and adolescents'),
('General Medicine', 'Provides primary healthcare services');

-- Insert admin user (password: admin123)
INSERT INTO users (username, password, email, full_name, role) VALUES 
('admin', '$2y$10$6Y7.S4xMzfXzVcgUg0OZS.8QnJ3Uw4HHvH9HkQ1syBkcKJg4wccLK', 'admin@hpms.com', 'System Administrator', 'admin');

-- Insert sample employee data
INSERT INTO users (username, password, email, full_name, role, profile_image) VALUES
('emilycarter', '$2y$10$6Y7.S4xMzfXzVcgUg0OZS.8QnJ3Uw4HHvH9HkQ1syBkcKJg4wccLK', 'emily@hpms.com', 'Dr. Emily Carter', 'doctor', 'doctor1.jpg'),
('marcusreed', '$2y$10$6Y7.S4xMzfXzVcgUg0OZS.8QnJ3Uw4HHvH9HkQ1syBkcKJg4wccLK', 'marcus@hpms.com', 'Dr. Marcus Reed', 'doctor', 'doctor2.jpg'),
('nataliebrooks', '$2y$10$6Y7.S4xMzfXzVcgUg0OZS.8QnJ3Uw4HHvH9HkQ1syBkcKJg4wccLK', 'natalie@hpms.com', 'Dr. Natalie Brooks', 'doctor', 'doctor3.jpg');

-- Get the user IDs for the inserted users
-- (In a real scenario, you'd use the actual IDs, but for this demo we'll use fixed values)
INSERT INTO employees (user_id, emp_id, department_id, position, specialization, join_date, salary) VALUES
(2, 'DOC001', 2, 'Senior Doctor', 'Dermatologist', '2022-04-15', 85000.00),
(3, 'DOC002', 3, 'Senior Doctor', 'Orthopedic Surgeon', '2021-06-12', 90000.00),
(4, 'DOC003', 1, 'Doctor', 'Cardiologist', '2023-02-23', 80000.00);

-- Insert sample shifts
INSERT INTO shifts (name, start_time, end_time, description) VALUES
('Morning Shift', '08:00:00', '16:00:00', 'Regular morning shift'),
('Evening Shift', '16:00:00', '00:00:00', 'Regular evening shift'),
('Night Shift', '00:00:00', '08:00:00', 'Regular night shift');
