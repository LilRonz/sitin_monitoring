<?php
include 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

// Check if an announcement ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid request.");
}

$id = intval($_GET['id']);

// Fetch existing announcement data
$sql = "SELECT * FROM announcements WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Announcement not found.");
}

$announcement = $result->fetch_assoc();

// Handle update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    $update_sql = "UPDATE announcements SET title = '$title', message = '$message' WHERE id = $id";

    if ($conn->query($update_sql) === TRUE) {
        header("Location: admin_announcement.php?success=Announcement updated successfully!");
        exit();
    } else {
        $error = "Error updating announcement: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Announcement | Admin Panel</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #121212; /* Dark background */
            color: #fff;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar (consistent with dashboard) */
        .sidebar {
            width: 250px;
            background: #1a1a1a;
            padding: 20px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            border-right: 1px solid #333;
        }

        .sidebar h2 {
            text-align: center;
            color: #007bff;
            margin-bottom: 25px;
            font-size: 1.5rem;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar ul li {
            margin: 15px 0;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: #ddd;
            display: block;
            padding: 10px 15px;
            border-radius: 5px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .sidebar ul li a:hover {
            background: #007bff;
            color: white;
        }

        .sidebar ul li a.active {
            background: #007bff;
            color: white;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 30px;
        }

        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: #1e1e1e;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            border: 1px solid #333;
        }

        h2 {
            color: #007bff;
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.8rem;
        }

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
            border: 1px solid #444;
            border-radius: 6px;
            background: #2a2a2a;
            color: #fff;
            font-size: 1rem;
            transition: border 0.3s;
        }

        input[type="text"]:focus,
        textarea:focus {
            border-color: #007bff;
            outline: none;
        }

        textarea {
            resize: vertical;
            min-height: 150px;
        }

        button {
            padding: 12px 25px;
            border: none;
            background: #007bff;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
            margin-right: 10px;
        }

        button:hover {
            background: #0056b3;
        }

        button[type="button"] {
            background: #6c757d;
        }

        button[type="button"]:hover {
            background: #5a6268;
        }

        .error {
            color: #dc3545;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Sidebar (matches dashboard) -->
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="admin_student_list.php">List of Students</a></li>
            <li><a href="admin_announcement.php" class="active">Create Announcement</a></li>
            <li><a href="manage_sitins.php">Manage Sit-ins</a></li>
            <li><a href="sit_in_records.php">Sit-in Records</a></li>
            <li><a href="leaderboard.php">Reward Leaderboard</a></li>
            <li><a href="feedback.php">Feedback Reports</a></li>
            <li><a href="lab_schedule.php">Lab Schedule</a></li>
            <li><a href="admin_upload_resource.php">Upload Resources</a></li>
            <li><a href="#">Settings</a></li>
            <li><a href="admin_logout.php">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="form-container">
            <h2>Edit Announcement</h2>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

            <form method="POST">
                <label>Title:</label>
                <input type="text" name="title" value="<?= htmlspecialchars($announcement['title']) ?>" required>

                <label>Message:</label>
                <textarea name="message" required><?= htmlspecialchars($announcement['message']) ?></textarea>

                <button type="submit">Update Announcement</button>
                <button type="button" onclick="window.location.href='admin_announcement.php'">Cancel</button>
            </form>
        </div>
    </div>
</body>
</html>