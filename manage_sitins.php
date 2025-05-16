<?php
include 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables
$reward_message = '';

// Handle reward action (which will now also timeout the student)
if (isset($_POST['reward_id'])) {
    $reward_id = $_POST['reward_id'];
    
    // Get student info
    $select_query = "SELECT a.id, a.idno, CONCAT(u.firstname, ' ', u.lastname) AS fullname,
                    a.lab_classroom, a.purpose, a.time_in
                    FROM admin_sitin a
                    JOIN users u ON a.idno = u.idno
                    WHERE a.id = ?";
    $stmt = $conn->prepare($select_query);
    $stmt->bind_param('i', $reward_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sit_in_info = $result->fetch_assoc();

    if ($sit_in_info) {
        $conn->begin_transaction();
        
        try {
            // 1. Add reward point
            $check_query = "SELECT points FROM student_rewards WHERE student_idno = ?";
            $stmt_check = $conn->prepare($check_query);
            $stmt_check->bind_param('s', $sit_in_info['idno']);
            $stmt_check->execute();
            $existing_points = $stmt_check->get_result()->fetch_assoc();
            
            $current_points = $existing_points ? $existing_points['points'] : 0;
            $new_points = $current_points + 1;

            $reward_query = "INSERT INTO student_rewards (student_idno, points) 
                           VALUES (?, ?)
                           ON DUPLICATE KEY UPDATE points = VALUES(points)";
            $stmt_reward = $conn->prepare($reward_query);
            $stmt_reward->bind_param('si', $sit_in_info['idno'], $new_points);
            $stmt_reward->execute();

            // 2. Insert into sitin_history (timeout the student)
            $insert_history = "INSERT INTO sitin_history (student_idno, lab_classroom, purpose, time_in, sitin_time) 
                             VALUES (?, ?, ?, ?, NOW())";
            $stmt_insert = $conn->prepare($insert_history);
            $stmt_insert->bind_param('ssss', $sit_in_info['idno'], $sit_in_info['lab_classroom'], 
                                    $sit_in_info['purpose'], $sit_in_info['time_in']);
            $stmt_insert->execute();

            // 3. Deduct session
            $deduct_session = "UPDATE users SET remaining_sessions = remaining_sessions - 1 
                              WHERE idno = ? AND remaining_sessions > 0";
            $stmt_deduct = $conn->prepare($deduct_session);
            $stmt_deduct->bind_param('s', $sit_in_info['idno']);
            $stmt_deduct->execute();

            // 4. Delete from admin_sitin
            $delete_query = "DELETE FROM admin_sitin WHERE id = ?";
            $stmt_delete = $conn->prepare($delete_query);
            $stmt_delete->bind_param('i', $reward_id);
            $stmt_delete->execute();

            $conn->commit();
            
            echo json_encode([
                'status' => 'success',
                'message' => "Reward point added to {$sit_in_info['fullname']} and student timed out",
                'student_id' => $sit_in_info['idno']
            ]);
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode([
                'status' => 'error',
                'message' => "Error: " . $e->getMessage()
            ]);
            exit();
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => "Student record not found."
        ]);
        exit();
    }
}

// Handle logout action (timeout without reward)
if (isset($_POST['logout_id'])) {
    $logout_id = $_POST['logout_id'];

    // Get the sit-in info before deleting
    $select_query = "SELECT id, idno, lab_classroom, purpose, time_in FROM admin_sitin WHERE id = ?";
    $stmt = $conn->prepare($select_query);
    $stmt->bind_param('i', $logout_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sit_in_info = $result->fetch_assoc();

    if ($sit_in_info) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // 1. Insert into sitin_history with both time_in and timeout
            $insert_history = "INSERT INTO sitin_history (student_idno, lab_classroom, purpose, time_in, sitin_time) 
                             VALUES (?, ?, ?, ?, NOW())";
            $stmt_insert = $conn->prepare($insert_history);
            $stmt_insert->bind_param('ssss', $sit_in_info['idno'], $sit_in_info['lab_classroom'], 
                                    $sit_in_info['purpose'], $sit_in_info['time_in']);
            $stmt_insert->execute();

            // 2. Deduct 1 from remaining_sessions in users table
            $deduct_session = "UPDATE users SET remaining_sessions = remaining_sessions - 1 
                              WHERE idno = ? AND remaining_sessions > 0";
            $stmt_deduct = $conn->prepare($deduct_session);
            $stmt_deduct->bind_param('s', $sit_in_info['idno']);
            $stmt_deduct->execute();

            // 3. Delete from admin_sitin
            $delete_query = "DELETE FROM admin_sitin WHERE id = ?";
            $stmt_delete = $conn->prepare($delete_query);
            $stmt_delete->bind_param('i', $logout_id);
            $stmt_delete->execute();

            // Commit transaction if all queries succeed
            $conn->commit();

            if ($stmt_delete->affected_rows > 0) {
                echo "<script>alert('Student logged out and session deducted successfully!'); 
                      window.location.href = 'sit_in_records.php';</script>";
            } else {
                echo "<script>alert('Logout failed.');</script>";
            }
        } catch (Exception $e) {
            // Rollback transaction if any error occurs
            $conn->rollback();
            echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
        }
    } else {
        echo "<script>alert('Student record not found.');</script>";
    }
}

