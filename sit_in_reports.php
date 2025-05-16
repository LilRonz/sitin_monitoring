<?php
include 'config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin'])) {
    echo "<p style='color: red;'>Error: Unauthorized access. Only administrators can view this page.</p>";
    header("Refresh: 2; url=index.php");
    exit();
}

// Fetch historical sit-in records
$query = "
SELECT h.id, h.student_idno, CONCAT(u.firstname, ' ', u.lastname) AS fullname, 
       h.lab_classroom, h.purpose, h.time_in, h.sitin_time
FROM sitin_history h
JOIN users u ON h.student_idno = u.idno
ORDER BY h.sitin_time DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in Records</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Global Styling */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: url('p1.png') no-repeat center center fixed;
            background-size: cover;
            color: white;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 250px;
            background: #000;
            padding: 20px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
        }

        .sidebar h2 {
            text-align: center;
            color: white;
            margin-bottom: 20px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 15px 0;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: white;
            display: block;
            padding: 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .sidebar ul li a:hover {
            background: #007bff;
        }

        .main-content {
            margin-left: 270px;
            padding: 30px;
            width: calc(100% - 270px);
        }

        .page-header {
            background-color: rgba(0, 0, 0, 0.6);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .page-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #ccc;
            margin-bottom: 15px;
        }

        .back-btn {
            display: inline-block;
            padding: 8px 15px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: rgba(0, 0, 0, 0.6);
        }

        th, td {
            border: 1px solid #444;
            padding: 12px;
            text-align: left;
        }

        th {
            background: #007bff;
            color: white;
        }

        tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 0.05);
        }

        tr:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .active {
            color: #ff6b6b;
            font-weight: bold;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <ul>
        <li><a href="admin_dashboard.php">Dashboard</a></li>
        <li><a href="admin_student_list.php">List of Students</a></li>
        <li><a href="admin_announcement.php">Create Announcement</a></li>
        <li><a href="manage_sitins.php">Manage Sit-ins</a></li>
        <li><a href="sit_in_records.php" style="background: #007bff;">Sit-in Records</a></li>
        <li><a href="sit_in_reports.php">Sit-in Reports</a></li>
        <li><a href="feedback.php">Feedback Reports</a></li>
        <li><a href="#">Settings</a></li>
        <li><a href="admin_logout.php">Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="page-header">
        <h1>All Sit-in Reports</h1>
        <p>View all students' sit-in sessions.</p>
    </div>

    <table>
        <tr>
            <th>Student Name</th>
            <th>Purpose</th>
            <th>Lab</th>
            <th>Start Time</th>
            <th>End Time</th>
        </tr>
        
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                    <td><?= htmlspecialchars($row['purpose']) ?></td>
                    <td>Lab <?= htmlspecialchars($row['lab_classroom']) ?></td>
                    <td><?= htmlspecialchars(date("F d, Y - h:i A", strtotime($row['time_in']))) ?></td>
                    <td><?= htmlspecialchars(date("F d, Y - h:i A", strtotime($row['sitin_time']))) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="text-align: center;">No sit-in records found.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>
</body>
</html>