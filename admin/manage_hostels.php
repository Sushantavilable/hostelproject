<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit;
}

// Include database connection
require_once('../includes/db_connection.php');

// Get the HostelID from the URL
if (!isset($_GET['id'])) {
    echo "Error: Hostel ID is missing.";
    exit;
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

// Handle Delete Operation
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $roomId = intval($_GET['roomId']);

    // First check if the room is booked
    $checkBookingQuery = "SELECT r.AvailabilityStatus, 
                                COUNT(b.BookingID) as active_bookings 
                         FROM rooms r 
                         LEFT JOIN bookings b ON r.RoomID = b.RoomID 
                         WHERE r.RoomID = ? AND r.HostelID = ? 
                         AND (b.BookingStatus = 'Pending' OR b.BookingStatus = 'Confirmed')
                         GROUP BY r.RoomID, r.AvailabilityStatus";
    
    $checkStmt = $conn->prepare($checkBookingQuery);
    $checkStmt->bind_param("ii", $roomId, $HostelID);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $roomStatus = $result->fetch_assoc();

    if ($roomStatus && ($roomStatus['AvailabilityStatus'] == 'Booked' || $roomStatus['active_bookings'] > 0)) {
        $_SESSION['error'] = "Cannot delete room as it is currently booked or has pending bookings.";
    } else {
        // Delete the room only if it's not booked
        $deleteQuery = "DELETE FROM rooms WHERE RoomID = ? AND HostelID = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("ii", $roomId, $HostelID);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Room deleted successfully";
        } else {
            $_SESSION['error'] = "Failed to delete room";
        }
        $stmt->close();
    }
    $checkStmt->close();

    // Redirect back to the same page
    header("Location: manage_hostels.php?id=" . $HostelID);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hostel - <?php echo htmlspecialchars($hostel['Name']); ?></title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
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
</head>

<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <div class="main">
            <?php include 'navbar.php'; ?>
            <h2>Manage Hostel</h2>
            <div class="header-actions">
                <a href="view_hostels.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            <div class="manage-hostel-content">
                <?php
                    // Display success message if set
                    if (isset($_SESSION['success'])) {
                        echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
                        unset($_SESSION['success']);
                    }
                    
                    // Display error message if set
                    if (isset($_SESSION['error'])) {
                        echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
                        unset($_SESSION['error']); 
                    }
                    ?>
                <div class="hostel-header">
                    <h1><?php echo htmlspecialchars($hostel['Name']); ?> </h1>
                    <div class="action-buttons">
                        <a href="add_room.php?hostelId=<?php echo $HostelID; ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Room
                        </a>
                        <a href="edit_hostel.php?id=<?php echo $HostelID; ?>" class="btn btn-secondary">
                            <i class="fas fa-edit"></i> Edit Hostel
                        </a>
                    </div>
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
                                <?php while ($room = $roomResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($room['RoomNumber']); ?></td>
                                        <td><?php echo htmlspecialchars($room['RoomType']); ?></td>
                                        <td><?php echo htmlspecialchars($room['MaxOccupancy']); ?></td>
                                        <td>Rs. <?php echo htmlspecialchars($room['PricePerMonth']); ?></td>
                                        <td><?php echo htmlspecialchars($room['AvailabilityStatus']); ?></td>
                                        <td>
                                            <a href="edit_room.php?id=<?php echo $room['RoomID']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                            <a href="manage_hostels.php?action=delete&roomId=<?php echo $room['RoomID']; ?>&id=<?php echo $HostelID; ?>"
                                                class="btn btn-secondary btn-sm"
                                                style="background-color: #dc3545;"
                                                onclick="return confirm('Are you sure you want to delete this room?');">Delete</a>
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

    <?php
    // Close prepared statements and database connection
    $stmt->close();
    $roomStmt->close();
    $imageStmt->close();
    $totalRoomsStmt->close();
    $conn->close();
    ?>
</body>

</html>