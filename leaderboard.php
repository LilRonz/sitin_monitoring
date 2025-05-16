<?php
include 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// First, check for students who have reached 3 points and process their conversion
$check_query = "SELECT student_idno, points FROM student_rewards WHERE points >= 3";
$check_result = $conn->query($check_query);

while ($row = $check_result->fetch_assoc()) {
    $student_id = $row['student_idno'];
    $points = $row['points'];
    
    // Calculate how many full sessions we can convert (3 points = 1 session)
    $sessions_to_add = floor($points / 3);
    $remaining_points = $points % 3;
    
    if ($sessions_to_add > 0) {
        // Start transaction for data consistency
        $conn->begin_transaction();
        
        try {
            // Update the student's points (reset to remainder after conversion)
            $update_points = "UPDATE student_rewards SET points = $remaining_points WHERE student_idno = '$student_id'";
            $conn->query($update_points);
            
            // Add the sessions to the student's remaining_sessions
            $update_sessions = "UPDATE users SET remaining_sessions = remaining_sessions + $sessions_to_add WHERE idno = '$student_id'";
            $conn->query($update_sessions);
            
            // Log this conversion (optional but recommended)
            $log_conversion = "INSERT INTO reward_conversions (student_idno, points_converted, sessions_granted, conversion_date) 
                              VALUES ('$student_id', " . ($sessions_to_add * 3) . ", $sessions_to_add, NOW())";
            $conn->query($log_conversion);
            
            // Commit the transaction if all queries succeeded
            $conn->commit();
        } catch (Exception $e) {
            // Rollback if any error occurred
            $conn->rollback();
            error_log("Error converting points to sessions for student $student_id: " . $e->getMessage());
        }
    }
}

// Now fetch the top 3 students for the cards
$top3_query = "
    SELECT 
        u.idno, 
        CONCAT(u.firstname, ' ', u.lastname) AS fullname, 
        IFNULL(r.points, 0) AS points,
        u.remaining_sessions
    FROM users u
    LEFT JOIN student_rewards r ON u.idno = r.student_idno
    ORDER BY points DESC
    LIMIT 3
";
$top3_result = $conn->query($top3_query);

// Fetch all students for the table
$all_query = "
    SELECT 
        u.idno, 
        CONCAT(u.firstname, ' ', u.lastname) AS fullname, 
        IFNULL(r.points, 0) AS points,
        u.remaining_sessions
    FROM users u
    LEFT JOIN student_rewards r ON u.idno = r.student_idno
    ORDER BY points DESC
