
<?php
// Database connection parameters
$host = "localhost";
$dbname = "aatish";
$username = "root";
$password = ""; // Change this to your database password if needed

// Create connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to check if user is admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to redirect to a specific page
function redirect($page) {
    header("Location: $page");
    exit;
}

// Function to display flash message
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Function to get employee count by status and role
function get_employee_count($status = null, $role = null) {
    global $conn;
    
    $query = "SELECT COUNT(*) as count FROM employees e JOIN users u ON e.user_id = u.id WHERE 1=1";
    
    if ($status) {
        $query .= " AND e.status = :status";
    }
    
    if ($role) {
        $query .= " AND u.role = :role";
    }
    
    $stmt = $conn->prepare($query);
    
    if ($status) {
        $stmt->bindParam(':status', $status);
    }
    
    if ($role) {
        $stmt->bindParam(':role', $role);
    }
    
    $stmt->execute();
    $result = $stmt->fetch();
    
    return $result['count'];
}
?>
