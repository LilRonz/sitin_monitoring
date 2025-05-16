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

// Initialize variables
$student = null;
$error = "";
$success = "";
$remaining_sessions = 0;
define('TOTAL_SESSIONS', 30);

// Handle Timeout action
if (isset($_POST['timeout_id'])) {
    $timeout_id = $_POST['timeout_id'];
    
    // Get student info before timeout
    $stmt = $conn->prepare("SELECT idno FROM admin_sitin WHERE id = ?");
    $stmt->bind_param("i", $timeout_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sit_in = $result->fetch_assoc();
    
    if ($sit_in) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // 1. Record in history
            $insert = $conn->prepare("INSERT INTO sitin_history 
                                   SELECT NULL, idno, lab_classroom, purpose, time_in, NOW() 
                                   FROM admin_sitin WHERE id = ?");
            $insert->bind_param("i", $timeout_id);
            $insert->execute();
            
            // 2. Deduct 1 session
            $deduct = $conn->prepare("UPDATE users 
                                    SET remaining_sessions = remaining_sessions - 1 
                                    WHERE idno = ? AND remaining_sessions > 0");
            $deduct->bind_param("s", $sit_in['idno']);
            $deduct->execute();
            
            // 3. Remove from current sit-ins
            $delete = $conn->prepare("DELETE FROM admin_sitin WHERE id = ?");
            $delete->bind_param("i", $timeout_id);
            $delete->execute();
            
            $conn->commit();
            $success = "Student timed out successfully. Session deducted.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error processing timeout: " . $e->getMessage();
        }
    } else {
        $error = "Sit-in record not found.";
    }
}

// Handle search request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
    $idno = $_POST['idno'];

    $stmt = $conn->prepare("SELECT idno, lastname, firstname, midname, remaining_sessions FROM users WHERE idno = ?");
    $stmt->bind_param("s", $idno);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $remaining_sessions = $student['remaining_sessions'];
    } else {
        $error = "No student found with ID: $idno";
    }
    $stmt->close();
}

// Handle Sit In form submission - ENHANCED DUPLICATE PREVENTION WITH DETAILED MESSAGE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sit_in'])) {
    $idno = $_POST['idno'];
    $lab_classroom = $_POST['lab_classroom'];
    $purpose = $_POST['purpose'];

    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. First check if student is already sitting in (with detailed info)
        $check_active = $conn->prepare("SELECT a.id, a.lab_classroom, CONCAT(u.firstname, ' ', u.lastname) AS name 
                                      FROM admin_sitin a
                                      JOIN users u ON a.idno = u.idno
                                      WHERE a.idno = ?");
        $check_active->bind_param("s", $idno);
        $check_active->execute();
        $active_result = $check_active->get_result();
        
        if ($active_result->num_rows > 0) {
            $active_sit_in = $active_result->fetch_assoc();
            $error_message = "Cannot sit-in: ".htmlspecialchars($active_sit_in['name'])." is already in ";
            $error_message .= "Room ".htmlspecialchars($active_sit_in['lab_classroom']).". ";
            $error_message .= "Please timeout the student first before sitting in again.";
            throw new Exception($error_message);
        }
        
        // 2. Check remaining sessions (with FOR UPDATE lock)
        $check_sessions = $conn->prepare("SELECT remaining_sessions FROM users WHERE idno = ? FOR UPDATE");
        $check_sessions->bind_param("s", $idno);
        $check_sessions->execute();
        $sessions_result = $check_sessions->get_result();
        
        if ($sessions_row = $sessions_result->fetch_assoc()) {
            if ($sessions_row['remaining_sessions'] <= 0) {
                throw new Exception("Student has no remaining sessions!");
            }
            
            // 3. Record new sit-in
            $stmt = $conn->prepare("INSERT INTO admin_sitin (idno, lab_classroom, purpose) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $idno, $lab_classroom, $purpose);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to record sit-in.");
            }
            
            $conn->commit();
            $success = "Sit-in recorded successfully!";
            header("Location: manage_sitins.php");
            exit();
        } else {
            throw new Exception("Student not found!");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
        $_SESSION['error'] = $error; // Store error in session for persistence
    }
    
    if (isset($check_active)) $check_active->close();
    if (isset($check_sessions)) $check_sessions->close();
    if (isset($stmt)) $stmt->close();
}

