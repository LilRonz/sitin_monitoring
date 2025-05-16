<?php
include 'config.php';

header('Content-Type: application/json');

// Get all lab statuses from session (or database in production)
$response = [
    'status' => 'success',
    'labs' => $_SESSION['lab_status'] ?? []
];

echo json_encode($response);
?>