";
$all_result = $conn->query($all_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reward Leaderboard | Admin Panel</title>
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

        /* Welcome Header */
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

        /* Leaderboard Header */
        .leaderboard-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .leaderboard-header h2 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #4cc9f0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .leaderboard-header p {
            font-size: 16px;
            color: #aaa;
            margin-bottom: 20px;
        }

        /* Top Players Cards */
        .top-players {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .player-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            width: 280px;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }

        .player-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        .player-card.rank-1 {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.15), rgba(255, 215, 0, 0.05));
            border: 1px solid rgba(255, 215, 0, 0.3);
            order: 2;
        }

        .player-card.rank-2 {
            background: linear-gradient(135deg, rgba(192, 192, 192, 0.15), rgba(192, 192, 192, 0.05));
            border: 1px solid rgba(192, 192, 192, 0.3);
            order: 1;
            margin-top: 40px;
        }

        .player-card.rank-3 {
            background: linear-gradient(135deg, rgba(205, 127, 50, 0.15), rgba(205, 127, 50, 0.05));
            border: 1px solid rgba(205, 127, 50, 0.3);
            order: 3;
            margin-top: 40px;
        }

        .player-rank {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #fff;
            position: relative;
            display: inline-block;
        }

        .player-rank:after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 2px;
            background: #4cc9f0;
        }

        .player-name {
            font-size: 20px;
            font-weight: bold;
            margin: 15px 0;
            color: #fff;
        }

        .player-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #4cc9f0;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Leaderboard Table */
        .leaderboard-table {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            overflow: hidden;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 16px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        th {
            background: rgba(0, 0, 0, 0.3);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 1px;
            color: #4cc9f0;
        }

        tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 0.03);
        }

        tr.highlight {
            background-color: rgba(76, 201, 240, 0.15);
            font-weight: 500;
        }

        tr:hover {
            background-color: rgba(255, 255, 255, 0.08);
        }

        /* Rank Badges */
        .rank-badge {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            line-height: 30px;
            text-align: center;
            font-weight: bold;
            color: white;
        }

        .rank-1-badge {
            background: linear-gradient(135deg, #FFD700, #FFA500);
        }

        .rank-2-badge {
            background: linear-gradient(135deg, #C0C0C0, #A0A0A0);
        }

        .rank-3-badge {
            background: linear-gradient(135deg, #CD7F32, #A05A2C);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .top-players {
                flex-direction: column;
                align-items: center;
            }
            
            .player-card {
                width: 100%;
                max-width: 400px;
                margin-top: 0 !important;
                margin-bottom: 20px;
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
                content: "AP";
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
            .welcome-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .date-time {
                margin-top: 10px;
            }
            
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
        <li><a href="admin_student_list.php"><i class="fas fa-users"></i> <span>Students</span></a></li>
        <li><a href="admin_announcement.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a></li>
        <li><a href="manage_sitins.php"><i class="fas fa-user-clock"></i> <span>Manage Sit-ins</span></a></li>
        <li><a href="sit_in_records.php"><i class="fas fa-history"></i> <span>Sit-in Records</span></a></li>
        <li><a href="leaderboard.php" class="active"><i class="fas fa-trophy"></i> <span>Leaderboard</span></a></li>
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
        <!-- Welcome Header -->
        <div class="welcome-header">
            <h1>Reward Leaderboard</h1>
            <div class="date-time">
                <i class="far fa-calendar-alt"></i>
                <span id="current-date-time"></span>
            </div>
        </div>

        <div class="leaderboard-header">
            <h2>Top Performers</h2>
            <p>3 points = 1 additional session (automatically converted)</p>
        </div>

        <!-- Top 3 Players -->
        <div class="top-players">
            <?php 
            $rank = 1;
            while ($row = $top3_result->fetch_assoc()):
            ?>
                <div class="player-card rank-<?= $rank ?>">
                    <div class="player-rank">
                        <?php if ($rank == 1): ?>
                            <span class="rank-badge rank-1-badge"><?= $rank ?></span>
                        <?php elseif ($rank == 2): ?>
                            <span class="rank-badge rank-2-badge"><?= $rank ?></span>
                        <?php else: ?>
                            <span class="rank-badge rank-3-badge"><?= $rank ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="player-name"><?= htmlspecialchars($row['fullname']) ?></div>
                    <div class="player-stats">
                        <div class="stat">
                            <div class="stat-value"><?= htmlspecialchars($row['points']) ?></div>
                            <div class="stat-label">Points</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?= htmlspecialchars($row['remaining_sessions']) ?></div>
                            <div class="stat-label">Sessions</div>
                        </div>
                    </div>
                </div>
            <?php 
                $rank++;
            endwhile; 
            ?>
        </div>

        <!-- Full Leaderboard Table -->
        <div class="leaderboard-table">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Points</th>
                        <th>Total Sessions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    while ($row = $all_result->fetch_assoc()):
                        $highlight = $row['points'] >= 3 ? 'highlight' : '';
                    ?>
                        <tr class="<?= $highlight ?>">
                            <td><?= $rank++ ?></td>
                            <td><?= htmlspecialchars($row['idno']) ?></td>
                            <td><?= htmlspecialchars($row['fullname']) ?></td>
                            <td><?= htmlspecialchars($row['points']) ?></td>
                            <td><?= htmlspecialchars($row['remaining_sessions']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
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
    </script>
</body>
</html>

<?php
$conn->close();
?>