<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid user ID";
    $_SESSION['message_type'] = 'danger';
    header("Location: users.php");
    exit;
}

include('../includes/db_connection.php');

$user_id = (int)$_GET['id'];

// First, check if the user exists
$check_query = "SELECT Username FROM users WHERE UserID = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "User not found";
    $_SESSION['message_type'] = 'danger';
    header("Location: users.php");
    exit;
}

// Delete the user
$delete_query = "DELETE FROM users WHERE UserID = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param("i", $user_id);

if ($delete_stmt->execute()) {
    $_SESSION['message'] = "User successfully deleted";
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = "Error deleting user: " . $conn->error;
    $_SESSION['message_type'] = 'danger';
    error_log("Error deleting user ID $user_id: " . $conn->error);
}

// Close statements
$stmt->close();
$delete_stmt->close();
$conn->close();

// Redirect back to users page
header("Location: users.php");
exit;
?>