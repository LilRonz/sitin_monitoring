<?php
session_start(); // Start session

// Check if the admin is logged in
if (isset($_SESSION['admin'])) {
    // Destroy admin session
    session_unset();
    session_destroy();
    
    // Redirect to admin login page
    header("Location: admin_login.php");
    exit();
} else {
    // Redirect to admin login if no session is found
    header("Location: admin_login.php");
    exit();
}
?>
