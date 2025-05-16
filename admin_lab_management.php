<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle PC status changes
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $pc_id = $_POST['pc_id'];
    $new_status = $_POST['new_status'];
    
    $query = "UPDATE computer_stations SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $new_status, $pc_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "PC status updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating PC status: " . $conn->error;
    }
    
    // Redirect to prevent form resubmission
    header("Location: admin_lab_management.php?lab=" . urlencode($_GET['lab'] ?? ''));
    exit();
}

// Get all labs
$labs = $conn->query("SELECT * FROM computer_labs WHERE lab_number IN (524,526,528,530,542,544) ORDER BY lab_number");

// Get selected lab or default to first
$selected_lab = $_GET['lab'] ?? ($labs->num_rows > 0 ? $labs->fetch_assoc()['lab_number'] : null);

// Get PCs for selected lab
$computers = [];
if ($selected_lab) {
    $stmt = $conn->prepare("SELECT cs.*, u.firstname, u.lastname, r.purpose
                          FROM computer_stations cs
                          LEFT JOIN users u ON cs.current_user_id = u.idno
                          LEFT JOIN reservations r ON cs.reservation_id = r.id
                          WHERE cs.lab_id = (SELECT id FROM computer_labs WHERE lab_number = ?)
                          ORDER BY cs.pc_number");
    $stmt->bind_param("i", $selected_lab);
    $stmt->execute();
    $computers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Ensure we show all 25 PCs even if some aren't in database
    $pc_numbers = array_column($computers, 'pc_number');
    for ($i = 1; $i <= 25; $i++) {
        if (!in_array($i, $pc_numbers)) {
            $computers[] = [
                'id' => 0,
                'pc_number' => $i,
                'status' => 'available',
                'firstname' => '',
                'lastname' => '',
                'purpose' => ''
            ];
        }
    }
    
    // Sort by PC number
    usort($computers, function($a, $b) {
        return $a['pc_number'] - $b['pc_number'];
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Computer Lab Management</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* General Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, rgb(43, 77, 74) 0%, #16213e 100%);
            color: white;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: rgba(0, 0, 0, 0.8);
            padding: 20px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 100;
        }

        .sidebar h2 {
            text-align: center;
            color: white;
            margin-bottom: 20px;
            font-size: 22px;
            border-bottom: 2px solid #4cc9f0;
            padding-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 15px 0;
            position: relative;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: white;
            display: block;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 15px;
        }

        .sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .sidebar ul li a:hover {
            background: rgba(76, 201, 240, 0.2);
            transform: translateX(5px);
        }

        .sidebar ul li a.active {
            background: linear-gradient(90deg, rgba(76, 201, 240, 0.3) 0%, transparent 100%);
            border-left: 3px solid #4cc9f0;
        }

        /* Main Content */
        .main-content {
            margin-left: 270px;
            padding: 40px;
            width: calc(100% - 270px);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header h1 {
            font-size: 24px;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
        }

        /* Message Alerts */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 3px solid;
            font-size: 14px;
        }

        .success {
            background: rgba(40, 167, 69, 0.2);
            border-left-color: #28a745;
            color: #d4edda;
        }

        .error {
            background: rgba(220, 53, 69, 0.2);
            border-left-color: #dc3545;
            color: #f8d7da;
        }

        /* Lab List */
        .lab-list {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .lab-item {
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid rgba(76, 201, 240, 0.3);
            font-weight: 500;
        }

        .lab-item:hover {
            background: rgba(76, 201, 240, 0.2);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.1);
        }

        .lab-item.active {
            background: rgba(76, 201, 240, 0.3);
            border-color: #4cc9f0;
            color: white;
        }

        /* Lab Details */
        .lab-details {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 30px;
        }

        .lab-title {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .lab-title h2 {
            font-size: 22px;
            margin-bottom: 5px;
            color: #4cc9f0;
        }

        .lab-title p {
            color: #aaa;
            font-size: 14px;
        }

        /* PC Grid */
        .pc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .pc-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }

        .pc-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .pc-number {
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .pc-status {
            font-size: 13px;
            padding: 5px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .status-available {
            background: rgba(40, 167, 69, 0.2);
            color: #d4edda;
            border: 1px solid rgba(40, 167, 69, 0.5);
        }

        .status-occupied {
            background: rgba(220, 53, 69, 0.2);
            color: #f8d7da;
            border: 1px solid rgba(220, 53, 69, 0.5);
        }

        .status-maintenance {
            background: rgba(255, 193, 7, 0.2);
            color: #fff3cd;
            border: 1px solid rgba(255, 193, 7, 0.5);
        }

        .pc-user {
            margin-top: 10px;
            font-size: 12px;
            color: #aaa;
            line-height: 1.4;
        }

        .pc-user strong {
            color: #ddd;
        }

        /* Stats Container */
        .stats-container {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stat-item {
            text-align: center;
            flex: 1;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .stat-label {
            font-size: 13px;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Form Elements */
        select {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid rgba(76, 201, 240, 0.3);
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-family: 'Poppins', sans-serif;
            margin-top: 10px;
            transition: all 0.3s;
        }

        select:focus {
            outline: none;
            border-color: #4cc9f0;
            box-shadow: 0 0 0 2px rgba(76, 201, 240, 0.2);
        }

        hr {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin: 25px 0;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                padding: 15px 10px;
            }
            
            .sidebar h2 {
                font-size: 0;
                padding-bottom: 0;
                border: none;
            }
            
            .sidebar h2:after {
                content: "LM";
                font-size: 18px;
                display: block;
            }
            
            .sidebar ul li a span {
                display: none;
            }
            
            .sidebar ul li a i {
                margin-right: 0;
                font-size: 18px;
            }
            
            .main-content {
                margin-left: 90px;
                padding: 20px;
            }
            
            .pc-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .stats-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .lab-list {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Updated Sidebar -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <ul>
        <li><a href="admin_dashboard.php" ><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li><a href="admin_student_list.php"><i class="fas fa-users"></i> <span>Students</span></a></li>
        <li><a href="admin_announcement.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a></li>
        <li><a href="manage_sitins.php"><i class="fas fa-user-clock"></i> <span>Manage Sit-ins</span></a></li>
        <li><a href="sit_in_records.php"><i class="fas fa-history"></i> <span>Sit-in Records</span></a></li>
        <li><a href="leaderboard.php"><i class="fas fa-trophy"></i> <span>Leaderboard</span></a></li>
        <li><a href="feedback.php"><i class="fas fa-comment-alt"></i> <span>Feedback</span></a></li>
        <li><a href="lab_schedule.php"><i class="fas fa-calendar-alt"></i> <span>Lab Schedule</span></a></li>
        <li><a href="admin_upload_resource.php"><i class="fas fa-upload"></i> <span>Resources</span></a></li>
        <li><a href="admin_reservations.php"><i class="fas fa-calendar-check"></i> <span>Reservations</span></a></li>
        <li><a href="admin_lab_management.php"class="active"><i class="fas fa-laptop-house"></i> <span>Lab Management</span></a></li>
        <li><a href="#"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
        <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-laptop-code"></i> Computer Lab Management</h1>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message'] ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error_message'] ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="lab-list">
            <?php foreach ($labs as $lab): ?>
                <div class="lab-item <?= $selected_lab == $lab['lab_number'] ? 'active' : '' ?>"
                     onclick="window.location.href='admin_lab_management.php?lab=<?= $lab['lab_number'] ?>'">
                    <i class="fas fa-laptop"></i> Lab <?= $lab['lab_number'] ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($selected_lab): ?>
            <div class="lab-details">
                <div class="lab-title">
                    <h2><i class="fas fa-laptop-house"></i> Lab <?= $selected_lab ?></h2>
                    <p><?= $current_lab_stats['description'] ?? '' ?></p>
                </div>
                
                <div class="pc-grid">
                    <?php foreach ($computers as $pc): ?>
                        <div class="pc-item">
                            <div class="pc-number"><i class="fas fa-desktop"></i> PC <?= $pc['pc_number'] ?></div>
                            <div class="pc-status status-<?= $pc['status'] ?>">
                                <?= ucfirst($pc['status']) ?>
                            </div>
                            
                            <?php if ($pc['status'] == 'occupied' && !empty($pc['user_name'])): ?>
                                <div class="pc-user">
                                    <strong><i class="fas fa-user"></i> User:</strong> <?= $pc['user_name'] ?><br>
                                    <?php if (!empty($pc['time_in'])): ?>
                                        <strong><i class="fas fa-clock"></i> Since:</strong> <?= date('H:i', strtotime($pc['time_in'])) ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" style="margin-top: 15px;">
                                <input type="hidden" name="pc_id" value="<?= $pc['id'] ?>">
                                <select name="new_status" onchange="this.form.submit()">
                                    <option value="available" <?= $pc['status'] == 'available' ? 'selected' : '' ?>>Available</option>
                                    <option value="occupied" <?= $pc['status'] == 'occupied' ? 'selected' : '' ?>>Occupied</option>
                                    <option value="maintenance" <?= $pc['status'] == 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <hr>
                
                <div class="stats-container">
                    <div class="stat-item">
                        <div class="stat-value"><?= $current_lab_stats['total_pcs'] ?? 25 ?></div>
                        <div class="stat-label">Total PCs</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $current_lab_stats['available_pcs'] ?? 0 ?></div>
                        <div class="stat-label">Available</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $current_lab_stats['occupied_pcs'] ?? 0 ?></div>
                        <div class="stat-label">Occupied</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $current_lab_stats['maintenance_pcs'] ?? 0 ?></div>
                        <div class="stat-label">Maintenance</div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="lab-details">
                <p><i class="fas fa-info-circle"></i> No labs found. Please add labs first.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>