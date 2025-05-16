<?php
session_start(); // Starxat session for user authentication

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "sitin_monitoring";

// Enable error reporting for development
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Create a new connection
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset("utf8mb4"); // Ensure correct charset

} catch (Exception $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Could not connect to the database. Please try again later.");
}

// Function to check if user is logged in
function check_auth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}
?>

