<?php
require 'config.php'; // Database connection (session is already started in config.php)

// Remove session_start() from here to prevent multiple calls

$error = ""; // Initialize error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['user'] ?? '');
    $password = trim($_POST['pass'] ?? '');

    // Prepared statement to prevent SQL injection
    $sql = "SELECT idno, username, password FROM users WHERE username = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($idno, $db_username, $db_password);
            $stmt->fetch();

            // Verify the password
            if (password_verify($password, $db_password)) {
                // Set session variables
                $_SESSION['username'] = $db_username; // Store username
                $_SESSION['student_id'] = $idno; // Store student ID (idno)

                // Redirect to dashboard or reservation page
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid Username or Password!";
            }
        } else {
            $error = "Invalid Username or Password!";
        }
        $stmt->close();
    } else {
        $error = "Database error: Unable to prepare statement.";
    }
}
?>




<!DOCTYPE html>
<html lang='en'>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Sitin Monitoring System</title>
    <link rel="stylesheet" href="styles.css">
    
    <style>
        body {
            background-image: url('lab.jpg'); /* Ensure the image is in the same folder */
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
        <h2>CCS Sit-in Monitoring System</h2>
        
        <?php if (!empty($error)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <label for="user">Username:</label>
                <input type="text" id="user" name="user" required>
            </div>
            <div class="form-row">
                <label for="pass">Password:</label>
                <input type="password" id="pass" name="pass" required>
            </div>
            <div class="button-group">
                <button type="submit">Login</button>
                <button type="button" onclick="window.location.href='register.php'">Register</button>
            </div>
        </form>
    </div>
</body>
</html>