// Fetch current sit-ins for display
$current_sitins = $conn->query("
    SELECT a.id, a.idno, CONCAT(u.firstname, ' ', u.lastname) AS fullname, 
           a.lab_classroom, a.purpose, a.time_in, u.remaining_sessions
    FROM admin_sitin a
    JOIN users u ON a.idno = u.idno
    ORDER BY a.time_in DESC
");

// Fetch student count per lab classroom
$lab_classrooms = [];
$student_counts = [];

$query = $conn->query("SELECT lab_classroom, COUNT(*) as count FROM admin_sitin GROUP BY lab_classroom");
while ($row = $query->fetch_assoc()) {
    $lab_classrooms[] = $row['lab_classroom'];
    $student_counts[] = $row['count'];
}

// Fetch student count per purpose
$purposes = [];
$purpose_counts = [];

$query2 = $conn->query("SELECT purpose, COUNT(*) as count FROM admin_sitin GROUP BY purpose");
while ($row2 = $query2->fetch_assoc()) {
    $purposes[] = $row2['purpose'];
    $purpose_counts[] = $row2['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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

        /* Main Content Styling */
        .main-content {
            margin-left: 270px;
            padding: 40px;
            width: calc(100% - 270px);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Search Bar */
        .search-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 15px 0;
            margin-bottom: 20px;
        }

        .search-bar input {
            padding: 12px 15px;
            border: none;
            border-radius: 8px;
            width: 300px;
            margin-right: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #4cc9f0;
            box-shadow: 0 0 0 2px rgba(76, 201, 240, 0.2);
        }

        .search-bar button {
            padding: 12px 20px;
            border: none;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .search-bar button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.3);
        }

        /* Student Details */
        .student-details {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 350px;
            position: absolute;
            top: 120px;
            right: 40px;
            z-index: 9;
            transition: all 0.3s ease;
        }

        .student-details:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        .student-details h3 {
            font-size: 22px;
            margin-bottom: 20px;
            color: #4cc9f0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 10px;
        }

        .student-details p {
            font-size: 16px;
            margin: 15px 0;
            color: #ddd;
        }

        .student-details strong {
            color: #4cc9f0;
        }

        .student-details label {
            display: block;
            margin: 15px 0 8px;
            font-size: 14px;
            color: #aaa;
        }

        .student-details select {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .student-details select:focus {
            outline: none;
            border-color: #4cc9f0;
            box-shadow: 0 0 0 2px rgba(76, 201, 240, 0.2);
        }

        /* Button Container */
        .button-container {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 25px;
        }

        /* Button Styles */
        .button-container button {
            flex: 1;
            padding: 12px 0;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close-btn {
            background: rgba(255, 0, 25, 0.92);
            color:rgb(215, 187, 189);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .close-btn:hover {
            background: rgba(220, 53, 69, 0.3);
            transform: translateY(-2px);
        }

        .sit-in-btn {
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            color: white;
        }

        .sit-in-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.3);
        }

        /* Error Message */
        .error-message {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 3px solid #dc3545;
            font-weight: 500;
        }

        /* Charts Container */
        .charts-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
            margin-top: 30px;
        }

        .chart-wrapper {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 45%;
            min-width: 400px;
            transition: all 0.3s ease;
        }

        .chart-wrapper:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        /* Enhanced Section Styles */
        .enhanced-section {
            display: flex;
            gap: 30px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .section-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            flex: 1;
            min-width: 300px;
            transition: all 0.3s ease;
        }

        .section-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        .section-title {
            font-size: 18px;
            color: #4cc9f0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .custom-checkbox {
            display: flex;
            align-items: center;
            position: relative;
            padding-left: 30px;
            cursor: pointer;
            color: #ddd;
            transition: all 0.3s ease;
        }

        .custom-checkbox:hover {
            color: #4cc9f0;
        }

        .custom-checkbox input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 20px;
            width: 20px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .custom-checkbox:hover .checkmark {
            background-color: rgba(76, 201, 240, 0.2);
        }

        .custom-checkbox input:checked ~ .checkmark {
            background-color: #4cc9f0;
        }

        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }

        .custom-checkbox input:checked ~ .checkmark:after {
            display: block;
        }

        .custom-checkbox .checkmark:after {
            left: 7px;
            top: 3px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .checkbox-label {
            margin-right: 8px;
        }

        .checkbox-count {
            color: #aaa;
            font-size: 0.9em;
        }

        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .quick-btn {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            background: rgba(76, 201, 240, 0.2);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .quick-btn:hover {
            background: rgba(76, 201, 240, 0.4);
            transform: translateY(-2px);
        }

        .quick-btn i {
            font-size: 14px;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .chart-wrapper {
                width: 100%;
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

            .student-details {
                width: 300px;
                right: 20px;
            }
        }

        @media (max-width: 768px) {
            .search-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-bar input {
                width: 100%;
                margin-bottom: 10px;
                margin-right: 0;
            }
            
            .student-details {
                position: relative;
                width: 100%;
                top: auto;
                right: auto;
                margin-top: 20px;
            }
            
            .chart-wrapper {
                min-width: 100%;
            }

            .enhanced-section {
                flex-direction: column;
            }

            .section-card {
                min-width: 100%;
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
        <li><a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li><a href="admin_student_list.php"><i class="fas fa-users"></i> <span>Students</span></a></li>
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

    <!-- Search Bar -->
    <form method="POST" class="search-bar">
        <input type="text" name="idno" placeholder="Search by ID No..." required>
        <button type="submit" name="search"><i class="fas fa-search"></i> Search</button>
    </form>

    <!-- Display Student Details If Found -->
    <?php if ($student) : ?>
    <form method="POST" class="student-details">
        <h3>Student Details</h3>
        <p><strong>ID No:</strong> <?= htmlspecialchars($student['idno']); ?></p>
        <p><strong>Name:</strong> <?= htmlspecialchars($student['firstname'] . " " . $student['midname'] . " " . $student['lastname']); ?></p>

        <!-- Hidden IDNO Field -->
        <input type="hidden" name="idno" value="<?= htmlspecialchars($student['idno']); ?>">

        <!-- Dropdown for Lab Classroom -->
        <label for="lab_classroom">Lab Classroom:</label>
        <select id="lab_classroom" name="lab_classroom" required>
            <option value="524">Room 524</option>
            <option value="526">Room 526</option>
            <option value="528">Room 528</option>
            <option value="530">Room 530</option>
            <option value="542">Room 542</option>
            <option value="544">Room 544</option>
        </select>

        <!-- Dropdown for Purpose -->
        <label for="purpose">Purpose:</label>
        <select id="purpose" name="purpose" required>
            <option value="C Programming">C Programming</option>
            <option value="C# Programming">C# Programming</option>
            <option value="JAVA Programming">JAVA Programming</option>
            <option value="System Integration & Architechture">System Integration & Architechture</option>
            <option value="Embedded Systems & Iot">Embedded Systems & Iot</option>
            <option value="Digital Logic & Design">Digital Logic & Design</option>
            <option value="Computer Application">Computer Application</option>
            <option value="Database">Database</option>
            <option value="Project Management">Project Management</option>
            <option value="Python Programming">Python Programming</option>
            <option value="Mobile Application">Mobile Application</option>
            <option value="Others..">Others..</option>
        </select>

        <p><strong>Remaining Sessions:</strong> <?= $remaining_sessions; ?></p>

        <!-- Buttons -->
        <div class="button-container">
            <button type="button" onclick="closeDetails()" class="close-btn"><i class="fas fa-times"></i> Close</button>
            <button type="submit" name="sit_in" class="sit-in-btn"><i class="fas fa-chair"></i> Sit In</button>
        </div>
    </form>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <button class="quick-btn select-all">
            <i class="fas fa-check-square"></i> Select All
        </button>
        <button class="quick-btn deselect-all">
            <i class="fas fa-square"></i> Deselect All
        </button>
        <button class="quick-btn apply-action">
            <i class="fas fa-play"></i> Apply Action
        </button>
    </div>

    <!-- Enhanced Checkbox Sections -->
    <div class="enhanced-section">
        <div class="section-card">
            <h3 class="section-title">Students by Lab Classroom</h3>
            <div class="checkbox-grid">
                <label class="custom-checkbox">
                    <input type="checkbox">
                    <span class="checkmark"></span>
                    <span class="checkbox-label">SX4</span>
                    <span class="checkbox-count">(24)</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox">
                    <span class="checkmark"></span>
                    <span class="checkbox-label">Lab 544</span>
                    <span class="checkbox-count">(18)</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox">
                    <span class="checkmark"></span>
                    <span class="checkbox-label">C Programming</span>
                    <span class="checkbox-count">(32)</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox">
                    <span class="checkmark"></span>
                    <span class="checkbox-label">JAVA Programming</span>
                    <span class="checkbox-count">(27)</span>
                </label>
            </div>
        </div>

        <div class="section-card">
            <h3 class="section-title">Students by Purpose</h3>
            <div class="checkbox-grid">
                <label class="custom-checkbox">
                    <input type="checkbox">
                    <span class="checkmark"></span>
                    <span class="checkbox-label">Search by G/No.</span>
                    <span class="checkbox-count">(14)</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox">
                    <span class="checkmark"></span>
                    <span class="checkbox-label">Q-Sense</span>
                    <span class="checkbox-count">(8)</span>
                </label>
            </div>
        </div>
    </div>

    <div class="charts-container">
        <div class="chart-wrapper">
            <canvas id="sitInPieChart"></canvas>
        </div>
        <div class="chart-wrapper">
            <canvas id="purposePieChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function closeDetails() {
        document.querySelector('.student-details').style.display = 'none';
    }

    document.addEventListener("DOMContentLoaded", function () {
        // First Pie Chart - Student Count per Lab Classroom
        const ctx1 = document.getElementById("sitInPieChart").getContext("2d");
        new Chart(ctx1, {
            type: "pie",
            data: {
                labels: <?= json_encode($lab_classrooms); ?>,
                datasets: [{
                    data: <?= json_encode($student_counts); ?>,
                    backgroundColor: ["#4cc9f0", "#4895ef", "#4361ee", "#3f37c9", "#3a0ca3", "#480ca8"],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            color: "white",
                            font: { size: 14 }
                        }
                    },
                    title: {
                        display: true,
                        text: "Students by Lab Classroom",
                        color: "white",
                        font: { size: 18 }
                    }
                }
            }
        });

        // Second Pie Chart - Student Count per Purpose
        const ctx2 = document.getElementById("purposePieChart").getContext("2d");
        new Chart(ctx2, {
            type: "pie",
            data: {
                labels: <?= json_encode($purposes); ?>,
                datasets: [{
                    data: <?= json_encode($purpose_counts); ?>,
                    backgroundColor: ["#f72585", "#b5179e", "#7209b7", "#560bad", "#480ca8", "#3a0ca3"],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            color: "white",
                            font: { size: 14 }
                        }
                    },
                    title: {
                        display: true,
                        text: "Students by Purpose",
                        color: "white",
                        font: { size: 18 }
                    }
                }
            }
        });

        // Checkbox interactions
        const checkboxes = document.querySelectorAll('.custom-checkbox');
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('click', function() {
                if (this.querySelector('input').checked) {
                    this.style.transform = 'scale(1.02)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 200);
                }
            });
        });
        
        // Quick action buttons
        document.querySelector('.select-all').addEventListener('click', function() {
            document.querySelectorAll('.custom-checkbox input').forEach(checkbox => {
                checkbox.checked = true;
            });
        });

        document.querySelector('.deselect-all').addEventListener('click', function() {
            document.querySelectorAll('.custom-checkbox input').forEach(checkbox => {
                checkbox.checked = false;
            });
        });

        document.querySelector('.apply-action').addEventListener('click', function() {
            // Add your action logic here
            alert('Action applied to selected items!');
        });
    });
</script>

</body>
</html>