<?php
require 'config.php';

// Enable strict error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables
$student_id = '';
$student_name = '';
$remaining_sessions = 0;
$error = '';
$success = '';
$has_pending_reservation = false;
$is_currently_sitin = false; // New flag for sit-in check

// Get current user info from session or database
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    
    // Query to get student information
    $user_query = "SELECT idno, lastname, firstname, midname, remaining_sessions 
                  FROM users WHERE username = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $student_id = $user['idno'];
        $student_name = $user['lastname'] . ', ' . $user['firstname'] . ' ' . ($user['midname'] ?? '');
        $remaining_sessions = $user['remaining_sessions'];
        
        // Store in session for future use
        $_SESSION['student_id'] = $student_id;
        $_SESSION['student_name'] = $student_name;
        $_SESSION['remaining_sessions'] = $remaining_sessions;
        
        // Check for pending reservations
        $pending_query = "SELECT id FROM reservations 
                         WHERE student_id = ? AND status = 'Pending'";
        $pending_stmt = $conn->prepare($pending_query);
        $pending_stmt->bind_param("s", $student_id);
        $pending_stmt->execute();
        $has_pending_reservation = $pending_stmt->get_result()->num_rows > 0;

        // ✅ Check if student is currently a sit-in
        $check_sitin_query = "SELECT id FROM admin_sitin WHERE idno = ?";
        $stmt_sitin = $conn->prepare($check_sitin_query);
        $stmt_sitin->bind_param("s", $student_id);
        $stmt_sitin->execute();
        $is_currently_sitin = $stmt_sitin->get_result()->num_rows > 0;

    } else {
        $error = "User not found in database.";
    }
} elseif (isset($_SESSION['student_id'])) {
    // Fallback to session data if available
    $student_id = $_SESSION['student_id'];
    $student_name = $_SESSION['student_name'] ?? '';
    $remaining_sessions = $_SESSION['remaining_sessions'] ?? 0;

    // Check for pending reservations
    $pending_query = "SELECT id FROM reservations 
                 WHERE student_id = ? AND status = 'Pending'";
    $pending_stmt = $conn->prepare($pending_query);
    $pending_stmt->bind_param("s", $student_id);
    $pending_stmt->execute();
    $has_pending_reservation = $pending_stmt->get_result()->num_rows > 0;

    // Check for active sit-ins in manage_sitins
    $active_sitin_query = "SELECT id FROM manage_sitins WHERE idno = ?";
    $active_sitin_stmt = $conn->prepare($active_sitin_query);
    $active_sitin_stmt->bind_param("s", $student_id);
    $active_sitin_stmt->execute();
    $has_active_sitin = $active_sitin_stmt->get_result()->num_rows > 0;


    // ✅ Check if student is currently a sit-in
    $check_sitin_query = "SELECT id FROM admin_sitin WHERE idno = ?";
    $stmt_sitin = $conn->prepare($check_sitin_query);
    $stmt_sitin->bind_param("s", $student_id);
    $stmt_sitin->execute();
    $is_currently_sitin = $stmt_sitin->get_result()->num_rows > 0;

} else {
    $error = "Not logged in. Please login first.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_reservation'])) {
    $purpose = $_POST['purpose'];
    $lab_number = $_POST['lab_number'];
    $pc_number = is_numeric($_POST['pc_number']) ? $_POST['pc_number'] : str_replace('PC', '', $_POST['pc_number']);
    $date = $_POST['date'];
    $time_in = date('H:i:s'); // Current time

    // Validate inputs
    if (empty($purpose) || empty($lab_number) || empty($pc_number) || empty($date)) {
        $error = "All fields are required";
    } 
    elseif ($has_pending_reservation) {
        $error = "You already have a pending reservation. Please wait for it to be approved or canceled before making another one.";
    } 
    // ✅ BLOCK reservation if student is still logged in as a sit-in
    elseif ($is_currently_sitin) {
        $error = "You cannot make a reservation while still logged in as a sit-in. Please log out from the lab first.";
    }
    elseif ($remaining_sessions <= 0) {
    $error = "You have no remaining sessions available.";
    } elseif ($has_active_sitin) {
    $error = "You are currently logged in a lab sit-in. Please log out before making a reservation.";
    } else {
        try {
            $conn->begin_transaction();

            // 1. Check if PC exists in this lab
            $check_pc = $conn->prepare("SELECT cs.id 
                FROM computer_stations cs
                JOIN computer_labs cl ON cs.lab_id = cl.id
                WHERE cl.lab_number = ? AND cs.pc_number = ?");
            $check_pc->bind_param("ii", $lab_number, $pc_number);
            $check_pc->execute();

            if ($check_pc->get_result()->num_rows == 0) {
                $error = "PC $pc_number does not exist in Lab $lab_number";
                $conn->rollback();
            } else {
                // 2. Check for time conflicts (within 1 hour window)
                $check_time = $conn->prepare("SELECT id FROM reservations 
                    WHERE lab_number = ? AND pc_number = ? AND date = ? 
                    AND ABS(TIMESTAMPDIFF(MINUTE, time_in, ?)) < 60");
                $check_time->bind_param("iiss", $lab_number, $pc_number, $date, $time_in);
                $check_time->execute();

                if ($check_time->get_result()->num_rows > 0) {
                    $error = "This PC is already reserved for the selected time period";
                    $conn->rollback();
                } else {
                    // 3. Create reservation
                    $stmt = $conn->prepare("INSERT INTO reservations 
                        (student_id, student_name, purpose, lab_number, pc_number, date, time_in, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
                    $stmt->bind_param("sssiiss", $student_id, $student_name, $purpose, 
                                     $lab_number, $pc_number, $date, $time_in);

                    if ($stmt->execute()) {
                        // Deduct from remaining sessions
                        $update_stmt = $conn->prepare("UPDATE users 
                            SET remaining_sessions = remaining_sessions - 0
                            WHERE idno = ?");
                        $update_stmt->bind_param("s", $student_id);
                        $update_stmt->execute();

                        $conn->commit();
                        $_SESSION['success'] = "Reservation submitted successfully!";
                        header("Location: ".$_SERVER['PHP_SELF']);
                        exit();
                    }
                }
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Check for success message from redirect
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Get reservation history if student is logged in
$history_result = null;
if (!empty($student_id)) {
   $history_query = "SELECT date, lab_number, pc_number, time_in, 
                 COALESCE(status, 'Pending') as status
                 FROM reservations 
                 WHERE student_id = ?
                 ORDER BY date DESC, time_in DESC
                 LIMIT 5";
    $stmt = $conn->prepare($history_query);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $history_result = $stmt->get_result();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Reservation System</title>
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
            flex-wrap: wrap;
            gap: 30px;
            align-items: flex-start;
        }

        /* Reservation Form */
        .reservation-form {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            flex: 1;
            min-width: 400px;
            transition: all 0.3s ease;
        }

        .reservation-form:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        .reservation-form h2 {
            font-size: 24px;
            margin-bottom: 25px;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-align: center;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #aaa;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4cc9f0;
            box-shadow: 0 0 0 2px rgba(76, 201, 240, 0.2);
        }

        .form-group input[readonly] {
            background: rgba(255, 255, 255, 0.03);
            color: #ccc;
        }

         .form-group select option {
            background-color: #16213e;
            color: white;
            padding: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

          @-moz-document url-prefix() {
            .form-group select {
                color: white !important;
                text-shadow: 0 0 0 white;
            }
            .form-group select option {
                background-color: #16213e;
            }
        }

        /* For IE10+ */
        @media screen and (-ms-high-contrast: active), (-ms-high-contrast: none) {
            .form-group select {
                color: white;
            }
            .form-group select option {
                background-color: #16213e;
                color: white;
            }
        }

        /* PC Grid */
        .pc-grid-container {
            margin-top: 15px;
        }

        .pc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }

        .pc-item {
            padding: 10px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pc-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .pc-item.available {
            border-color: rgba(40, 167, 69, 0.3);
        }

        .pc-item.available:hover {
            border-color: rgba(40, 167, 69, 0.5);
        }

        .pc-item.used {
            border-color: rgba(220, 53, 69, 0.3);
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pc-item.selected {
            background: rgba(76, 201, 240, 0.2);
            border-color: #4cc9f0;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.2);
        }

        .pc-item i {
            display: block;
            margin-bottom: 5px;
            font-size: 18px;
        }

        .pc-status {
            font-size: 10px;
            margin-top: 5px;
            padding: 2px 5px;
            border-radius: 10px;
            display: inline-block;
        }

        .status-available {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }

        .status-occupied {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        .status-maintenance {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .pc-placeholder {
            grid-column: 1 / -1;
            text-align: center;
            padding: 20px;
            color: #6c757d;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
        }

        .pc-placeholder i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #4cc9f0;
        }

        /* Button */
        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.3);
        }

        /* Messages */
        .success-message {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 3px solid #28a745;
        }

        .error-message {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 3px solid #dc3545;
        }

        /* History Section */
        .history-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            flex: 1;
            min-width: 350px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .history-section h2 {
            font-size: 24px;
            margin-bottom: 25px;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-align: center;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            text-align: left;
            padding: 12px 15px;
            font-size: 14px;
            color: #aaa;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-weight: 500;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 14px;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.03);
        }

        /* Status Indicators */
        .status-pending {
            color: #ffc107;
            font-weight: 500;
        }

        .status-approved {
            color: #28a745;
            font-weight: 500;
        }

        .status-rejected {
            color: #dc3545;
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .main-content {
                flex-direction: column;
            }
            
            .reservation-form,
            .history-section {
                width: 100%;
                min-width: auto;
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
        }

        @media (max-width: 768px) {
            .pc-grid {
                grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
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
            <li><a href="student_resources.php"><i class="fas fa-file-alt"></i> <span>Resources</span></a></li>
            <li><a href="student_lab_schedule.php"><i class="fas fa-calendar-alt"></i> <span>Schedule</span></a></li>
            <li><a href="reservation.php" class="active"><i class="fas fa-calendar-plus"></i> <span>Reservation</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Log Out</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Reservation Form -->
        <div class="reservation-form">
            <h2>Lab Reservation</h2>
            
            <?php if (isset($success)): ?>
                <div class="success-message"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Student ID</label>
                    <input type="text" value="<?= $student_id ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" value="<?= $student_name ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Remaining Sessions</label>
                    <input type="text" value="<?= $remaining_sessions ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Purpose *</label>
                    <select name="purpose" required>
                        <option value="">Select Purpose</option>
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
                        <option value="Other..">Other..</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Laboratory Room *</label>
                    <select name="lab_number" id="lab_number" required>
                        <option value="">Select Lab</option>
                        <option value="524">524</option>
                        <option value="526">526</option>
                        <option value="528">528</option>
                        <option value="530">530</option>
                        <option value="542">542</option>
                        <option value="544">544</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Select PC *</label>
                    <div class="pc-grid-container">
                        <div class="pc-grid" id="pc_grid">
                            <!-- PC options will be loaded via AJAX -->
                            <div class="pc-placeholder">
                                <i class="fas fa-desktop"></i>
                                <p>Please select a lab first</p>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="pc_number" id="pc_number" required>
                </div>
                
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="date" required>
                </div>
                
                <div class="form-group">
                    <label>Time In</label>
                    <input type="time" name="time_in" value="<?= date('H:i') ?>">
                </div>
                
                <button type="submit" name="submit_reservation">Submit Reservation</button>
            </form>
        </div>
        
        <!-- Reservation History -->
        <div class="history-section">
            <h2>Reservation History</h2>
            <table>
                <thead>
                    <tr>
                        <th>DATE</th>
                        <th>LAB</th>
                        <th>PC</th>
                        <th>TIME</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $history_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['date'] ?></td>
                            <td><?= $row['lab_number'] ?></td>
                            <td><?= $row['pc_number'] ?></td>
                            <td><?= $row['time_in'] ?></td>
                            <td class="status-<?= strtolower($row['status']) ?>">
                                <?php 
                                if ($row['status'] === 'Approved') {
                                    echo '<i class="fas fa-check-circle"></i> Approved';
                                } elseif ($row['status'] === 'Rejected') {
                                    echo '<i class="fas fa-times-circle"></i> Rejected';
                                } else {
                                    echo '<i class="fas fa-clock"></i> Pending';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Load PC availability when lab is selected
        $('#lab_number').change(function() {
            const lab_number = $(this).val();
            const date = $('input[name="date"]').val();
            
            if (lab_number && date) {
                $.ajax({
                    url: 'get_pc_availability.php',
                    type: 'GET',
                    data: { 
                        lab_number: lab_number,
                        date: date
                    },
                    success: function(data) {
                        let html = '';
                        if (data.pcs && data.pcs.length > 0) {
                            for (let i = 1; i <= 25; i++) {
                                const pcNum = 'PC' + i;
                                const pc = data.pcs.find(p => p.number === pcNum);
                                const status = pc ? pc.status : 'available';
                                const statusClass = status === 'available' ? 'available' : 'used';
                                const statusText = status === 'available' ? 'Available' : 'Occupied';
                                
                                html += `<div class="pc-item ${statusClass}" data-pc="${pcNum}">
                                            <i class="fas fa-desktop"></i>
                                            ${pcNum}
                                            <div class="pc-status status-${statusClass}">${statusText}</div>
                                        </div>`;
                            }
                        } else {
                            // Fallback if no data
                            for (let i = 1; i <= 25; i++) {
                                const pcNum = 'PC' + i;
                                html += `<div class="pc-item available" data-pc="${pcNum}">
                                            <i class="fas fa-desktop"></i>
                                            ${pcNum}
                                            <div class="pc-status status-available">Available</div>
                                        </div>`;
                            }
                        }
                        $('#pc_grid').html(html);
                    }
                });
            }
        });

        // Add date change listener to refresh PC availability
        $('input[name="date"]').change(function() {
            if ($('#lab_number').val()) {
                $('#lab_number').trigger('change');
            }
        });
        
        // PC selection
        $(document).on('click', '.pc-item.available', function() {
            $('.pc-item').removeClass('selected');
            $(this).addClass('selected');
            $('#pc_number').val($(this).data('pc'));
        });
    });
    </script>
</body>
</html>