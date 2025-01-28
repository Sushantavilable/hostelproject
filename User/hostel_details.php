<?php

// Include database connection
require_once('../includes/db_connection.php');

if (isset($_SESSION['UserID'])) {
    $userQuery = "SELECT * FROM users WHERE UserID = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param("i", $_SESSION['UserID']);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userData = $userResult->fetch_assoc();
}

$HostelID = intval($_GET['id']);

// Fetch the specific hostel's details from the database
$query = "SELECT * FROM hostels WHERE HostelID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $HostelID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Error: Hostel not found.";
    exit;
}

// Fetch hostel data
$hostel = $result->fetch_assoc();

// Fetch rooms for this hostel
$roomQuery = "SELECT * FROM rooms WHERE HostelID = ?";
$roomStmt = $conn->prepare($roomQuery);
$roomStmt->bind_param("i", $HostelID);
$roomStmt->execute();
$roomResult = $roomStmt->get_result();

// Fetch images for the hostel
$imageQuery = "SELECT * FROM hostel_images WHERE HostelID = ?";
$imageStmt = $conn->prepare($imageQuery);
$imageStmt->bind_param("i", $HostelID);
$imageStmt->execute();
$imageResult = $imageStmt->get_result();

// Fetch total and available rooms
$totalRoomsQuery = "SELECT 
    COUNT(*) as TotalRooms, 
    SUM(CASE WHEN AvailabilityStatus = 'Available' THEN 1 ELSE 0 END) as AvailableRooms 
    FROM rooms 
    WHERE HostelID = ?";
$totalRoomsStmt = $conn->prepare($totalRoomsQuery);
$totalRoomsStmt->bind_param("i", $HostelID);
$totalRoomsStmt->execute();
$roomCountResult = $totalRoomsStmt->get_result()->fetch_assoc();

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Finders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .manage-hostel-content {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: 20px;
        }

        .hostel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .hostel-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .images-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .image-card {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .image-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .image-card:hover {
            transform: scale(1.05);
        }

        .rooms-table {
            width: 100%;
            border-collapse: collapse;
        }

        .rooms-table th,
        .rooms-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .rooms-table thead {
            background-color: #f2f2f2;
            font-weight: bold;
        }
    </style>
    <!-- Add this script to show login message -->
    <script>
        function showLoginMessage() {
            alert('Please log in to book a room.');
        }

        function showBookedMessage() {
            alert('This room is currently booked. Please check other available rooms.');
        }
    </script>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main">
            <div class="manage-hostel-content">
                <div>
                    <a href="javascript:history.back()" class="btn btn-secondary" style="margin-bottom: 20px; padding: 8px 16px; background-color: #f2f2f2; border: none; border-radius: 4px; color: #333; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease;">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                <div class="hostel-header">
                    <h1 style="font-size: xx-large; font-weight:bold;"><?php echo htmlspecialchars($hostel['Name']); ?> </h1>

                </div>

                <div class="hostel-details-grid">
                    <div class="detail-card">
                        <h3>Basic Information</h3>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($hostel['Address']); ?></p>
                        <p><strong>City:</strong> <?php echo htmlspecialchars($hostel['City']); ?></p>
                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($hostel['ContactNumber']); ?></p>
                    </div>
                    <div class="detail-card">
                        <h3>Room Statistics</h3>
                        <p><strong>Total Rooms:</strong> <?php echo $roomCountResult['TotalRooms']; ?></p>
                        <p><strong>Available Rooms:</strong> <?php echo $roomCountResult['AvailableRooms']; ?></p>
                        <p><strong>Occupancy Rate:</strong>
                            <?php
                            $occupancyRate = ($roomCountResult['TotalRooms'] > 0)
                                ? round((1 - $roomCountResult['AvailableRooms'] / $roomCountResult['TotalRooms']) * 100, 2)
                                : 0;
                            echo $occupancyRate . '%';
                            ?>
                        </p>
                    </div>
                </div>

                <div class="images-container">
                    <?php if ($imageResult->num_rows > 0): ?>
                        <?php while ($image = $imageResult->fetch_assoc()): ?>
                            <div class="image-card">
                                <img src="../<?php echo htmlspecialchars($image['ImagePath']); ?>" alt="Hostel Image">
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No images uploaded for this hostel.</p>
                    <?php endif; ?>
                </div>

                <div class="rooms-section">
                    <h3>Rooms in this Hostel</h3>
                    <?php if ($roomResult->num_rows > 0): ?>
                        <table class="rooms-table">
                            <thead>
                                <tr>
                                    <th>Room Number</th>
                                    <th>Type</th>
                                    <th>Capacity</th>
                                    <th>Price/Month</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Start the session at the top of the PHP file
                                // session_start();

                                while ($room = $roomResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($room['RoomNumber']); ?></td>
                                        <td><?php echo htmlspecialchars($room['RoomType']); ?></td>
                                        <td><?php echo htmlspecialchars($room['MaxOccupancy']); ?></td>
                                        <td>Rs. <?php echo htmlspecialchars($room['PricePerMonth']); ?></td>
                                        <td><?php echo htmlspecialchars($room['AvailabilityStatus']); ?></td>
                                        <td>
                                            <a href="view_room_details.php?id=<?php echo $room['RoomID']; ?>" class="btn" style="background-color: #4CAF50; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; margin-right: 5px; display: inline-block;">View</a>

                                            <?php if ($room['AvailabilityStatus'] == 'Available'): ?>
                                                <?php if (isset($_SESSION['username'])): ?>
                                                    <a href="book_room.php?id=<?php echo $room['RoomID']; ?>" class="btn" style="background-color: #2196F3; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; display: inline-block;">Book</a>
                                                <?php else: ?>
                                                    <a href="#" class="btn" onclick="showLoginMessage();" style="background-color: gray; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; display: inline-block; cursor: not-allowed;">Book</a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <a href="#" class="btn" onclick="showBookedMessage();" style="background-color: gray; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; display: inline-block; cursor: not-allowed;">Book</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No rooms found for this hostel.</p>
                    <?php endif; ?>
                </div>


            </div>
        </div>
    </div>


    <?php include 'footer.php'; ?>
</body>

</html>