<?php
include 'header.php';
require_once('../includes/db_connection.php');

$booking_id = intval($_GET['booking_id']);

// Fetch booking details
$query = "SELECT b.*, r.RoomNumber, r.RoomType, h.Name as HostelName 
          FROM bookings b 
          JOIN rooms r ON b.RoomID = r.RoomID 
          JOIN hostels h ON b.HostelID = h.HostelID 
          WHERE b.BookingID = ? AND b.UserID = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: my_bookings.php");
    exit();
}

$booking = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html>

<head>
    <title>Booking Confirmation</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .success-message {
            text-align: center;
            color: #28a745;
            margin-bottom: 30px;
        }

        .booking-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }

        .btn-view-bookings {
            background-color: #2196F3;
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .btn-view-bookings:hover {
            background-color: #1976D2;
        }
    </style>
</head>

<body>
    <div class="confirmation-container">
        <div class="success-message">
            <h1><i class="fas fa-check-circle"></i> Booking Confirmed!</h1>
            <p>Your room has been successfully booked.</p>
        </div>

        <div class="booking-details">
            <?php if ($booking): ?>
                <h2>Booking Details</h2>
                <p><strong>Booking ID:</strong> #<?php echo htmlspecialchars($booking['BookingID']); ?></p>
                <p><strong>Hostel:</strong> <?php echo htmlspecialchars($booking['HostelName']); ?></p>
                <p><strong>Room Number:</strong> <?php echo htmlspecialchars($booking['RoomNumber']); ?></p>
                <p><strong>Room Type:</strong> <?php echo htmlspecialchars($booking['RoomType']); ?></p>
                <p><strong>Check-in Date:</strong> <?php echo date('F d, Y', strtotime($booking['CheckInDate'])); ?></p>
                <p><strong>Check-out Date:</strong> <?php echo date('F d, Y', strtotime($booking['CheckOutDate'])); ?></p>
                <p><strong>Number of Occupants:</strong> <?php echo htmlspecialchars($booking['NumberOfOccupants']); ?></p>
                <p><strong>Total Price:</strong> Rs. <?php echo htmlspecialchars($booking['TotalPrice']); ?></p>
                <p><strong>Booking Status:</strong> <?php echo htmlspecialchars($booking['BookingStatus']); ?></p>
            <?php endif; ?>
        </div>


        <div class="action-buttons">
            <a href="my_bookings.php" class="btn-view-bookings">View My Bookings</a>
        </div>
    </div>

    
</body>

</html>

<?php include 'footer.php'; ?>