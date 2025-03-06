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

// Check if the user has any active bookings (Pending or Confirmed)
$booking_check_query = "SELECT COUNT(*) as booking_count FROM bookings 
                        WHERE UserID = ? AND BookingStatus IN ('Pending', 'Confirmed')";
$booking_stmt = $conn->prepare($booking_check_query);
$booking_stmt->bind_param("i", $user_id);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();
$booking_data = $booking_result->fetch_assoc();

// If user has active bookings, prevent deletion
if ($booking_data['booking_count'] > 0) {
    $_SESSION['message'] = "Cannot delete user with active bookings. Please cancel or complete their bookings first.";
    $_SESSION['message_type'] = 'warning';
    header("Location: users.php");
    exit;
}

// Delete the user if no active bookings
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
$booking_stmt->close();
$delete_stmt->close();
$conn->close();

// Redirect back to users page
header("Location: users.php");
exit;
?>