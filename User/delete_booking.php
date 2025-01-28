<?php
session_start();
require_once('../includes/db_connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid booking ID.";
    header('Location: my_bookings.php');
    exit();
}

$booking_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

try {
    // Start a transaction to ensure data integrity
    $conn->begin_transaction();

    // First, verify the booking belongs to the logged-in user and is cancelled
    $verify_query = "SELECT BookingID, BookingStatus FROM bookings WHERE BookingID = ? AND UserID = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("ii", $booking_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();

    if ($verify_result->num_rows === 0) {
        throw new Exception("Booking not found or you do not have permission to delete this booking.");
    }

    $booking_data = $verify_result->fetch_assoc();

    // Check if booking is cancelled before deletion
    if ($booking_data['BookingStatus'] !== 'Cancelled') {
        throw new Exception("Only cancelled bookings can be deleted.");
    }

    // Delete the booking
    $delete_query = "DELETE FROM bookings WHERE BookingID = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $booking_id);
    $delete_stmt->execute();

    // Commit the transaction
    $conn->commit();

    // Set success message
    $_SESSION['success_message'] = "Booking successfully deleted.";

    // Redirect back to my bookings page
    header('Location: my_bookings.php');
    exit();

} catch (Exception $e) {
    // Rollback the transaction in case of error
    $conn->rollback();

    // Set error message
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: my_bookings.php');
    exit();
}
?>