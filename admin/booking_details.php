<?php
session_start();
require_once('../includes/db_connection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_bookings.php");
    exit;
}

$booking_id = intval($_GET['id']);

// Handle booking status change
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    try {
        // Start a transaction to ensure data consistency
        $conn->begin_transaction();

        switch ($action) {
            case 'approve':
                // Update booking status to Confirmed
                $update_booking_sql = "UPDATE bookings SET BookingStatus = 'Confirmed' WHERE BookingID = ?";
                $update_room_sql = "UPDATE rooms r 
                                    JOIN bookings b ON b.RoomID = r.RoomID 
                                    SET r.AvailabilityStatus = 'Booked' 
                                    WHERE b.BookingID = ?";
                $redirect = false; // Do not redirect
                break;
            case 'unconfirm':
                // Update booking status back to Pending
                $update_booking_sql = "UPDATE bookings SET BookingStatus = 'Pending' WHERE BookingID = ?";
                $update_room_sql = "UPDATE rooms r 
                                    JOIN bookings b ON b.RoomID = r.RoomID 
                                    SET r.AvailabilityStatus = 'Available' 
                                    WHERE b.BookingID = ?";
                $redirect = false; // Do not redirect
                break;
            case 'cancel':
                // Update booking status to Cancelled
                $update_booking_sql = "UPDATE bookings SET BookingStatus = 'Cancelled' WHERE BookingID = ?";
                $update_room_sql = "UPDATE rooms r 
                                    JOIN bookings b ON b.RoomID = r.RoomID 
                                    SET r.AvailabilityStatus = 'Available' 
                                    WHERE b.BookingID = ?";
                $redirect = false; // Do not redirect
                break;
            case 'uncancel':
                // Update booking status back to Pending
                $update_booking_sql = "UPDATE bookings SET BookingStatus = 'Pending' WHERE BookingID = ?";
                $update_room_sql = "UPDATE rooms r 
                                    JOIN bookings b ON b.RoomID = r.RoomID 
                                    SET r.AvailabilityStatus = 'Pending' 
                                    WHERE b.BookingID = ?";
                $redirect = false; // Do not redirect
                break;
            case 'delete':
                // First, update room availability
                $update_room_sql = "UPDATE rooms r 
                                    JOIN bookings b ON b.RoomID = r.RoomID 
                                    SET r.AvailabilityStatus = 'Available' 
                                    WHERE b.BookingID = ?";
                // Then delete the booking
                $delete_booking_sql = "DELETE FROM bookings WHERE BookingID = ?";
                $redirect = true; // Redirect to view bookings page
                break;
            default:
                throw new Exception("Invalid action");
        }

        // Prepare and execute statements
        if ($action === 'delete') {
            // For delete, we need to update room status first
            $stmt = $conn->prepare($update_room_sql);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();

            // Then delete the booking
            $stmt = $conn->prepare($delete_booking_sql);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
        } else {
            // For approve, unconfirm, cancel, uncancel
            $stmt = $conn->prepare($update_booking_sql);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();

            $stmt = $conn->prepare($update_room_sql);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
        }

        // Commit the transaction
        $conn->commit();

        // Redirect based on the action
        if ($redirect) {
            header("Location: view_bookings.php");
            exit;
        }
    } catch (Exception $e) {
        // Rollback the transaction in case of error
        $conn->rollback();
        die("Error processing action: " . $e->getMessage());
    }
}

// Fetch detailed booking information
$sql = "SELECT 
            b.BookingID, 
            u.FullName AS UserName, 
            u.Email AS UserEmail,
            u.PhoneNumber AS UserPhone,
            h.Name AS HostelName, 
            h.Address AS HostelAddress,
            r.RoomNumber,
            r.RoomType,
            r.PricePerMonth,
            b.CheckInDate, 
            b.CheckOutDate, 
            b.TotalPrice, 
            b.BookingStatus, 
            b.CreatedAt 
        FROM bookings b
        JOIN users u ON b.UserID = u.UserID
        JOIN rooms r ON b.RoomID = r.RoomID
        JOIN hostels h ON r.HostelID = h.HostelID
        WHERE b.BookingID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Booking not found");
}

$booking = $result->fetch_assoc();

// Calculate booking duration
$check_in = new DateTime($booking['CheckInDate']);
$check_out = new DateTime($booking['CheckOutDate']);
$duration = $check_in->diff($check_out);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
   
</head>

