<?php
include 'config.php'; // DB connection
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Announcements</title>
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
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .content-header h1 {
            font-size: 28px;
            font-weight: 600;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
            margin: 0;
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

        /* Announcements Container */
        .announcements-container {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .announcements-container h2 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 30px;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Announcement Cards */
        .announcement-card {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid #4cc9f0;
            transition: all 0.3s ease;
        }

        .announcement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            border-left: 4px solid #4895ef;
        }

        .announcement-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #4cc9f0;
        }

        .announcement-content {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 15px;
            color: #ddd;
        }

        .announcement-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #aaa;
        }

        .announcement-date {
            display: flex;
            align-items: center;
        }

        .announcement-date i {
            margin-right: 5px;
            color: #4cc9f0;
        }

        .no-announcements {
            text-align: center;
            padding: 40px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            border: 1px dashed rgba(255, 255, 255, 0.1);
        }

        .no-announcements i {
            font-size: 48px;
            color: #4cc9f0;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .no-announcements p {
            font-size: 18px;
            color: #aaa;
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
            .announcement-card {
                padding: 20px;
            }
            
            .announcement-title {
                font-size: 18px;
            }
            
            .announcement-content {
                font-size: 15px;
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
            <li><a href="dashboard.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
            <li><a href="edit-profile.php"><i class="fas fa-edit"></i> <span>Edit</span></a></li>
            <li><a href="Announcements.php" class="active"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a></li>
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
        <div class="content-header">
            <h1>Announcements</h1>
            <div class="date-time">
                <i class="far fa-calendar-alt"></i>
                <span id="current-date-time"></span>
            </div>
        </div>
        
        <div class="announcements-container">
            <h2>Latest Announcements</h2>

            <?php
            // Fetch announcements from the database
            $sql = "SELECT title, message, created_at FROM announcements ORDER BY created_at DESC";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="announcement-card">
                        <div class="announcement-title"><?= htmlspecialchars($row['title']) ?></div>
                        <div class="announcement-content"><?= nl2br(htmlspecialchars($row['message'])) ?></div>
                        <div class="announcement-meta">
                            <div class="announcement-date">
                                <i class="far fa-clock"></i>
                                <?= date("F j, Y g:i a", strtotime($row['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-announcements">
                    <i class="fas fa-bullhorn"></i>
                    <p>No announcements available at the moment.</p>
                </div>
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
    </script>
</body>
</html>
