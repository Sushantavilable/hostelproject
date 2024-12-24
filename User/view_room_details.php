<!-- view_room_details.php -->
<?php
require_once('../includes/db_connection.php');

$RoomID = intval($_GET['id']);

// Fetch room details
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
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Details - <?php echo htmlspecialchars($room['RoomNumber']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .room-details-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: 20px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 28px;
        }

        .detail-section {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
        }

        .amenity-tag {
            display: inline-block;
            background-color: #e9ecef;
            padding: 5px 10px;
            border-radius: 15px;
            margin: 5px;
            font-size: 0.9em;
        }

        .status-available {
            color: green;
            font-weight: bold;
        }

        .status-booked {
            color: red;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="room-details-container">
            <div>
                <a href="javascript:history.back()" class="btn btn-secondary" style="margin-bottom: 20px; padding: 8px 16px; background-color: #f2f2f2; border: none; border-radius: 4px; color: #333; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <h1>Room <?php echo htmlspecialchars($room['RoomNumber']); ?></h1>
            <p>In <?php echo htmlspecialchars($room['HostelName']); ?></p>

            <div class="detail-grid">
                <div class="detail-section">
                    <h3>Basic Information</h3>
                    <p><strong>Room Type:</strong> <?php echo htmlspecialchars($room['RoomType']); ?></p>
                    <p><strong>Floor:</strong> <?php echo htmlspecialchars($room['Floor']); ?></p>
                    <p><strong>Price per Month:</strong> Rs. <?php echo htmlspecialchars($room['PricePerMonth']); ?></p>
                    <p><strong>Status:</strong>
                        <span class="status-<?php echo strtolower($room['AvailabilityStatus']); ?>">
                            <?php echo htmlspecialchars($room['AvailabilityStatus']); ?>
                        </span>
                    </p>
                    <p><strong>Maximum Occupancy:</strong> <?php echo htmlspecialchars($room['MaxOccupancy']); ?> persons</p>
                </div>

                <div class="detail-section">
                    <h3>Room Features</h3>
                    <p><strong>Square Footage:</strong> <?php echo htmlspecialchars($room['SquareFootage']); ?> sq ft</p>
                    <p><strong>Window View:</strong> <?php echo htmlspecialchars($room['WindowView']); ?></p>
                    <p><strong>Furnishing Status:</strong> <?php echo htmlspecialchars($room['FurnishingStatus']); ?></p>
                </div>

                <div class="detail-section">
                    <h3>Amenities</h3>
                    <div>
                        <?php if ($room['HasPrivateBathroom']): ?>
                            <span class="amenity-tag"><i class="fas fa-bath"></i> Private Bathroom</span>
                        <?php endif; ?>

                        <?php if ($room['HasAirConditioning']): ?>
                            <span class="amenity-tag"><i class="fas fa-wind"></i> Air Conditioning</span>
                        <?php endif; ?>

                        <?php if ($room['AdditionalAmenities']): ?>
                            <?php
                            $amenities = explode(',', $room['AdditionalAmenities']);
                            foreach ($amenities as $amenity): ?>
                                <span class="amenity-tag"><?php echo htmlspecialchars(trim($amenity)); ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($room['AvailabilityStatus'] == 'Available'): ?>
                <div style="margin-top: 20px; text-align: center;">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="book_room.php?id=<?php echo $room['RoomID']; ?>" class="btn">Book This Room</a>
                    <?php else: ?>
                        <a href="#" class="btn" onclick="showLoginMessage();">Book This Room</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <script>
                function showLoginMessage() {
                    alert('Please log in to book a room.');
                }
            </script>

        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>

</html>