// Handle sit-in form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['idno'], $_POST['lab_classroom'], $_POST['purpose']) && !isset($_POST['logout_id'])) {
    $idno = $_POST['idno'];
    $lab_classroom = $_POST['lab_classroom'];
    $purpose = $_POST['purpose'];

    $query = "SELECT firstname, lastname FROM users WHERE idno = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $idno);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $insert = "INSERT INTO admin_sitin (idno, lab_classroom, purpose, time_in) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert);
        $stmt->bind_param('sss', $idno, $lab_classroom, $purpose);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "<script>alert('Sit-in record added successfully!'); window.location.href = 'sit_in_records.php';</script>";
        } else {
            echo "<script>alert('Failed to add record.');</script>";
        }
    } else {
        echo "<script>alert('Student ID not found.');</script>";
    }
}

// Fetch sit-in records
$query = "
SELECT a.id, a.idno, CONCAT(u.firstname, ' ', u.lastname) AS fullname, a.lab_classroom, a.purpose, a.time_in
FROM admin_sitin a
JOIN users u ON a.idno = u.idno
ORDER BY a.id DESC";

$result = $conn->query($query);
if (!$result) {
    die("Database error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sit-ins</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

        /* Sidebar Styling */
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
        }

        .main-content h2 {
            font-size: 28px;
            margin-bottom: 25px;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 20px;
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
            background: rgba(0, 0, 0, 0.3);
            color: #4cc9f0;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 1px;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
        }

        button {
            padding: 8px 15px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .logout {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.5);
        }

        .logout:hover {
            background: rgba(220, 53, 69, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.2);
        }

        .reward-btn {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.5);
        }

        .reward-btn:hover {
            background: rgba(40, 167, 69, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.2);
        }

        /* Reward Notification */
        .reward-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            z-index: 1000;
            display: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
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
        }

        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
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
        <li><a href="manage_sitins.php"class="active" ><i class="fas fa-user-clock"></i> <span>Manage Sit-ins</span></a></li>
        <li><a href="sit_in_records.php"><i class="fas fa-history"></i> <span>Sit-in Records</span></a></li>
        <li><a href="leaderboard.php"><i class="fas fa-trophy"></i> <span>Leaderboard</span></a></li>
        <li><a href="feedback.php"><i class="fas fa-comment-alt"></i> <span>Feedback</span></a></li>
        <li><a href="lab_schedule.php"><i class="fas fa-calendar-alt"></i> <span>Lab Schedule</span></a></li>
        <li><a href="admin_upload_resource.php"><i class="fas fa-upload"></i> <span>Resources</span></a></li>
        <li><a href="admin_reservations.php"><i class="fas fa-calendar-check"></i> <span>Reservations</span></a></li>
        <li><a href="admin_lab_management.php"><i class="fas fa-laptop-house"></i> <span>Lab Management</span></a></li>
        <li><a href="#"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
        <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <h2>Manage Sit-ins</h2>
    
    <?php if (!empty($reward_message)): ?>
        <div class="reward-notification" id="rewardMessage">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($reward_message) ?>
        </div>
        <script>
            $(document).ready(function() {
                $('#rewardMessage').fadeIn().delay(3000).fadeOut();
            });
        </script>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID No</th>
                <th>Full Name</th>
                <th>Lab Classroom</th>
                <th>Purpose</th>
                <th>Time In</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['idno']) ?></td>
                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                    <td><?= htmlspecialchars($row['lab_classroom']) ?></td>
                    <td><?= htmlspecialchars($row['purpose']) ?></td>
                    <td><?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($row['time_in']))) ?></td>
                    <td>
                        <div class="action-buttons">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="logout_id" value="<?= htmlspecialchars($row['id']) ?>">
                                <button type="submit" class="logout" onclick="return confirm('Are you sure you want to log out this student?')">
                                    <i class="fas fa-sign-out-alt"></i> Timeout
                                </button>
                            </form>
                            <form method="POST" style="display:inline;" class="reward-form">
                                <input type="hidden" name="reward_id" value="<?= htmlspecialchars($row['id']) ?>">
                                <button type="submit" class="reward-btn" onclick="return confirm('Add reward point and timeout this student?')">
                                    <i class="fas fa-star"></i> Add Reward
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="reward-notification" id="rewardNotification"></div>

<script>
$(document).ready(function() {
    $('.reward-form').on('submit', function(e) {
        e.preventDefault();
        
        if (confirm('Add reward point and timeout this student?')) {
            var form = $(this);
            $.ajax({
                url: 'manage_sitins.php',
                method: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Show notification
                        $('#rewardNotification').html('<i class="fas fa-check-circle"></i> ' + response.message).fadeIn().delay(2000).fadeOut();
                        
                        // Remove the row from the table
                        form.closest('tr').remove();
                        
                        // Optional: Redirect to leaderboard after 2 seconds
                        setTimeout(function() {
                            window.location.href = 'leaderboard.php';
                        }, 2000);
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while processing your request.');
                }
            });
        }
    });
});
</script>
</body>
</html>