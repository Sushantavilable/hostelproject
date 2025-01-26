<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to admin login page
header("Location: ../user/login.php");
exit;
