<?php
include 'config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];

// Get student_idno using the logged-in username
$sql = "SELECT idno FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($student_idno);
$stmt->fetch();
$stmt->close();

// Handle feedback submission
if (isset($_POST['submit_feedback'])) {
    $history_id = $_POST['history_id'];
    $feedback = $_POST['feedback'];

    $sql = "UPDATE sitin_history SET feedback = ? WHERE id = ? AND student_idno = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sis", $feedback, $history_id, $student_idno);
    $stmt->execute();
    $stmt->close();

    header("Location: sit_in_history.php?submitted=1&id=" . $history_id);
    exit();
}

// Fetch sit-in history records
$sql = "
SELECT 
    id, 
    lab_classroom, 
    purpose, 
    time_in, 
    sitin_time, 
    feedback 
FROM sitin_history 
WHERE student_idno = ? 
ORDER BY sitin_time DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_idno);
$stmt->execute();
$history = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sit-in History</title>
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
        }

        .main-content h2 {
            font-size: 28px;
            font-weight: 600;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 30px;
            text-align: center;
        }

        /* History Cards */
        .history-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .history-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .history-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        .history-card h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #4cc9f0;
            font-size: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 10px;
        }

        .history-card p {
            margin: 8px 0;
            font-size: 14px;
        }

        .history-card strong {
            color: #aaa;
            font-weight: 500;
        }

        /* Feedback Box */
        .feedback-box {
            margin-top: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            border-left: 3px solid #4cc9f0;
        }

        .feedback-box strong {
            color: #4cc9f0;
        }

        /* Feedback Form */
        .feedback-form {
            margin-top: 15px;
        }

        .feedback-form label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #aaa;
        }

        .feedback-form textarea {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: white;
            font-size: 14px;
            resize: vertical;
            min-height: 80px;
            transition: all 0.3s;
        }

        .feedback-form textarea:focus {
            outline: none;
            border-color: #4cc9f0;
            box-shadow: 0 0 0 3px rgba(76, 201, 240, 0.2);
        }

        .feedback-form button {
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            margin-top: 10px;
            transition: all 0.3s;
            width: 100%;
        }

        .feedback-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.4);
        }

        /* No History Message */
        .no-history {
            text-align: center;
            padding: 30px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px dashed rgba(255, 255, 255, 0.2);
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
            .history-container {
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
            <li><a href="sit_in_history.php" class="active"><i class="fas fa-history"></i> <span>History</span></a></li>
            <li><a href="student_resources.php"><i class="fas fa-file-alt"></i> <span>Resources</span></a></li>
            <li><a href="student_lab_schedule.php"><i class="fas fa-calendar-alt"></i> <span>Schedule</span></a></li>
            <li><a href="student_reservation.php"><i class="fas fa-calendar-plus"></i> <span>Reservation</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Log Out</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h2>Your Sit-in History</h2>

        <?php if ($history->num_rows > 0): ?>
        <div class="history-container">
            <?php while ($row = $history->fetch_assoc()): ?>
                <div class="history-card">
                    <h3><?php echo htmlspecialchars($row['lab_classroom']); ?></h3>
                    <p><strong>Purpose:</strong> <?php echo htmlspecialchars($row['purpose']); ?></p>
                    <p><strong>Time In:</strong> <?php echo htmlspecialchars($row['time_in']); ?></p>
                    <p><strong>Time Out:</strong> <?php echo htmlspecialchars($row['sitin_time']); ?></p>

                    <?php if (!empty($row['feedback'])): ?>
                        <div class="feedback-box">
                            <strong>Feedback Submitted:</strong>
                            <p><?php echo htmlspecialchars($row['feedback']); ?></p>
                        </div>
                    <?php else: ?>
                        <form class="feedback-form" method="POST">
                            <input type="hidden" name="history_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                            <label>Your Feedback:</label>
                            <textarea name="feedback" rows="3" placeholder="Share your experience..."></textarea>
                            <button type="submit" name="submit_feedback">Submit Feedback</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
            <div class="no-history">
                <i class="fas fa-history" style="font-size: 24px; margin-bottom: 10px;"></i>
                <p>No sit-in history available yet.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>
