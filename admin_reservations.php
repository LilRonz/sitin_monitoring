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

// Handle approval/disapproval
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve'])) {
        $reservation_id = $_POST['reservation_id'];
        
        $conn->begin_transaction();
        try {
            // 1. Get full reservation details
            $res_query = "SELECT r.*, l.id AS lab_id 
                         FROM reservations r
                         JOIN computer_labs l ON r.lab_number = l.lab_number
                         WHERE r.id = ?";
            $stmt = $conn->prepare($res_query);
            $stmt->bind_param("i", $reservation_id);
            $stmt->execute();
            $reservation = $stmt->get_result()->fetch_assoc();
            
            if (!$reservation) {
                throw new Exception("Reservation not found");
            }
            
            // 2. Update reservation status
            $update_res = "UPDATE reservations SET status = 'Approved' WHERE id = ?";
            $stmt = $conn->prepare($update_res);
            $stmt->bind_param("i", $reservation_id);
            $stmt->execute();
            
            // 3. Update PC status
            $update_pc = "UPDATE computer_stations 
                         SET status = 'occupied',
                             current_user_id = ?,
                             reservation_id = ?
                         WHERE lab_id = ? AND pc_number = ?";
            $stmt = $conn->prepare($update_pc);
            $stmt->bind_param("siii", 
                $reservation['student_id'],
                $reservation_id,
                $reservation['lab_id'],
                $reservation['pc_number']
            );
            $stmt->execute();

             // 4. Check if student is already sitting in
            $check_query = "SELECT id FROM admin_sitin WHERE idno = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("s", $reservation['student_id']);
            $stmt->execute();

             if ($stmt->get_result()->num_rows == 0) {
                // 5. Add to manage_sitin table
                $insert_query = "INSERT INTO admin_sitin (idno, lab_classroom, purpose, time_in) 
                                VALUES (?, ?, ?, NOW())";
                $stmt = $conn->prepare($insert_query);
                $lab_classroom = "Lab " . $reservation['lab_number'];
                $stmt->bind_param("sss", $reservation['student_id'], $lab_classroom, $reservation['purpose']);
                $stmt->execute();
            }
            
            // 6. Add notification
            $notification_msg = "Your reservation for Lab {$reservation['lab_number']} PC {$reservation['pc_number']} has been approved!";
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, related_id) VALUES (?, ?, ?)");
            $notif_stmt->bind_param("ssi", $reservation['student_id'], $notification_msg, $reservation_id);
            $notif_stmt->execute();
            
            $conn->commit();
            $_SESSION['success_message'] = "Reservation approved and notification sent!";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
        }
        
        header("Location: admin_reservations.php");
        exit();
        
   } elseif (isset($_POST['disapprove'])) {
    $reservation_id = $_POST['reservation_id'];
    
    // Start transaction
    $conn->begin_transaction();
    try {
        // 1. Get reservation details first
        $res_query = "SELECT * FROM reservations WHERE id = ?";
        $stmt = $conn->prepare($res_query);
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $reservation = $stmt->get_result()->fetch_assoc();
        
        if (!$reservation) {
            throw new Exception("Reservation not found");
        }
        
        // 2. Update reservation status - CHANGED FROM 'Disapproved' TO 'Rejected'
        $update_res = "UPDATE reservations SET status = 'Rejected' WHERE id = ?";
        $stmt = $conn->prepare($update_res);
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        
        // 3. Return the session
        $stmt = $conn->prepare("UPDATE users SET remaining_sessions = remaining_sessions + 0
                              WHERE idno = ?");
        $stmt->bind_param("s", $reservation['student_id']);
        $stmt->execute();
        
        // 4. Add notification - CHANGED MESSAGE FROM 'disapproved' TO 'rejected'
        $notification_msg = "Your reservation for Lab {$reservation['lab_number']} PC {$reservation['pc_number']} has been rejected.";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, related_id) VALUES (?, ?, ?)");
        $notif_stmt->bind_param("ssi", $reservation['student_id'], $notification_msg, $reservation_id);
        $notif_stmt->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "Reservation rejected and notification sent!"; // CHANGED MESSAGE
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
    
    header("Location: admin_reservations.php");
    exit();
}
}

