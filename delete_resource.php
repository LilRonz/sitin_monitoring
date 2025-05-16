<?php
include 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

// Verify CSRF token
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: admin_upload_resource.php?error=Invalid CSRF token");
    exit();
}

// Check if resource ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_upload_resource.php?error=Invalid resource ID");
    exit();
}

$resource_id = $_GET['id'];

// Fetch resource details to get file path
$stmt = $conn->prepare("SELECT file_path, uploaded_by FROM resources WHERE id = ?");
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Resource doesn't exist
    header("Location: admin_upload_resource.php?error=Resource not found");
    exit();
}

$resource = $result->fetch_assoc();

// Optional: Check if current user is the uploader or has admin privileges
// Uncomment these lines if you want to implement permission checks
/*
if ($resource['uploaded_by'] != $_SESSION['admin_id'] && $_SESSION['admin_role'] != 'super_admin') {
    header("Location: admin_upload_resource.php?error=You don't have permission to delete this resource");
    exit();
}
*/

// Delete the file from server
if (file_exists($resource['file_path'])) {
    if (!unlink($resource['file_path'])) {
        header("Location: admin_upload_resource.php?error=Failed to delete file from server");
        exit();
    }
}

// Delete record from database
$stmt = $conn->prepare("DELETE FROM resources WHERE id = ?");
$stmt->bind_param("i", $resource_id);

if ($stmt->execute()) {
    header("Location: admin_upload_resource.php?success=Resource deleted successfully");
} else {
    header("Location: admin_upload_resource.php?error=Failed to delete resource from database");
}

exit();
?>