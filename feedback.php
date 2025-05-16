<?php
include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$sql = "
SELECT sh.feedback, sh.sitin_time, sh.student_idno, 
       sh.lab_classroom, sh.purpose, sh.time_in, 
       u.lastname, u.firstname, u.midname 
FROM sitin_history sh
JOIN users u ON sh.student_idno = u.idno
WHERE sh.feedback IS NOT NULL AND sh.feedback != '' 
ORDER BY sh.time_in DESC";


$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback Reports</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            text-align: center;
        }

        /* Card Container */
        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        /* Feedback Cards */
        .card {
            background: rgba(255, 255, 255, 0.05); 
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            border-color: rgba(76, 201, 240, 0.5);
        }

        .student-name { 
            font-size: 18px; 
            font-weight: 600; 
            margin-bottom: 15px; 
            color: #4cc9f0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 10px;
        }
        
        .feedback { 
            font-size: 14px; 
            margin-bottom: 20px; 
            white-space: pre-wrap; 
            color: #ddd;
            line-height: 1.6;
        }

        /* View More Details */
        .view-more {
            position: relative; 
            font-weight: 500; 
            color: #4cc9f0; 
            cursor: pointer; 
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s;
        }
        
        .view-more:hover {
            color: #4895ef;
        }
        
        .view-more .details {
            display: none; 
            position: absolute; 
            bottom: 100%; 
            left: 0;
            width: 300px; 
            background: rgba(0, 0, 0, 0.9); 
            color: white;
            padding: 15px; 
            border-radius: 10px; 
            z-index: 10; 
            font-size: 14px;
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.3);
            border: 1px solid rgba(76, 201, 240, 0.3);
        }
        
        .view-more:hover .details { 
            display: block; 
        }
        
        .view-more .details::after {
            content: ""; 
            position: absolute; 
            top: 100%; 
            left: 20px;
            border-width: 8px; 
            border-style: solid; 
            border-color: rgba(0, 0, 0, 0.9) transparent transparent transparent;
        }
        
        .view-more .details strong {
            color: #4cc9f0;
            display: inline-block;
            min-width: 100px;
        }
        
        .view-more .details div {
            margin-bottom: 8px;
        }

        /* No Feedback Message */
        .no-feedback {
            text-align: center;
            padding: 30px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px dashed rgba(255, 255, 255, 0.2);
            color: #aaa;
            font-size: 16px;
            grid-column: 1 / -1;
        }
        
        .no-feedback i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #4cc9f0;
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
            .card-container {
                grid-template-columns: 1fr;
            }
            
            .view-more .details {
                width: 250px;
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
        <li><a href="feedback.php"  class="active"><i class="fas fa-comment-alt"></i> <span>Feedback</span></a></li>
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
    <h2>Feedback Reports</h2>

    <div class="card-container">
        <?php if ($result->num_rows == 0): ?>
            <div class="no-feedback">
                <i class="fas fa-comment-slash"></i>
                <p>No feedback found.</p>
            </div>
        <?php else: ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card">
                    <div class="student-name">
                        <?php echo htmlspecialchars($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['midname']); ?>
                    </div>
                    <div class="feedback">
                        <?php echo nl2br(htmlspecialchars($row['feedback'])); ?>
                    </div>
                    <div class="view-more">
                        <i class="fas fa-info-circle"></i> View Details
                        <div class="details">
                            <div><strong>Lab/Classroom:</strong> <?php echo htmlspecialchars($row['lab_classroom']); ?></div>
                            <div><strong>Time In:</strong> <?php echo htmlspecialchars($row['time_in']); ?></div>
                            <div><strong>Time Out:</strong> <?php echo htmlspecialchars($row['sitin_time']); ?></div>
                            <div><strong>Purpose:</strong> <?php echo htmlspecialchars($row['purpose']); ?></div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

<?php
$conn->close();
?>