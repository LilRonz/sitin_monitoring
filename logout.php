<?php
session_destroy(); // Destroy session
header("Location: log-in.php"); // Redirect to login page
exit();
?>