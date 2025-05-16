<?php
session_start();

// Hardcoded admin credentials (consider moving to database later)
$admin_username = "admin";
$admin_password = "admin123"; // In production, use password_hash() and verify

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check credentials
    if ($username === $admin_username && $password === $admin_password) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set BOTH session variables for compatibility
        $_SESSION['admin'] = true;          // General admin flag
        $_SESSION['admin_id'] = 1;          // Static ID since you're using hardcoded auth
        $_SESSION['admin_username'] = $username; // For logging/display
        
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Invalid admin username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style> 
        /* Center the login form */
body {
    background: url('lab.jpg') no-repeat center center fixed;
    background-size: cover;
    font-family: Arial, sans-serif;
}

.container {
    width: 100%;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-box {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    text-align: center;
    width: 350px;
}

.logo {
    width: 100px;
    margin-bottom: 10px;
}

h2 {
    font-size: 20px;
    margin-bottom: 15px;
}

input[type="text"], input[type="password"] {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #ccc;
    border-radius: 5px;
}

button {
    width: 100%;
    padding: 10px;
    border: none;
    border-radius: 5px;
    color: white;
    cursor: pointer;
}

.login-btn {
    background: #007bff;
}

.login-btn:hover {
    background: #0056b3;
}

    </style>
</head>
<body>

<div class="container">
    <div class="login-box">
        <img src="ccs.png" alt="Logo" class="logo">
        <h2>Admin Login</h2>

        <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>

        <form method="post" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="login-btn">Login</button>
        </form>
    </div>
</div>

</body>
</html>
