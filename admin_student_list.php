<?php
require 'config.php'; // Include database connection

// Handle reset actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reset_single'])) {
        // Reset single student session
        $idno = $_POST['idno'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // 1. Remove from current sit-ins
            $delete = $conn->prepare("DELETE FROM admin_sitin WHERE idno = ?");
            $delete->bind_param("s", $idno);
            $delete->execute();
            
            // 2. Reset remaining sessions (set to default value, e.g., 3)
            $reset = $conn->prepare("UPDATE users SET remaining_sessions = 30 WHERE idno = ?");
            $reset->bind_param("s", $idno);
            $reset->execute();
            
            $conn->commit();
            echo "<script>alert('Session reset for student ID: $idno');</script>";
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Error resetting session: " . addslashes($e->getMessage()) . "');</script>";
        }
    } elseif (isset($_POST['reset_all'])) {
        // Reset all student sessions
        $conn->begin_transaction();
        
        try {
            // 1. Clear all current sit-ins
            $conn->query("TRUNCATE TABLE admin_sitin");
            
            // 2. Reset all remaining sessions to default
            $conn->query("UPDATE users SET remaining_sessions = 30");
            
            $conn->commit();
            echo "<script>alert('All student sessions have been reset');</script>";
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Error resetting all sessions: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}

// Fetch all students from the `users` table
$sql = "SELECT idno, firstname, lastname, remaining_sessions FROM users ORDER BY lastname ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>List of Students</title>
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

        /* Content Box */
        .content-box {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .content-box:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        .content-box h2 {
            font-size: 28px;
            margin-bottom: 25px;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-align: center;
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 20px;
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

        /* Button Styles */
        .reset-btn {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.5);
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            font-size: 13px;
        }
        
        .reset-btn:hover {
            background: rgba(220, 53, 69, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.2);
        }
        
        .reset-all-btn {
            background: linear-gradient(90deg, #f72585, #b5179e);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            margin-top: 20px;
            margin-bottom: 30px;
            display: block;
            margin-left: auto;
            margin-right: auto;
            transition: all 0.3s;
        }
        
        .reset-all-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(247, 37, 133, 0.3);
        }

        /* Session Count */
        .session-count {
            font-weight: bold;
            color: #4cc9f0;
        }

        /* No Students Message */
        .no-students {
            text-align: center;
            padding: 20px;
            color: #aaa;
            font-size: 16px;
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
        <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li><a href="admin_student_list.php"  class="active"><i class="fas fa-users"></i> <span>Students</span></a></li>
        <li><a href="admin_announcement.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a></li>
        <li><a href="manage_sitins.php"><i class="fas fa-user-clock"></i> <span>Manage Sit-ins</span></a></li>
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
    <div class="content-box">
        <h2>List of Registered Students</h2>

        <form method="post" style="display: inline;">
            <button type="submit" name="reset_all" class="reset-all-btn" 
                    onclick="return confirm('WARNING: This will reset ALL student sessions and clear all current sit-ins. Continue?');">
                <i class="fas fa-sync-alt"></i> Reset All Student Sessions
            </button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID No.</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Remaining Sessions</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['idno']) ?></td>
                            <td><?= htmlspecialchars($row['firstname']) ?></td>
                            <td><?= htmlspecialchars($row['lastname']) ?></td>
                            <td class="session-count"><?= htmlspecialchars($row['remaining_sessions']) ?></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="idno" value="<?= htmlspecialchars($row['idno']) ?>">
                                    <button type="submit" name="reset_single" class="reset-btn"
                                            onclick="return confirm('Reset sessions for <?= htmlspecialchars($row['firstname']) ?> <?= htmlspecialchars($row['lastname']) ?>? This will remove any current sit-ins and reset their session count.');">
                                        <i class="fas fa-redo"></i> Reset Session
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-students">
                            <i class="fas fa-user-slash"></i> No students found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

<?php
$conn->close();
?>