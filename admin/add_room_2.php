<!-- Step 2: add_room_2.php -->
<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit;
}

// Check if room quantities are set
if (!isset($_SESSION['room_quantities']) || !isset($_SESSION['new_hostel_id'])) {
    $_SESSION['error'] = "Please start the room addition process from the beginning.";
    header("Location: add_rooms_step1.php");
    exit;
}

// Include database connection
require_once('../includes/db_connection.php');

// Get room quantities from session
$roomQuantities = $_SESSION['room_quantities'];

// Track current room being added
$currentRoomType = null;

// Determine current room to add
foreach ($roomQuantities as $type => $quantity) {
    if ($quantity > 0) {
        $currentRoomType = $type;
        break;
    }
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $roomNumber = trim($_POST['room_number']);
    $floor = max(1, (int)$_POST['floor']);
    $pricePerMonth = max(0, (float)$_POST['price_per_month']);
    $maxOccupancy = max(1, (int)$_POST['max_occupancy']);
    $hasPrivateBathroom = isset($_POST['has_private_bathroom']) ? 1 : 0;
    $hasAirConditioning = isset($_POST['has_air_conditioning']) ? 1 : 0;
    $windowView = $_POST['window_view'] ?? null;
    $squareFootage = max(0, (float)$_POST['square_footage']);
    $furnishingStatus = $_POST['furnishing_status'] ?? null;
    $additionalAmenities = trim($_POST['additional_amenities']);

    // Validate required fields
    $errors = [];
    if (empty($roomNumber)) $errors[] = "Room number is required.";
    if (empty($pricePerMonth)) $errors[] = "Price per month is required.";

    if (empty($errors)) {
        try {
            // Prepare room insertion query
            $query = "INSERT INTO rooms (RoomNumber, Floor, HostelID, RoomType, PricePerMonth, 
                      MaxOccupancy, HasPrivateBathroom, HasAirConditioning, WindowView, 
                      SquareFootage, FurnishingStatus, AdditionalAmenities) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "siisdiiissss",
                $roomNumber,
                $floor,
                $_SESSION['new_hostel_id'],
                $currentRoomType,
                $pricePerMonth,
                $maxOccupancy,
                $hasPrivateBathroom,
                $hasAirConditioning,
                $windowView,
                $squareFootage,
                $furnishingStatus,
                $additionalAmenities
            );

            if ($stmt->execute()) {
                // Decrement room quantity for current type
                $roomQuantities[$currentRoomType]--;

                // Update session with remaining room quantities
                $_SESSION['room_quantities'] = $roomQuantities;

                // Determine next steps
                $nextRoomType = null;
                foreach ($roomQuantities as $type => $quantity) {
                    if ($quantity > 0) {
                        $nextRoomType = $type;
                        break;
                    }
                }

                if ($nextRoomType) {
                    $_SESSION['message'] = "Room added successfully. Add next {$nextRoomType} room.";
                    header("Location: add_room_2.php");
                    exit;
                } else {
                    unset($_SESSION['room_quantities']);
                    unset($_SESSION['new_hostel_id']);
                    $_SESSION['message'] = "All rooms added successfully!";
                    header("Location: view_hostels.php");
                    exit;
                }
            } else {
                throw new Exception("Failed to insert room: " . $stmt->error);
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
    } else {
        // Store errors in session
        $_SESSION['error'] = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Rooms - Step 2</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <div class="main">
            <?php include 'navbar.php'; ?>

            <h2>Add Rooms - Step 2 (<?php echo $currentRoomType; ?> Room)</h2>

            <?php
            // Display success or error messages
            if (isset($_SESSION['message'])) {
                echo "<div class='alert alert-success'>" . htmlspecialchars($_SESSION['message']) . "</div>";
                unset($_SESSION['message']);
            }
            if (isset($_SESSION['error'])) {
                echo "<div class='alert alert-danger'>" . htmlspecialchars($_SESSION['error']) . "</div>";
                unset($_SESSION['error']);
            }
            ?>

            <form method="POST" action="" class="horizontal-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="room_number">Room Number</label>
                        <input type="text" class="form-control" id="room_number" name="room_number" 
                            value="<?php echo isset($_POST['room_number']) ? htmlspecialchars($_POST['room_number']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="floor">Floor</label>
                        <input type="number" class="form-control" id="floor" name="floor" 
                            value="<?php echo isset($_POST['floor']) ? htmlspecialchars($_POST['floor']) : '1'; ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="room_type">Room Type</label>
                        <input type="text" class="form-control" id="room_type" name="room_type" 
                            value="<?php echo htmlspecialchars($currentRoomType); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="price_per_month">Price Per Month</label>
                        <input type="number" class="form-control" id="price_per_month" name="price_per_month" 
                            value="<?php echo isset($_POST['price_per_month']) ? htmlspecialchars($_POST['price_per_month']) : ''; ?>" required step="0.01">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="availability_status">Availability Status</label>
                        <select class="form-control" id="availability_status" name="availability_status" required>
                            <option value="Available" selected>Available</option>
                            <option value="Booked">Booked</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="max_occupancy">Maximum Occupancy</label>
                        <input type="number" class="form-control" id="max_occupancy" name="max_occupancy" 
                            value="<?php echo isset($_POST['max_occupancy']) ? htmlspecialchars($_POST['max_occupancy']) : ($currentRoomType == 'Single' ? '1' : ($currentRoomType == 'Double' ? '2' : '4')); ?>" 
                            required min="1">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="window_view">Window View</label>
                        <select class="form-control" id="window_view" name="window_view">
                            <option value="Street" <?php echo (isset($_POST['window_view']) && $_POST['window_view'] == 'Street') ? 'selected' : ''; ?>>Street</option>
                            <option value="Garden" <?php echo (isset($_POST['window_view']) && $_POST['window_view'] == 'Garden') ? 'selected' : ''; ?>>Garden</option>
                            <option value="Courtyard" <?php echo (isset($_POST['window_view']) && $_POST['window_view'] == 'Courtyard') ? 'selected' : ''; ?>>Courtyard</option>
                            <option value="No View" <?php echo (isset($_POST['window_view']) && $_POST['window_view'] == 'No View') ? 'selected' : ''; ?>>No View</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="furnishing_status">Furnishing Status</label>
                        <select class="form-control" id="furnishing_status" name="furnishing_status">
                            <option value="Fully Furnished" <?php echo (isset($_POST['furnishing_status']) && $_POST['furnishing_status'] == 'Fully Furnished') ? 'selected' : ''; ?>>Fully Furnished</option>
                            <option value="Partially Furnished" <?php echo (isset($_POST['furnishing_status']) && $_POST['furnishing_status'] == 'Partially Furnished') ? 'selected' : ''; ?>>Partially Furnished</option>
                            <option value="Unfurnished" <?php echo (isset($_POST['furnishing_status']) && $_POST['furnishing_status'] == 'Unfurnished') ? 'selected' : ''; ?>>Unfurnished</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Amenities</label>
                        <div style="margin-top: 10px;">
                            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                <input type="checkbox" name="has_private_bathroom" style="width: auto; margin-right: 8px;"
                                    <?php echo isset($_POST['has_private_bathroom']) ? 'checked' : ''; ?>>
                                <span>Private Bathroom</span>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <input type="checkbox" name="has_air_conditioning" style="width: auto; margin-right: 8px;"
                                    <?php echo isset($_POST['has_air_conditioning']) ? 'checked' : ''; ?>>
                                <span>Air Conditioning</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="square_footage">Square Footage</label>
                        <input type="number" class="form-control" id="square_footage" name="square_footage" 
                            value="<?php echo isset($_POST['square_footage']) ? htmlspecialchars($_POST['square_footage']) : ''; ?>" step="0.01">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="additional_amenities">Additional Amenities</label>
                        <textarea class="form-control" id="additional_amenities" name="additional_amenities" 
                                rows="3"><?php echo isset($_POST['additional_amenities']) ? htmlspecialchars($_POST['additional_amenities']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php
                        $remainingRooms = array_sum($roomQuantities);
                        echo ($remainingRooms > 1) ? "Save and Add Next Room" : "Finish Room Addition";
                        ?>
                    </button>
                    <a href="view_hostels.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
<?php
// Close the database connection
$conn->close();
?>