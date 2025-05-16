<?php
require 'config.php';

header('Content-Type: application/json');

$lab_number = $_GET['lab_number'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

$response = ['pcs' => []];

if ($lab_number) {
    // Get all PCs for this lab
    $query = "SELECT cs.pc_number as number, 
              CASE WHEN r.id IS NOT NULL THEN 'used' ELSE 'available' END as status
              FROM computer_stations cs
              LEFT JOIN reservations r ON cs.reservation_id = r.id 
                  AND r.date = ?
                  AND r.status = 'Approved'
              WHERE cs.lab_id = (SELECT id FROM computer_labs WHERE lab_number = ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $date, $lab_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $response['pcs'][] = $row;
    }
}

echo json_encode($response);
?>