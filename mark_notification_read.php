<?php
require 'config.php';
session_start();

if (!isset($_SESSION['username']) || !isset($_GET['id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

$idno = $_SESSION['student_id'] ?? '';
$notif_id = $_GET['id'];

try {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("is", $notif_id, $idno);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}