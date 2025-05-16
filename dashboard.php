<?php
require 'config.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
// Redirect to login if not authenticated
if (!isset($_SESSION['username'])) {
    header('Location: http://localhost/ccs-2/log-in.php'); // Correct login page
    exit;
}

$username = $_SESSION['username']; // Safely retrieve username

// Initialize variables to avoid undefined warnings
$idno = $lastname = $firstname = $midname = $course = $yearlevel = $email = $remaining_sessions = "";

// Fetch user details from the database
$sql = "SELECT idno, lastname, firstname, midname, course, yearlevel, email, username, remaining_sessions FROM users WHERE username = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($idno, $lastname, $firstname, $midname, $course, $yearlevel, $email, $username, $remaining_sessions);

    if ($stmt->fetch()) {
        // Data fetched successfully
        // Set default to 30 if remaining_sessions is null
        if ($remaining_sessions === null) {
            $remaining_sessions = 30;
            // Update database with default value
            $update_sql = "UPDATE users SET remaining_sessions = 30 WHERE username = ?";
            if ($update_stmt = $conn->prepare($update_sql)) {
                $update_stmt->bind_param("s", $username);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
    } else {
        echo "<script>alert('User data not found.');</script>";
    }
    $stmt->close();
} else {
    echo "Database error: " . $conn->error;
}

// At the top of dashboard.php (after user data fetch):
// Get unread notifications
$notifications = [];
$unread_count = 0;
$notif_query = "SELECT id, message, created_at, is_read FROM notifications 
               WHERE user_id = ? 
               ORDER BY is_read ASC, created_at DESC 
               LIMIT 10"; // Added is_read to the query
$stmt = $conn->prepare($notif_query);
$stmt->bind_param("s", $idno);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$unread_count = count(array_filter($notifications, function($n) { return !$n['is_read']; }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        /* General Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg,rgb(85, 56, 104) 0%,rgb(41, 79, 28) 100%);
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

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        /* Profile Card */
        .profile-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4cc9f0, #4895ef);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 30px;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
        }

        .profile-title h2 {
            font-size: 22px;
            margin-bottom: 5px;
        }

        .profile-title p {
            font-size: 14px;
            color: #aaa;
        }

        .profile-info {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
        }

        .info-item i {
            width: 30px;
            height: 30px;
            background: rgba(76, 201, 240, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: #4cc9f0;
            font-size: 14px;
        }

        .info-label {
            font-size: 14px;
            color: #aaa;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 500;
        }

        /* Stats Card */
        .stats-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .stats-header h3 {
            font-size: 18px;
            color: #4cc9f0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.03);
            transition: background 0.3s;
        }

        .stat-item:hover {
            background: rgba(255, 255, 255, 0.07);
        }

        .stat-value {
            font-size: 28px;
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

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }

        .action-btn {
            background: rgba(76, 201, 240, 0.1);
            border: 1px solid rgba(76, 201, 240, 0.2);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            color: white;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .action-btn:hover {
            background: rgba(76, 201, 240, 0.2);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.1);
        }

        .action-btn i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #4cc9f0;
        }

        .action-btn span {
            font-size: 13px;
        }

        /* Notification Styles */
        .notification-bell {
            position: fixed;
            top: 20px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: white;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.7);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        
        .notification-bell:hover {
            background: rgba(76, 201, 240, 0.2);
            transform: scale(1.1);
        }
        
        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .notification-panel {
            position: fixed;
            top: 80px;
            right: 20px;
            width: 350px;
            max-height: 500px;
            overflow-y: auto;
            background: rgba(0, 0, 0, 0.9);
            border: 1px solid #4cc9f0;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(76, 201, 240, 0.3);
            display: none;
            z-index: 999;
            backdrop-filter: blur(10px);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid rgba(76, 201, 240, 0.2);
            background: rgba(0, 0, 0, 0.7);
            position: sticky;
            top: 0;
        }
        
        .notification-header h3 {
            margin: 0;
            color: white;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .close-notifications {
            color: white;
            font-size: 20px;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .close-notifications:hover {
            color: #4cc9f0;
        }
        
        .notification-list {
            padding: 15px;
        }
        
        .notification-item {
            padding: 12px;
            margin-bottom: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-left: 3px solid #4cc9f0;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .notification-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .notification-item p {
            margin: 0 0 5px 0;
            color: white;
            font-size: 14px;
        }
        
        .notification-item small {
            color: #aaa;
            font-size: 12px;
        }

        .read-notification {
            opacity: 0.7;
            background-color: rgba(255, 255, 255, 0.03);
        }

        .notification-item.rejected {
            border-left: 3px solid #f72585;
        }

        .notification-item.approved {
            border-left: 3px solid #4cc9f0;
        }

        /* Session Meter */
        .session-meter {
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
        }

        .meter-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .meter-header span {
            font-size: 14px;
            color: #aaa;
        }

        .meter-bar {
            height: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            overflow: hidden;
        }

        .meter-progress {
            height: 100%;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            border-radius: 5px;
            width: <?php echo ($remaining_sessions/30)*100; ?>%;
            transition: width 1s ease;
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
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .date-time {
                margin-top: 10px;
            }
        }
    </style>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Sitin Monitoring</h2>
        <ul>
            <li><a href="dashboard.php" class="active"><i class="fas fa-user"></i> <span>Profile</span></a></li>
            <li><a href="edit-profile.php"><i class="fas fa-edit"></i> <span>Edit</span></a></li>
            <li><a href="Announcements.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a></li>
            <li><a href="sit-in-rules.php"><i class="fas fa-book"></i> <span>Sit-in Rules</span></a></li>
            <li><a href="lab-rules.php"><i class="fas fa-gavel"></i> <span>Regulations</span></a></li>
            <li><a href="sit_in_history.php"><i class="fas fa-history"></i> <span>History</span></a></li>
            <li><a href="student_resources.php"><i class="fas fa-file-alt"></i> <span>Resources</span></a></li>
            <li><a href="student_lab_schedule.php"><i class="fas fa-calendar-alt"></i> <span>Schedule</span></a></li>
            <li><a href="student_reservation.php"><i class="fas fa-calendar-plus"></i> <span>Reservation</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Log Out</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Welcome Header -->
        <div class="welcome-header">
            <h1>Welcome Back, <?php echo htmlspecialchars($firstname); ?>!</h1>
            <div class="date-time">
                <i class="far fa-calendar-alt"></i>
                <span id="current-date-time"></span>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo substr($firstname, 0, 1) . substr($lastname, 0, 1); ?>
                    </div>
                    <div class="profile-title">
                        <h2><?php echo htmlspecialchars($firstname . " " . $lastname); ?></h2>
                        <p>BS Information Technology</p>
                    </div>
                </div>
                <div class="profile-info">
                    <div class="info-item">
                        <i class="fas fa-id-card"></i>
                        <div>
                            <div class="info-label">Student ID</div>
                            <div class="info-value"><?php echo htmlspecialchars($idno); ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-graduation-cap"></i>
                        <div>
                            <div class="info-label">Year Level</div>
                            <div class="info-value"><?php echo htmlspecialchars($yearlevel); ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($email); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Session Meter -->
                <div class="session-meter">
                    <div class="meter-header">
                        <span>Available Sessions</span>
                        <span><?php echo $remaining_sessions; ?>/30</span>
                    </div>
                    <div class="meter-bar">
                        <div class="meter-progress"></div>
                    </div>
                </div>
                
                <!-- Edit Button -->
                <a href="edit-profile.php" class="action-btn" style="margin-top: 20px; text-align: center;">
                    <i class="fas fa-user-edit"></i>
                    <span>Edit Profile</span>
                </a>
            </div>

            <!-- Stats Card -->
            <div class="stats-card">
                <div class="stats-header">
                    <h3>Your Statistics</h3>
                    <i class="fas fa-chart-line" style="color: #4cc9f0;"></i>
                </div>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $remaining_sessions; ?></div>
                        <div class="stat-label">Sessions Left</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">12</div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">3</div>
                        <div class="stat-label">Reservations</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">95%</div>
                        <div class="stat-label">Attendance</div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <a href="reservation.php" class="action-btn">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Reserve</span>
                    </a>
                    <a href="student_resources.php" class="action-btn">
                        <i class="fas fa-book-open"></i>
                        <span>Resources</span>
                    </a>
                    <a href="sit_in_history.php" class="action-btn">
                        <i class="fas fa-history"></i>
                        <span>History</span>
                    </a>
                    <a href="Announcements.php" class="action-btn">
                        <i class="fas fa-bullhorn"></i>
                        <span>News</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Notification Bell -->
    <div class="notification-bell">
        <i class="fas fa-bell"></i>
        <?php if ($unread_count > 0): ?>
            <span class="notification-count"><?= $unread_count ?></span>
        <?php endif; ?>
    </div>

    <!-- Notification Panel -->
    <div class="notification-panel">
        <div class="notification-header">
            <h3>Notifications</h3>
            <span class="close-notifications">&times;</span>
        </div>
        <div class="notification-list">
            <?php if (empty($notifications)): ?>
                <div class="notification-item">No new notifications</div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?= strpos($notif['message'], 'rejected') !== false ? 'rejected' : 
                                                  (strpos($notif['message'], 'approved') !== false ? 'approved' : '') ?>"
                         data-id="<?= $notif['id'] ?>">
                        <div class="notification-message"><?= htmlspecialchars($notif['message']) ?></div>
                        <small class="notification-time">
                            <?= date('M j, g:i a', strtotime($notif['created_at'])) ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
        
        // Toggle notification panel
        document.querySelector('.notification-bell').addEventListener('click', function() {
            const panel = document.querySelector('.notification-panel');
            panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
        });
        
        // Close panel
        document.querySelector('.close-notifications').addEventListener('click', function() {
            document.querySelector('.notification-panel').style.display = 'none';
        });
        
        // Mark as read when clicked
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notifId = this.getAttribute('data-id');
                if (notifId) {
                    // Send AJAX request to mark as read
                    fetch('mark_notification_read.php?id=' + notifId)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Add visual feedback that it's been read
                                this.classList.add('read-notification');
                                updateNotificationCount();
                            }
                        });
                }
            });
        });
        
        function updateNotificationCount() {
            // Update the notification count display
            const countElement = document.querySelector('.notification-count');
            if (countElement) {
                const currentCount = parseInt(countElement.textContent);
                if (currentCount > 1) {
                    countElement.textContent = currentCount - 1;
                } else {
                    countElement.remove();
                }
            }
        }
    </script>
</body>
</html>