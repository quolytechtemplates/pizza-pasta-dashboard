<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'school_management');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for Albanian characters
$conn->set_charset("utf8mb4");

// Timezone
date_default_timezone_set('Europe/Tirane');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL
define('BASE_URL', '/school_management/');

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Helper function to check user role
function checkRole($allowed_roles) {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
    
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: " . BASE_URL . "unauthorized.php");
        exit();
    }
}

// Helper function to redirect based on role
function redirectToDashboard() {
    $role = $_SESSION['role'];
    switch($role) {
        case 'admin':
            header("Location: " . BASE_URL . "admin/dashboard.php");
            break;
        case 'teacher':
            header("Location: " . BASE_URL . "teacher/dashboard.php");
            break;
        case 'student':
            header("Location: " . BASE_URL . "student/dashboard.php");
            break;
        case 'parent':
            header("Location: " . BASE_URL . "parent/dashboard.php");
            break;
        default:
            header("Location: " . BASE_URL . "login.php");
    }
    exit();
}

// Log activity
function logActivity($user_id, $action, $description = null) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $action, $description, $ip);
    $stmt->execute();
    $stmt->close();
}

// Create notification
function createNotification($user_id, $title, $message, $type = 'info') {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $title, $message, $type);
    $stmt->execute();
    $stmt->close();
}

// Get unread notification count
function getUnreadNotificationCount($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'];
}

// Sanitize input
function sanitize($data) {
    global $conn;
    return htmlspecialchars(strip_tags(trim($data)));
}
?>
