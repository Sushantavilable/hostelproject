<!-- book_room.php -->
<?php
include 'header.php';

require_once('../includes/db_connection.php');


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
}

// Get room details
$RoomID = intval($_GET['id']);

// Fetch room and hostel details
$query = "SELECT r.*, h.Name as HostelName 
          FROM rooms r 
          JOIN hostels h ON r.HostelID = h.HostelID 
          WHERE r.RoomID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $RoomID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Error: Room not found.";
    exit;
}

$room = $result->fetch_assoc();

// Check if room is available
if ($room['AvailabilityStatus'] != 'Available') {
    echo "Error: This room is not currently available for booking.";
    exit;
}

// Process booking submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate form inputs
    $checkInDate = $_POST['check_in_date'];
    $checkOutDate = $_POST['check_out_date'];
    $numberOfOccupants = intval($_POST['number_of_occupants']);

    // Validate occupancy
    if ($numberOfOccupants > $room['MaxOccupancy']) {
        $error = "Number of occupants exceeds room's maximum capacity of {$room['MaxOccupancy']}.";
    } else {
        // Calculate total price
        $checkInDateTime = new DateTime($checkInDate);
        $checkOutDateTime = new DateTime($checkOutDate);
        $interval = $checkInDateTime->diff($checkOutDateTime);

        // Calculate months (round up to nearest month)
        $months = $interval->y * 12 + $interval->m;
        if ($interval->d > 0) {
            $months++;
        }

        $totalPrice = $room['PricePerMonth'] * $months;

        // Prepare booking insertion
        $insertQuery = "INSERT INTO bookings 
                        (UserID, RoomID, HostelID, CheckInDate, CheckOutDate, 
                        TotalPrice, BookingStatus, NumberOfOccupants) 
                        VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param(
            "iiissdi",
            $_SESSION['user_id'],
            $RoomID,
            $room['HostelID'],
            $checkInDate,
            $checkOutDate,
            $totalPrice,
            $numberOfOccupants
        );

        if ($insertStmt->execute()) {
            // Update room availability
            $updateRoomQuery = "UPDATE rooms SET AvailabilityStatus = 'Booked' WHERE RoomID = ?";
            $updateRoomStmt = $conn->prepare($updateRoomQuery);
            $updateRoomStmt->bind_param("i", $RoomID);
            $updateRoomStmt->execute();

            // Redirect to confirmation page
            header("Location: booking_confirmation.php?booking_id=" . $conn->insert_id);
            exit();
        } else {
            $error = "Booking failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Room - <?php echo htmlspecialchars($room['RoomNumber']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .booking-container {
            max-width: 700px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .room-details {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .btn-book {
            width: 100%;
            padding: 12px;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-book:hover {
            background-color: #1976D2;
        }

        .error-message {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <!-- <?php include 'header.php'; ?> -->

    <div class="booking-container">
        <h1>Book Room <?php echo htmlspecialchars($room['RoomNumber']); ?></h1>

        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="room-details">
            <h2>Room Details</h2>
            <p><strong>Hostel:</strong> <?php echo htmlspecialchars($room['HostelName']); ?></p>
            <p><strong>Room Type:</strong> <?php echo htmlspecialchars($room['RoomType']); ?></p>
            <p><strong>Price per Month:</strong> Rs. <?php echo htmlspecialchars($room['PricePerMonth']); ?></p>
            <p><strong>Maximum Occupancy:</strong> <?php echo htmlspecialchars($room['MaxOccupancy']); ?> persons</p>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="check_in_date">Check-In Date</label>
                <input type="date" id="check_in_date" name="check_in_date"
                    min="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <label for="check_out_date">Check-Out Date</label>
                <input type="date" id="check_out_date" name="check_out_date" required>
            </div>

            <div class="form-group">
                <label for="number_of_occupants">Number of Occupants</label>
                <input type="number" id="number_of_occupants" name="number_of_occupants"
                    min="1" max="<?php echo $room['MaxOccupancy']; ?>"
                    value="1" required>
                <small>Maximum <?php echo $room['MaxOccupancy']; ?> occupants</small>
            </div>

            <button type="submit" class="btn-book">Confirm Booking</button>
        </form>
    </div>

    <script>
        // Date validation
        const checkInInput = document.getElementById('check_in_date');
        const checkOutInput = document.getElementById('check_out_date');
        const occupantsInput = document.getElementById('number_of_occupants');

        // Set min check-out date to check-in date
        checkInInput.addEventListener('change', function() {
            checkOutInput.min = this.value;

            // Reset check-out date if it's before check-in date
            if (new Date(checkOutInput.value) <= new Date(this.value)) {
                checkOutInput.value = '';
            }
        });

        // Limit occupants to max occupancy
        occupantsInput.addEventListener('change', function() {
            const maxOccupancy = <?php echo $room['MaxOccupancy']; ?>;
            if (this.value > maxOccupancy) {
                this.value = maxOccupancy;
            }
            if (this.value < 1) {
                this.value = 1;
            }
        });
    </script>

    <?php include 'footer.php'; ?>
</body>

</html>