<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit;
}

// Include database connection
require_once('../includes/db_connection.php');

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $hostelId = intval($_GET['id']);
    
    // Check for active bookings
    $bookingQuery = "SELECT COUNT(*) as booking_count FROM bookings 
                    WHERE HostelID = ? AND BookingStatus IN ('Pending', 'Confirmed')";
    $bookingStmt = $conn->prepare($bookingQuery);
    $bookingStmt->bind_param("i", $hostelId);
    $bookingStmt->execute();
    $bookingResult = $bookingStmt->get_result()->fetch_assoc();

    // Check for booked rooms
    $roomQuery = "SELECT COUNT(*) as room_count FROM rooms 
                 WHERE HostelID = ? AND AvailabilityStatus = 'Booked'";
    $roomStmt = $conn->prepare($roomQuery);
    $roomStmt->bind_param("i", $hostelId);
    $roomStmt->execute();
    $roomResult = $roomStmt->get_result()->fetch_assoc();

    if ($bookingResult['booking_count'] > 0) {
        $_SESSION['error'] = "Cannot delete hostel - it has " . $bookingResult['booking_count'] . " active booking(s)";
    } elseif ($roomResult['room_count'] > 0) {
        $_SESSION['error'] = "Cannot delete hostel - it has " . $roomResult['room_count'] . " booked room(s)";
    } else {
        // First, find and delete the hostel admin
        $adminQuery = "DELETE FROM admins WHERE AssignedHostelID = ? AND Role = 'hostel_admin'";
        $adminStmt = $conn->prepare($adminQuery);
        $adminStmt->bind_param("i", $hostelId);
        $adminStmt->execute();
        $adminStmt->close();
        
        // Then delete the hostel
        $deleteQuery = "DELETE FROM hostels WHERE HostelID = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $hostelId);
        
        if ($deleteStmt->execute()) {
            $_SESSION['message'] = "Hostel and associated admin deleted successfully.";
        } else {
            $_SESSION['error'] = "Error deleting hostel: " . $conn->error;
        }
        $deleteStmt->close();
    }

    $bookingStmt->close();
    $roomStmt->close();
    $conn->close();
    
    header("Location: view_hostels.php");
    exit;
}
?>