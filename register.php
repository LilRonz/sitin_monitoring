<?php
require 'config.php'; // Include database connection

// Initialize variables to avoid undefined variable warnings
$success = $error = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idno = $_POST['idno'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $firstname = $_POST['firstname'] ?? '';
    $midname = $_POST['midname'] ?? '';
    $course = $_POST['course'] ?? '';
    $yearlevel = $_POST['yearlevel'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate required fields
    if (empty($idno) || empty($lastname) || empty($firstname) || empty($course) || empty($yearlevel) || empty($email) || empty($username) || empty($password)) {
        $error = "All fields except 'Middle Name' are required.";
    } else {
        // Check if username or email already exists
        $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Username or email already exists!";
        } else {
            // Hash the password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Modified SQL query to handle auto-increment primary key
            $sql = "INSERT INTO users (idno, lastname, firstname, midname, course, yearlevel, email, username, password, remaining_sessions) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            // Prepare and execute the query
            if ($stmt = $conn->prepare($sql)) {
                $remaining_sessions = 30; // Default value
                $stmt->bind_param("sssssssssi", $idno, $lastname, $firstname, $midname, $course, $yearlevel, $email, $username, $hashed_password, $remaining_sessions);

                if ($stmt->execute()) {
                    $success = "Registration successful! You can now <a href='log-in.php'>login</a>.";
                } else {
                    $error = "Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Database error: Unable to prepare statement.";
            }
        }
        $check_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Page</title>
    <link rel="stylesheet" href="register1.css"> 

    <style>
        body {
            background-image: url('lab.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>
    <div class="content-box">
        <img src="ccs.png" alt="CCS Logo">
        <h2>Registration Form</h2>

        <?php if (!empty($success)): ?>
            <p style="color: green;"><?php echo $success; ?></p>
        <?php elseif (!empty($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <label for="idno">ID Number:</label>
                <input type="text" id="idno" name="idno" required>
            </div>

            <div class="form-row">
                <label for="lastname">Last Name:</label>
                <input type="text" id="lastname" name="lastname" required>
            </div>

            <div class="form-row">
                <label for="firstname">First Name:</label>
                <input type="text" id="firstname" name="firstname" required>
            </div>

            <div class="form-row">
                <label for="midname">Middle Name:</label>
                <input type="text" id="midname" name="midname">
            </div>

            <div class="form-row">
                <label for="course">Course:</label>
                <select id="course" name="course" required>
                    <option value="">Select Course</option>
                    <option value="Bachelor of Science in Information Technology">BS Information Technology</option>
                    <option value="Bachelor of Science in Computer Science">BS Computer Science</option>
                    <option value="Bachelor of Science in Computer Engineering">BS Computer Engineering</option>
                    <option value="Bachelor of Science in Civil Engineering">BS Civil Engineering</option>
                    <option value="Bachelor of Science in Mechanical Engineering">BS Mechanical Engineering</option>
                    <option value="Bachelor of Science in Electrical Engineering">BS Electrical Engineering</option>
                    <option value="Bachelor of Science in Industrial Engineering">BS Industrial Engineering</option>
                    <option value="Bachelor of Science in Criminology">BS Criminology</option>
                    <option value="Bachelor of Science in Accountancy">BS Accountancy</option>
                    <option value="Bachelor of Science in Political Science">BS Political Science</option>
                    <option value="Bachelor of Science in Social Works">BS Social Works</option>
                </select>
            </div>

            <div class="form-row">
                <label for="yearlevel">Year Level:</label>
                <select id="yearlevel" name="yearlevel" required>
                    <option value="">Select Year Level</option>
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                </select>
            </div>

            <div class="form-row">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-row">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-row">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="button-group">
                <button type="submit">Register</button>
                <button type="reset">Clear</button>
            </div>

            <p class="login-link">Already have an account? <a href="log-in.php">Login here</a></p>
        </form>
    </div>
</body>
</html>