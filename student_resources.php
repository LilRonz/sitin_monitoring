<?php
include 'config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get resources available to all users
$stmt = $conn->prepare("SELECT * FROM resources WHERE available_to = 'all' ORDER BY upload_date DESC");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Available Resources</title>
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
            min-height: 100vh;
        }

        /* Content Box */
        .content-box {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Header */
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

        /* Resource Grid */
        .resource-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        /* Resource Cards */
        .resource-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border-color: rgba(76, 201, 240, 0.3);
        }

        .resource-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #4cc9f0;
        }

        .resource-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #aaa;
        }

        .resource-meta span {
            display: flex;
            align-items: center;
        }

        .resource-meta i {
            margin-right: 5px;
            color: #4cc9f0;
            font-size: 14px;
        }

        .resource-description {
            font-size: 15px;
            line-height: 1.5;
            margin-bottom: 20px;
            color: #ddd;
        }

        /* Download Button */
        .download-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.3);
        }

        .download-btn i {
            margin-right: 8px;
        }

        /* No Resources */
        .no-resources {
            text-align: center;
            padding: 40px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            border: 1px dashed rgba(255, 255, 255, 0.1);
        }

        .no-resources i {
            font-size: 48px;
            color: #4cc9f0;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .no-resources p {
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
            .resource-grid {
                grid-template-columns: 1fr;
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
            <li><a href="Announcements.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a></li>
            <li><a href="sit-in-rules.php"><i class="fas fa-book"></i> <span>Sit-in Rules</span></a></li>
            <li><a href="lab-rules.php"><i class="fas fa-gavel"></i> <span>Regulations</span></a></li>
            <li><a href="sit_in_history.php"><i class="fas fa-history"></i> <span>History</span></a></li>
            <li><a href="student_resources.php" class="active"><i class="fas fa-file-alt"></i> <span>Resources</span></a></li>
            <li><a href="student_lab_schedule.php"><i class="fas fa-calendar-alt"></i> <span>Schedule</span></a></li>
            <li><a href="reservation.php"><i class="fas fa-calendar-plus"></i> <span>Reservation</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Log Out</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-box">
            <div class="content-header">
                <h1>Available Resources</h1>
            </div>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="resource-grid">
                    <?php while ($resource = $result->fetch_assoc()): ?>
                        <div class="resource-card">
                            <div class="resource-title"><?php echo htmlspecialchars($resource['title']); ?></div>
                            <div class="resource-meta">
                                <span><i class="far fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($resource['upload_date'])); ?></span>
                                <span><i class="fas fa-file"></i> <?php echo round($resource['file_size'] / 1024 / 1024, 2); ?>MB</span>
                            </div>
                            <div class="resource-description">
                                <?php echo nl2br(htmlspecialchars($resource['description'])); ?>
                            </div>
                            <a href="<?php echo $resource['file_path']; ?>" class="download-btn" download>
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-resources">
                    <i class="fas fa-folder-open"></i>
                    <p>No resources available yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
