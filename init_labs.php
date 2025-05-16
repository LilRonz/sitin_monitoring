<?php
require 'config.php';

// Insert labs
$labs = [
    ['524', 'Programming Lab 1'],
    ['526', 'Programming Lab 2'],
    ['528', 'Networking Lab'],
    ['530', 'Hardware Lab'],
    ['542', 'Research Lab'],
    ['544', 'Multimedia Lab']
];

foreach ($labs as $lab) {
    $stmt = $conn->prepare("INSERT INTO computer_labs (lab_number, description, capacity) VALUES (?, ?, 25)");
    $stmt->bind_param("ss", $lab[0], $lab[1]);
    $stmt->execute();
}

// Insert computers for each lab (PC1-PC25)
$labs = $conn->query("SELECT id, lab_number FROM computer_labs");
while ($lab = $labs->fetch_assoc()) {
    for ($i = 1; $i <= 25; $i++) {
        $pc_number = "PC" . $i;
        $stmt = $conn->prepare("INSERT INTO computer_stations (lab_id, pc_number) VALUES (?, ?)");
        $stmt->bind_param("is", $lab['id'], $pc_number);
        $stmt->execute();
    }
}

echo "Labs and computers initialized successfully!";