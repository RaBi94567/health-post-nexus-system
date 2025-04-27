
<?php
require_once 'header.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = sanitize_input($_POST['id']);
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    
    // Validate input
    if (empty($name) || empty($id)) {
        set_flash_message('error', 'Department name and ID are required');
        redirect('department.php');
    }
    
    // Check if department exists (except the current one)
    $stmt = $conn->prepare("SELECT id FROM departments WHERE name = :name AND id != :id");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        set_flash_message('error', 'Department name already exists');
        redirect('department.php');
    }
    
    // Update department
    $stmt = $conn->prepare("UPDATE departments SET name = :name, description = :description WHERE id = :id");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        set_flash_message('success', 'Department updated successfully');
    } else {
        set_flash_message('error', 'Failed to update department');
    }
    
    redirect('department.php');
} else {
    // If accessed directly without POST data, redirect to departments
    redirect('department.php');
}
?>