// Get all pending and approved reservations
$query = "SELECT r.*, u.remaining_sessions 
          FROM reservations r
          JOIN users u ON r.student_id = u.idno
          WHERE r.status IN ('Pending', 'Approved', 'Rejected')  /* Added Rejected if needed */
          ORDER BY r.date, r.time_in";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Reservations</title>
    <style>
        /* Global Styling */
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

        /* Sidebar - Matching Dashboard Style */
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

        /* Welcome Header - Matching Dashboard */
        .welcome-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .welcome-header h1 {
            font-size: 28px;
            font-weight: 600;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
        }

        .date-time {
            font-size: 14px;
            color: #aaa;
            display: flex;
            align-items: center;
        }

        .date-time i {
            margin-right: 8px;
            color: #4cc9f0;
        }

        /* Table Styling - Updated to Match Theme */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        th {
            background-color: rgba(76, 201, 240, 0.2);
            color: #4cc9f0;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 1px;
        }
        
        tr:hover {
            background-color: rgba(76, 201, 240, 0.1);
        }
        
        /* Status Styling */
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }
        
        .status-approved {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-rejected {
            color: #f72585;
            font-weight: bold;
        }
        
        /* Button Styling */
        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin: 0 5px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 100px;
        }
        
        .approve-btn {
            background: linear-gradient(135deg, #28a745, #218838);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .approve-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        
        .disapprove-btn {
            background: linear-gradient(135deg, #f72585, #b5179e);
            color: white;
            box-shadow: 0 4px 15px rgba(247, 37, 133, 0.3);
        }
        
        .disapprove-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(247, 37, 133, 0.4);
        }
        
        /* Search and Filter */
        .search-filter {
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .search-filter input, 
        .search-filter select {
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            color: white;
            font-family: 'Poppins', sans-serif;
            flex: 1;
            min-width: 150px;
            transition: all 0.3s;
        }
        
        .search-filter input:focus, 
        .search-filter select:focus {
            outline: none;
            border-color: #4cc9f0;
            box-shadow: 0 0 0 2px rgba(76, 201, 240, 0.3);
        }
        
        /* Notification styles */
        .notification {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 10px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .success {
            border-left: 5px solid #28a745;
        }
        
        .error {
            border-left: 5px solid #f72585;
        }
        
        .close-notification {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        
        .close-notification:hover {
            opacity: 1;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            table {
                display: block;
                overflow-x: auto;
            }
        }

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
                content: "SM";
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
            
            .search-filter {
                flex-direction: column;
            }
        }

        @media (max-width: 768px) {
            .welcome-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .date-time {
                margin-top: 10px;
            }
            
            th, td {
                padding: 10px;
                font-size: 14px;
            }
            
            .action-btn {
                min-width: auto;
                padding: 6px 10px;
                font-size: 12px;
                margin: 2px;
            }
        }
    </style>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        <li><a href="admin_reservations.php"class="active"><i class="fas fa-calendar-check"></i> <span>Reservations</span></a></li>
        <li><a href="admin_lab_management.php"><i class="fas fa-laptop-house"></i> <span>Lab Management</span></a></li>
        <li><a href="#"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
        <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Welcome Header -->
        <div class="welcome-header">
            <h1>Manage Reservations</h1>
            <div class="date-time">
                <i class="far fa-calendar-alt"></i>
                <span id="current-date-time"></span>
            </div>
        </div>
        
        <!-- Notification Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="notification success">
                <?= $_SESSION['success_message'] ?>
                <button class="close-notification">&times;</button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="notification error">
                <?= $_SESSION['error_message'] ?>
                <button class="close-notification">&times;</button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="search-filter">
            <input type="text" placeholder="Search by student name or ID" id="searchInput">
            <select id="statusFilter">
                <option value="">All Statuses</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
            </select>
            <select id="labFilter">
                <option value="">All Labs</option>
                <option value="524">Lab 524</option>
                <option value="526">Lab 526</option>
                <option value="528">Lab 528</option>
                <option value="530">Lab 530</option>
                <option value="542">Lab 542</option>
                <option value="544">Lab 544</option>
            </select>
        </div>
        
        <table id="reservationsTable">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>ID</th>
                    <th>Purpose</th>
                    <th>Lab</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Sessions Left</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                        <td><?= htmlspecialchars($row['student_id']) ?></td>
                        <td><?= htmlspecialchars($row['purpose']) ?></td>
                        <td>Lab <?= htmlspecialchars($row['lab_number']) ?></td>
                        <td><?= date('Y-m-d', strtotime($row['date'])) ?></td>
                        <td><?= date('h:i A', strtotime($row['time_in'])) ?></td>
                        <td><?= $row['remaining_sessions'] ?></td>
                        <td class="status-<?= strtolower($row['status']) ?>">
                            <?= $row['status'] ?>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'Pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="reservation_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="approve" class="action-btn approve-btn">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="reservation_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="disapprove" class="action-btn disapprove-btn">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color: #aaa;">No actions</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Notification Bell (from dashboard) -->
    <div class="notification-bell">
        <i class="fas fa-bell"></i>
        <span class="notification-count">3</span>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Update current date and time
        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            document.getElementById('current-date-time').textContent = now.toLocaleDateString('en-US', options);
        }
        
        // Update immediately and then every minute
        updateDateTime();
        setInterval(updateDateTime, 60000);

        // Filter functionality
        $('#searchInput, #statusFilter, #labFilter').on('keyup change', function() {
            const searchText = $('#searchInput').val().toLowerCase();
            const statusFilter = $('#statusFilter').val();
            const labFilter = $('#labFilter').val();
            
            $('#reservationsTable tbody tr').each(function() {
                const studentText = $(this).find('td:first').text().toLowerCase();
                const idText = $(this).find('td:nth-child(2)').text().toLowerCase();
                const statusText = $(this).find('td:nth-child(8)').text();
                const labText = $(this).find('td:nth-child(4)').text();
                
                const matchesSearch = studentText.includes(searchText) || idText.includes(searchText);
                const matchesStatus = statusFilter === '' || statusText === statusFilter;
                const matchesLab = labFilter === '' || labText.includes(labFilter);
                
                $(this).toggle(matchesSearch && matchesStatus && matchesLab);
            });
        });

        // Close notification
        $('.close-notification').click(function() {
            $(this).parent().fadeOut();
        });
    });
    </script>
</body>
</html>