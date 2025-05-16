<?php
require 'config.php'; // Database connection

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];

// Fetch user data
$sql = "SELECT firstname, lastname, email, course, yearlevel, username FROM users WHERE username = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($firstname, $lastname, $email, $course, $yearlevel, $username);
    $stmt->fetch();
    $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $course = trim($_POST['course']);
    $yearlevel = trim($_POST['yearlevel']);
    $new_username = trim($_POST['username']); // Allow changing username

    // Check if the email is already used by another user
    $email_check_sql = "SELECT username FROM users WHERE email = ? AND username != ?";
    if ($stmt = $conn->prepare($email_check_sql)) {
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $_SESSION['error_message'] = "Error: Email is already in use by another account.";
            header("Location: edit-profile.php");
            exit();
        }
        $stmt->close();
    }

    // Update user details
    $update_sql = "UPDATE users SET firstname=?, lastname=?, email=?, course=?, yearlevel=?, username=? WHERE username=?";
    if ($stmt = $conn->prepare($update_sql)) {
        $stmt->bind_param("sssssss", $firstname, $lastname, $email, $course, $yearlevel, $new_username, $username);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully!";
            $_SESSION['username'] = $new_username; // Update session if username is changed
            header("Location: edit-profile.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error updating profile.";
        }
        $stmt->close();
    }

    // Handle profile picture upload
    if (isset($_FILES['profile-picture']) && $_FILES['profile-picture']['error'] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($new_username . ".jpg");
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if file is a valid image type
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES['profile-picture']['tmp_name'], $target_file)) {
                $_SESSION['success_message'] .= " Profile picture updated!";
            } else {
                $_SESSION['error_message'] .= " Error uploading profile picture.";
            }
        } else {
            $_SESSION['error_message'] .= " Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Student Dashboard</title>
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
            flex-direction: column;
            min-height: 100vh;
        }

        /* Edit Profile Container */
        .edit-container {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 700px;
            margin: 0 auto;
            width: 100%;
        }

        .edit-container h2 {
            font-size: 28px;
            margin-bottom: 30px;
            text-align: center;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        /* Form Styles */
        .edit-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #aaa;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: white;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4cc9f0;
            box-shadow: 0 0 0 3px rgba(76, 201, 240, 0.2);
        }

        /* Button Styles */
        .btn-save {
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            color: white;
            border: none;
            padding: 14px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s;
            width: 100%;
            grid-column: span 2;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.4);
        }

        /* Message Styles */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-size: 14px;
            grid-column: span 2;
        }

        .success-message {
            background: rgba(40, 167, 69, 0.2);
            border-left: 3px solid #28a745;
            color: #28a745;
        }

        .error-message {
            background: rgba(220, 53, 69, 0.2);
            border-left: 3px solid #dc3545;
            color: #dc3545;
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
            .edit-form {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            .btn-save {
                grid-column: span 1;
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
            <li><a href="edit-profile.php" class="active"><i class="fas fa-edit"></i> <span>Edit</span></a></li>
            <li><a href="Announcements.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a></li>
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
        <div class="edit-container">
            <h2>Edit Profile</h2>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="message success-message">
                    <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="message error-message">
                    <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <form class="edit-form" method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($firstname ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($lastname ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="course">Course</label>
                    <input type="text" id="course" name="course" value="<?php echo htmlspecialchars($course ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="yearlevel">Year Level</label>
                    <input type="text" id="yearlevel" name="yearlevel" value="<?php echo htmlspecialchars($yearlevel ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                </div>
                
                <div class="form-group full-width">
                    <label for="profile-picture">Profile Picture</label>
                    <input type="file" id="profile-picture" name="profile-picture" accept="image/jpeg, image/png, image/gif">
                </div>
                
                <button type="submit" class="btn-save">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        // Simple animation for form elements
        document.querySelectorAll('.form-group input').forEach((input, index) => {
            input.style.transitionDelay = `${index * 50}ms`;
        });
    </script>
</body>
</html>