<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <div class="main">
            <?php include 'navbar.php'; ?>

            <h2>Booking Details</h2>
            <div class="header-actions">
                <a href="view_bookings.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            <div class="booking-details">
                <div>
                    <h3>Booking Information</h3>
                    <div class="detail-group">
                        <label>Booking ID:</label>
                        <span><?php echo htmlspecialchars($booking['BookingID']); ?></span>
                    </div>
                    <div class="detail-group">
                        <label>Status:</label>
                        <span class="status-badge status-<?php echo strtolower($booking['BookingStatus']); ?>">
                            <?php echo htmlspecialchars($booking['BookingStatus']); ?>
                        </span>
                    </div>
                    <div class="detail-group">
                        <label>Check-In Date:</label>
                        <span><?php echo date('d M Y', strtotime($booking['CheckInDate'])); ?></span>
                    </div>
                    <div class="detail-group">
                        <label>Check-Out Date:</label>
                        <span><?php echo date('d M Y', strtotime($booking['CheckOutDate'])); ?></span>
                    </div>
                    <div class="detail-group">
                        <label>Duration:</label>
                        <span><?php echo $duration->format('%m months, %d days'); ?></span>
                    </div>
                    <div class="detail-group">
                        <label>Total Price:</label>
                        <span>Rs. <?php echo number_format($booking['TotalPrice'], 2); ?></span>
                    </div>
                </div>

                <div>
                    <h3>Room & Hostel Details</h3>
                    <div class="detail-group">
                        <label>Hostel Name:</label>
                        <span><?php echo htmlspecialchars($booking['HostelName']); ?></span>
                    </div>
                    <div class="detail-group">
                        <label>Hostel Address:</label>
                        <span><?php echo htmlspecialchars($booking['HostelAddress']); ?></span>
                    </div>
                    <div class="detail-group">
                        <label>Room Number:</label>
                        <span><?php echo htmlspecialchars($booking['RoomNumber']); ?></span>
                    </div>
                    <div class="detail-group">
                        <label>Room Type:</label>
                        <span><?php echo htmlspecialchars($booking['RoomType']); ?></span>
                    </div>
                    <div class="detail-group">
                        <label>Price Per Month:</label>
                        <span>Rs. <?php echo number_format($booking['PricePerMonth'], 2); ?></span>
                    </div>
                </div>

                <div>
                    <h3>User Information</h3>
                    <div class="detail-group">
                        <label>Full Name:</label>
                        <span><?php echo htmlspecialchars($booking['UserName']); ?></span>
                    </div>
                    <div class="detail-group">
                        <label>Email:</label>
                        <span><?php echo htmlspecialchars($booking['UserEmail']); ?></span>
                    </div>
                    <div class="detail-group">
                        <label>Phone Number:</label>
                        <span><?php echo htmlspecialchars($booking['UserPhone'] ?? 'N/A'); ?></span>
                    </div>
                </div>
            </div>

            <div class="action-container">
                <?php if ($booking['BookingStatus'] == 'Pending'): ?>
                    <a href="?id=<?php echo $booking_id; ?>&action=approve"
                        class="action-btn btn-approve"
                        onclick="return confirm('Are you sure you want to approve this booking?');">
                        <i class="fas fa-check"></i> Approve
                    </a>
                    <a href="?id=<?php echo $booking_id; ?>&action=cancel"
                        class="action-btn btn-cancel"
                        onclick="return confirm('Are you sure you want to cancel this booking?');">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                <?php elseif ($booking['BookingStatus'] == 'Confirmed'): ?>
                    <a href="?id=<?php echo $booking_id; ?>&action=unconfirm"
                        class="action-btn btn-cancel"
                        onclick="return confirm('Are you sure you want to unconfirm this booking?');">
                        <i class="fas fa-undo"></i> Unconfirm
                    </a>
                <?php elseif ($booking['BookingStatus'] == 'Cancelled'): ?>
                    <a href="?id=<?php echo $booking_id; ?>&action=uncancel"
                        class="action-btn btn-approve"
                        onclick="return confirm('Are you sure you want to uncancel this booking?');">
                        <i class="fas fa-undo"></i> Uncancel
                    </a>
                <?php endif; ?>
                <a href="?id=<?php echo $booking_id; ?>&action=delete"
                    class="action-btn btn-delete"
                    onclick="return confirm('Are you sure you want to delete this booking? This cannot be undone.');">
                    <i class="fas fa-trash"></i> Delete
                </a>
            </div>
        </div>
    </div>
    </div>
</body>

</html>
<?php
// Close the database connection
$conn->close();
?>