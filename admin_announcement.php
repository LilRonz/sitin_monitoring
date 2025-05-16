<?php
include 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle form submission for new announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    $sql = "INSERT INTO announcements (title, message, created_at) VALUES ('$title', '$message', NOW())";
    if ($conn->query($sql) === TRUE) {
        $success = "Announcement published successfully!";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $delete_sql = "DELETE FROM announcements WHERE id = $id";
    if ($conn->query($delete_sql) === TRUE) {
        $success = "Announcement deleted successfully!";
    } else {
        $error = "Error deleting announcement: " . $conn->error;
    }
}

// Fetch all announcements
$announcements_sql = "SELECT * FROM announcements ORDER BY created_at DESC";
$announcements_result = $conn->query($announcements_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements | Admin Panel</title>
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

        /* Announcements Container */
        .announcements-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        /* Create Announcement Section */
        .create-announcement {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .create-announcement:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        /* Announcement List Section */
        .announcement-list {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-height: 80vh;
            overflow-y: auto;
        }

        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-header h2 {
            font-size: 22px;
            color: #4cc9f0;
            margin-bottom: 0;
        }

        /* Form Elements */
        label {
            display: block;
            margin: 15px 0 5px;
            color: #ddd;
            font-weight: bold;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s;
        }

        input[type="text"]:focus,
        textarea:focus {
            border-color: #4cc9f0;
            outline: none;
            box-shadow: 0 0 0 2px rgba(76, 201, 240, 0.2);
        }

        textarea {
            resize: vertical;
            min-height: 150px;
        }

        button, .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        button[type="submit"] {
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            color: white;
            font-weight: 500;
        }

        button[type="submit"]:hover {
            background: linear-gradient(90deg, #3ab0d6, #3a84d6);
            transform: translateY(-2px);
        }

        /* Announcement Items */
        .announcement-item {
            background: rgba(0, 0, 0, 0.3);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 3px solid #4cc9f0;
            transition: all 0.3s;
        }

        .announcement-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .announcement-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #4cc9f0;
        }

        .announcement-date {
            font-size: 0.8rem;
            color: #aaa;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .announcement-date i {
            margin-right: 8px;
            color: #4cc9f0;
        }

        .announcement-message {
            margin-bottom: 15px;
            line-height: 1.6;
            color: #ddd;
        }

        .announcement-actions {
            display: flex;
            gap: 10px;
        }

        .btn-edit {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .btn-edit:hover {
            background: rgba(255, 193, 7, 0.3);
        }

        .btn-delete {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .btn-delete:hover {
            background: rgba(220, 53, 69, 0.3);
        }

        .no-announcements {
            text-align: center;
            padding: 30px;
            color: #aaa;
            font-style: italic;
        }

        /* Status Messages */
        .success {
            color: #28a745;
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(40, 167, 69, 0.1);
            border-radius: 5px;
            border-left: 3px solid #28a745;
        }

        .error {
            color: #dc3545;
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(220, 53, 69, 0.1);
            border-radius: 5px;
            border-left: 3px solid #dc3545;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .announcements-container {
                grid-template-columns: 1fr;
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
        <li><a href="admin_announcement.php"class="active"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a></li>
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
        <!-- Welcome Header -->
        <div class="welcome-header">
            <h1>Announcement Management</h1>
            <div class="date-time">
                <i class="far fa-calendar-alt"></i>
                <span id="current-date-time"></span>
            </div>
        </div>

        <div class="announcements-container">
            <!-- Create Announcement Form -->
            <div class="create-announcement">
                <div class="section-header">
                    <h2>Create New Announcement</h2>
                    <i class="fas fa-bullhorn" style="color: #4cc9f0;"></i>
                </div>
                
                <?php if (isset($success)): ?>
                    <div class="success"><?= $success ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="error"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <label>Title:</label>
                    <input type="text" name="title" required placeholder="Enter announcement title">

                    <label>Message:</label>
                    <textarea name="message" rows="5" required placeholder="Type your announcement message here..."></textarea>

                    <button type="submit">Publish Announcement</button>
                </form>
            </div>

            <!-- Existing Announcements -->
            <div class="announcement-list">
                <div class="section-header">
                    <h2>Existing Announcements</h2>
                    <i class="fas fa-list" style="color: #4cc9f0;"></i>
                </div>
                
                <?php if ($announcements_result->num_rows > 0): ?>
                    <?php while ($announcement = $announcements_result->fetch_assoc()): ?>
                        <div class="announcement-item">
                            <div class="announcement-title"><?= htmlspecialchars($announcement['title']) ?></div>
                            <div class="announcement-date">
                                <i class="far fa-clock"></i>
                                Posted on: <?= date("F j, Y, g:i a", strtotime($announcement['created_at'])) ?>
                            </div>
                            <div class="announcement-message"><?= nl2br(htmlspecialchars($announcement['message'])) ?></div>
                            <div class="announcement-actions">
                                <a href="edit_announcement.php?id=<?= $announcement['id'] ?>" class="btn btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="?delete=<?= $announcement['id'] ?>" class="btn btn-delete" 
                                   onclick="return confirm('Are you sure you want to delete this announcement?')">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-announcements">
                        <i class="fas fa-bullhorn" style="font-size: 2rem; margin-bottom: 15px; color: #4cc9f0;"></i>
                        <p>No announcements found.</p>
                    </div>
                <?php endif; ?>
            </div>
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