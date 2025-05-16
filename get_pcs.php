<?php
require 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['lab_number'])) {
    echo json_encode(['error' => 'Lab number not provided']);
    exit;
}

$lab_number = (int)$_GET['lab_number'];

try {
    $stmt = $conn->prepare("
        SELECT cs.pc_number, cs.status 
        FROM computer_stations cs
        JOIN computer_labs cl ON cs.lab_id = cl.id
        WHERE cl.lab_number = ?
        ORDER BY cs.pc_number
    ");
    $stmt->bind_param("i", $lab_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pcs = [];
    while ($row = $result->fetch_assoc()) {
        $pcs[] = [
            'pc_number' => $row['pc_number'],
            'status' => $row['status']
        ];
    }
    
    echo json_encode(['pcs' => $pcs]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}