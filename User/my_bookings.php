<?php
include 'header.php';

require_once('../includes/db_connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
}

$user_id = $_SESSION['user_id'];

// Fetch user's bookings with hostel and room details
$booking_query = "
    SELECT 
        b.BookingID, 
        b.CheckInDate, 
        b.CheckOutDate, 
        b.TotalPrice, 
        b.BookingStatus,
        h.Name AS HostelName, 
        r.RoomNumber, 
        r.RoomType
    FROM 
        bookings b
    JOIN 
        rooms r ON b.RoomID = r.RoomID
    JOIN 
        hostels h ON r.HostelID = h.HostelID
    WHERE 
        b.UserID = ?
    ORDER BY 
        b.CreatedAt DESC
";

$stmt = $conn->prepare($booking_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        /* Bookings Page Styles */
        .my-bookings {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
        }

        .my-bookings h1 {
            text-align: center;
            color: #333;
            margin-bottom: 2rem;
        }

        .bookings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .booking-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            transition: transform 0.3s ease;
        }

        .booking-card:hover {
            transform: translateY(-5px);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        .booking-header h3 {
            margin: 0;
            color: #2c3e50;
        }

        .status {
            padding: 0.3rem 0.7rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status.pending {
            background-color: #f39c12;
            color: white;
        }

        .status.confirmed {
            background-color: #27ae60;
            color: white;
        }

        .status.cancelled {
            background-color: #e74c3c;
            color: white;
        }

        .booking-details {
            margin-bottom: 1rem;
        }

        .booking-details p {
            margin: 0.5rem 0;
            color: #555;
        }

        .booking-actions {
            text-align: center;
            margin-top: 1rem;
        }

        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .btn-cancel {
            background-color: #e74c3c;
            color: white;
        }

        .btn-cancel:hover {
            background-color: #c0392b;
        }

        .no-bookings {
            text-align: center;
            color: #7f8c8d;
            font-style: italic;
        }

        /* Previous CSS remains the same, adding new alert styles */
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="container my-bookings">
        <!-- Message Display Section -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php
                echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php
                echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>
        <h1>My Bookings</h1>

        <?php if ($result->num_rows > 0): ?>
            <div class="bookings-grid">
                <?php while ($booking = $result->fetch_assoc()): ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <h3><?php echo htmlspecialchars($booking['HostelName']); ?></h3>
                            <span class="status <?php echo strtolower($booking['BookingStatus']); ?>">
                                <?php echo htmlspecialchars($booking['BookingStatus']); ?>
                            </span>
                        </div>
                        <div class="booking-details">
                            <p><strong>Room:</strong> <?php echo htmlspecialchars($booking['RoomNumber']); ?> (<?php echo htmlspecialchars($booking['RoomType']); ?>)</p>
                            <p><strong>Check-in:</strong> <?php echo date('F j, Y', strtotime($booking['CheckInDate'])); ?></p>
                            <p><strong>Check-out:</strong> <?php echo date('F j, Y', strtotime($booking['CheckOutDate'])); ?></p>
                            <p><strong>Total Price:</strong> $<?php echo number_format($booking['TotalPrice'], 2); ?></p>
                        </div>
                        <div class="booking-actions">
                            <?php if ($booking['BookingStatus'] == 'Pending'): ?>
                                <a href="cancel_booking.php?id=<?php echo $booking['BookingID']; ?>" class="btn btn-cancel">Cancel Booking</a>
                            <?php endif; ?>
                            <?php if ($booking['BookingStatus'] == 'Cancelled'): ?>
                                <a href="delete_booking.php?id=<?php echo $booking['BookingID']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this booking? This action cannot be undone.');">
                                    <i class="fas fa-trash-alt"></i> Delete Booking
                                </a>
                            <?php endif; ?>

                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="no-bookings">You have no bookings yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>

