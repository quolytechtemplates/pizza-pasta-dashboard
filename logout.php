<?php
session_start();

// Log activity before destroying session
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    logActivity($_SESSION['user_id'], 'Logout', 'User logged out');
}

// Destroy session
session_destroy();

// Redirect to login
header("Location: login.php");
exit();
?>
