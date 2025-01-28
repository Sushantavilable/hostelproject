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

    // First, verify the booking belongs to the logged-in user
    $verify_query = "SELECT RoomID, BookingStatus FROM bookings WHERE BookingID = ? AND UserID = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("ii", $booking_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();

    if ($verify_result->num_rows === 0) {
        throw new Exception("Booking not found or you do not have permission to cancel this booking.");
    }

    $booking_data = $verify_result->fetch_assoc();

    // Check if booking is already cancelled
    if ($booking_data['BookingStatus'] === 'Cancelled') {
        throw new Exception("This booking is already cancelled.");
    }

    // Update booking status to Cancelled
    $cancel_booking_query = "UPDATE bookings SET BookingStatus = 'Cancelled' WHERE BookingID = ?";
    $cancel_stmt = $conn->prepare($cancel_booking_query);
    $cancel_stmt->bind_param("i", $booking_id);
    $cancel_stmt->execute();

    // Update room availability status to Available
    $update_room_query = "UPDATE rooms SET AvailabilityStatus = 'Available' WHERE RoomID = ?";
    $update_room_stmt = $conn->prepare($update_room_query);
    $update_room_stmt->bind_param("i", $booking_data['RoomID']);
    $update_room_stmt->execute();

    // Commit the transaction
    $conn->commit();

    // Set success message
    $_SESSION['success_message'] = "Booking successfully cancelled.";

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