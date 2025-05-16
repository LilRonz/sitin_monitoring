<?php
include 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize default statuses if not set
$defaultStatuses = [
    'mw' => [
        '7:30AM–9:00AM' => ['517'=>'available', '524'=>'available', '526'=>'occupied', '528'=>'available', '530'=>'available', '542'=>'occupied'],
        '9:00AM–10:30AM' => ['517'=>'occupied', '524'=>'available', '526'=>'occupied', '528'=>'available', '530'=>'occupied', '542'=>'available'],
        // ... add all other time slots
    ],
    // ... other days
];

foreach ($defaultStatuses as $day => $times) {
    foreach ($times as $time => $labs) {
        foreach ($labs as $lab => $status) {
            if (!isset($_SESSION['lab_status'][$day][$time][$lab])) {
                $_SESSION['lab_status'][$day][$time][$lab] = $status;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Schedule - Admin</title>
    <style>
        /* Global Styling */
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
        }

        .main-content h1 {
            font-size: 28px;
            font-weight: 600;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
            margin-bottom: 15px;
        }

        .main-content p {
            font-size: 14px;
            color: #aaa;
            margin-bottom: 30px;
        }

        /* Toggle Buttons */
        .toggle-container {
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .toggle-btn {
            padding: 12px 20px;
            background: rgba(76, 201, 240, 0.1);
            border: 1px solid rgba(76, 201, 240, 0.3);
            border-radius: 8px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 500;
        }

        .toggle-btn:hover, .toggle-btn.active {
            background: rgba(76, 201, 240, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.2);
        }

        /* Table Styling */
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .schedule-table th, 
        .schedule-table td {
            padding: 16px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .schedule-table th {
            background: rgba(0, 0, 0, 0.3);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 1px;
            color: #4cc9f0;
        }

        .schedule-table tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        /* Status Toggle Buttons */
        .status-toggle {
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            color: white;
            font-weight: 500;
            font-size: 13px;
            min-width: 100px;
        }

        .status-toggle.available {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.5);
        }

        .status-toggle.occupied {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.5);
        }

        .status-toggle:hover {
            transform: scale(1.05);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }

        /* Day Header */
        .day-header {
            font-size: 22px;
            margin: 30px 0 15px 0;
            color: #4cc9f0;
            font-weight: 500;
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
            .schedule-table {
                display: block;
                overflow-x: auto;
            }
            
            .toggle-container {
                flex-direction: column;
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
            <li><a href="student_lab_schedule.php" class="active"><i class="fas fa-calendar-alt"></i> <span>Schedule</span></a></li>
            <li><a href="student_reservation.php"><i class="fas fa-calendar-plus"></i> <span>Reservation</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Log Out</span></a></li>
        </ul>
    </div>
    <!-- Main Content -->
    <div class="main-content">
        <h1>Lab Schedule</h1>
        <p>Current lab availability status and management</p>

        <div class="toggle-container">
            <button onclick="showSchedule('mw')" class="toggle-btn active">Mon/Wed</button>
            <button onclick="showSchedule('tt')" class="toggle-btn">Tues/Thurs</button>
            <button onclick="showSchedule('fri')" class="toggle-btn">Friday</button>
            <button onclick="showSchedule('sat')" class="toggle-btn">Saturday</button>
        </div>

        <!-- Monday/Wednesday Schedule -->
        <div id="schedule-mw" class="schedule-section">
            <h2 class="day-header">Monday/Wednesday</h2>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Time Slot</th>
                        <th>Lab 517</th>
                        <th>Lab 524</th>
                        <th>Lab 526</th>
                        <th>Lab 528</th>
                        <th>Lab 530</th>
                        <th>Lab 542</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $timeslots = [
                        '7:30AM–9:00AM',
                        '9:00AM–10:30AM',
                        '10:30AM–12:00PM',
                        '12:00PM–1:00PM',
                        '1:00PM–3:00PM',
                        '3:00PM–4:30PM'
                    ];
                    
                    foreach ($timeslots as $time): ?>
                    <tr>
                        <td><?= $time ?></td>
                        <?php foreach (['517', '524', '526', '528', '530', '542'] as $lab): 
                            $status = $_SESSION['lab_status']['mw'][$time][$lab] ?? 'available';
                        ?>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="day" value="mw">
                                <input type="hidden" name="time" value="<?= $time ?>">
                                <input type="hidden" name="lab" value="<?= $lab ?>">
                                <button type="submit" name="toggle_status" class="status-toggle <?= $status ?>">
                                    ● <?= ucfirst($status) ?>
                                </button>
                            </form>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Tuesday/Thursday Schedule -->
        <div id="schedule-tt" class="schedule-section" style="display:none;">
            <h2 class="day-header">Tuesday/Thursday</h2>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Time Slot</th>
                        <th>Lab 517</th>
                        <th>Lab 524</th>
                        <th>Lab 526</th>
                        <th>Lab 528</th>
                        <th>Lab 530</th>
                        <th>Lab 542</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($timeslots as $time): ?>
                    <tr>
                        <td><?= $time ?></td>
                        <?php foreach (['517', '524', '526', '528', '530', '542'] as $lab): 
                            $status = $_SESSION['lab_status']['tt'][$time][$lab] ?? 'available';
                        ?>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="day" value="tt">
                                <input type="hidden" name="time" value="<?= $time ?>">
                                <input type="hidden" name="lab" value="<?= $lab ?>">
                                <button type="submit" name="toggle_status" class="status-toggle <?= $status ?>">
                                    ● <?= ucfirst($status) ?>
                                </button>
                            </form>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Friday Schedule -->
        <div id="schedule-fri" class="schedule-section" style="display:none;">
            <h2 class="day-header">Friday</h2>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Time Slot</th>
                        <th>Lab 517</th>
                        <th>Lab 524</th>
                        <th>Lab 526</th>
                        <th>Lab 528</th>
                        <th>Lab 530</th>
                        <th>Lab 542</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($timeslots as $time): ?>
                    <tr>
                        <td><?= $time ?></td>
                        <?php foreach (['517', '524', '526', '528', '530', '542'] as $lab): 
                            $status = $_SESSION['lab_status']['fri'][$time][$lab] ?? 'available';
                        ?>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="day" value="fri">
                                <input type="hidden" name="time" value="<?= $time ?>">
                                <input type="hidden" name="lab" value="<?= $lab ?>">
                                <button type="submit" name="toggle_status" class="status-toggle <?= $status ?>">
                                    ● <?= ucfirst($status) ?>
                                </button>
                            </form>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Saturday Schedule -->
        <div id="schedule-sat" class="schedule-section" style="display:none;">
            <h2 class="day-header">Saturday</h2>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Time Slot</th>
                        <th>Lab 517</th>
                        <th>Lab 524</th>
                        <th>Lab 526</th>
                        <th>Lab 528</th>
                        <th>Lab 530</th>
                        <th>Lab 542</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($timeslots as $time): ?>
                    <tr>
                        <td><?= $time ?></td>
                        <?php foreach (['517', '524', '526', '528', '530', '542'] as $lab): 
                            $status = $_SESSION['lab_status']['sat'][$time][$lab] ?? 'available';
                        ?>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="day" value="sat">
                                <input type="hidden" name="time" value="<?= $time ?>">
                                <input type="hidden" name="lab" value="<?= $lab ?>">
                                <button type="submit" name="toggle_status" class="status-toggle <?= $status ?>">
                                    ● <?= ucfirst($status) ?>
                                </button>
                            </form>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function showSchedule(day) {
        // Update toggle buttons
        document.querySelectorAll('.toggle-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
        
        // Show selected schedule
        const sections = ['mw', 'tt', 'fri', 'sat'];
        sections.forEach(section => {
            const element = document.getElementById('schedule-' + section);
            if (element) {
                element.style.display = (section === day) ? 'block' : 'none';
            }
        });
    }
    </script>
</body>
</html>