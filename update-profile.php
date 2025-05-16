<?php
session_start();
include 'config.php'; // Ensure database connection is included

// Debugging: Check if session is properly set
if (!isset($_SESSION['idno'])) {
    die("Session IDNO is not set! Check login process.");
}

// Retrieve session data
$idno = $_SESSION['idno'];

// Fetch user data
$sql = "SELECT * FROM users WHERE idno=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $idno);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    die("User not found. Ensure that the user exists in the database.");
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $midname = mysqli_real_escape_string($conn, $_POST['midname']);
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $yearlevel = mysqli_real_escape_string($conn, $_POST['yearlevel']);

    // Update the user's information in the database
    $sql = "UPDATE users SET firstname=?, midname=?, lastname=?, email=?, course=?, yearlevel=? WHERE idno=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $firstname, $midname, $lastname, $email, $course, $yearlevel, $idno);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating profile: " . $stmt->error;
    }

    header("Location: edit-profile.php");
    var_dump($_SESSION);
    exit();
}
?>

