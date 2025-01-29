<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit;
}

// Database connection
$conn = mysqli_connect("localhost", "root", "", "hostelwebsite");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get room ID from URL
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$room_id) {
    $_SESSION['error'] = "Invalid room ID.";
    header("Location: view_hostels.php");
    exit;
}

// Initialize variables
$room_number = "";
$floor = "";
$hostel_id = "";
$room_type = "";
$price_per_month = "";
$availability_status = "";
$max_occupancy = "";
$has_private_bathroom = 0;
$has_air_conditioning = 0;
$window_view = "";
$square_footage = "";
$furnishing_status = "";
$additional_amenities = "";

// Fetch room details
if ($room_id > 0) {
    $sql = "SELECT * FROM rooms WHERE RoomID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $room_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $room_number = $row['RoomNumber'];
        $floor = $row['Floor'];
        $hostel_id = $row['HostelID'];
        $room_type = $row['RoomType'];
        $price_per_month = $row['PricePerMonth'];
        $availability_status = $row['AvailabilityStatus'];
        $max_occupancy = $row['MaxOccupancy'];
        $has_private_bathroom = $row['HasPrivateBathroom'];
        $has_air_conditioning = $row['HasAirConditioning'];
        $window_view = $row['WindowView'];
        $square_footage = $row['SquareFootage'];
        $furnishing_status = $row['FurnishingStatus'];
        $additional_amenities = $row['AdditionalAmenities'];
    } else {
        $_SESSION['error'] = "Room not found!";
        header("Location: view_hostels.php");
        exit;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_number = trim($_POST['room_number']);
    $floor = intval($_POST['floor']);
    $room_type = trim($_POST['room_type']);
    $price_per_month = floatval($_POST['price_per_month']);
    $availability_status = trim($_POST['availability_status']);
    $max_occupancy = intval($_POST['max_occupancy']);
    $has_private_bathroom = isset($_POST['has_private_bathroom']) ? 1 : 0;
    $has_air_conditioning = isset($_POST['has_air_conditioning']) ? 1 : 0;
    $window_view = trim($_POST['window_view']);
    $square_footage = floatval($_POST['square_footage']);
    $furnishing_status = trim($_POST['furnishing_status']);
    $additional_amenities = trim($_POST['additional_amenities']);
    
    // Validation
    $errors = array();
    
    if (empty($room_number)) {
        $errors[] = "Room number is required";
    }
    if ($price_per_month <= 0) {
        $errors[] = "Price must be greater than 0";
    }
    if ($max_occupancy <= 0) {
        $errors[] = "Maximum occupancy must be greater than 0";
    }
    
    // If no errors, update the room
    if (empty($errors)) {
        $sql = "UPDATE rooms SET 
                RoomNumber=?, Floor=?, RoomType=?, 
                PricePerMonth=?, AvailabilityStatus=?, MaxOccupancy=?,
                HasPrivateBathroom=?, HasAirConditioning=?, WindowView=?,
                SquareFootage=?, FurnishingStatus=?, AdditionalAmenities=?
                WHERE RoomID=?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sisdsiiiisdsi", 
            $room_number, $floor, $room_type,
            $price_per_month, $availability_status, $max_occupancy,
            $has_private_bathroom, $has_air_conditioning, $window_view,
            $square_footage, $furnishing_status, $additional_amenities,
            $room_id
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Room updated successfully!";
            header("Location: manage_hostels.php?id=" . $hostel_id);
            exit;
        } else {
            $_SESSION['error'] = "Error updating room: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <div class="main">
            <?php include 'navbar.php'; ?>

            <div class="content-header">
                <h2>Edit Room</h2>
            </div>

            <?php
            if (isset($_SESSION['error'])) {
                echo "<div class='alert alert-danger'>" . htmlspecialchars($_SESSION['error']) . "</div>";
                unset($_SESSION['error']);
            }
            if (isset($_SESSION['success'])) {
                echo "<div class='alert alert-success'>" . htmlspecialchars($_SESSION['success']) . "</div>";
                unset($_SESSION['success']);
            }
            ?>

            <form method="POST" action="" class="horizontal-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="room_number">Room Number</label>
                        <input type="text" class="form-control" id="room_number" name="room_number" 
                               value="<?php echo htmlspecialchars($room_number); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="floor">Floor</label>
                        <input type="number" class="form-control" id="floor" name="floor" 
                               value="<?php echo htmlspecialchars($floor); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="room_type">Room Type</label>
                        <select class="form-control" id="room_type" name="room_type" required>
                            <option value="Single" <?php echo $room_type === 'Single' ? 'selected' : ''; ?>>Single</option>
                            <option value="Double" <?php echo $room_type === 'Double' ? 'selected' : ''; ?>>Double</option>
                            <option value="Dorm" <?php echo $room_type === 'Dorm' ? 'selected' : ''; ?>>Dorm</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="price_per_month">Price Per Month</label>
                        <input type="number" class="form-control" id="price_per_month" name="price_per_month" 
                               value="<?php echo htmlspecialchars($price_per_month); ?>" required step="0.01">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="availability_status">Availability Status</label>
                        <select class="form-control" id="availability_status" name="availability_status" required>
                            <option value="Available" <?php echo $availability_status === 'Available' ? 'selected' : ''; ?>>Available</option>
                            <option value="Booked" <?php echo $availability_status === 'Booked' ? 'selected' : ''; ?>>Booked</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="max_occupancy">Maximum Occupancy</label>
                        <input type="number" class="form-control" id="max_occupancy" name="max_occupancy" 
                               value="<?php echo htmlspecialchars($max_occupancy); ?>" required min="1">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="window_view">Window View</label>
                        <select class="form-control" id="window_view" name="window_view">
                            <option value="Street" <?php echo $window_view === 'Street' ? 'selected' : ''; ?>>Street</option>
                            <option value="Garden" <?php echo $window_view === 'Garden' ? 'selected' : ''; ?>>Garden</option>
                            <option value="Courtyard" <?php echo $window_view === 'Courtyard' ? 'selected' : ''; ?>>Courtyard</option>
                            <option value="No View" <?php echo $window_view === 'No View' ? 'selected' : ''; ?>>No View</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="furnishing_status">Furnishing Status</label>
                        <select class="form-control" id="furnishing_status" name="furnishing_status">
                            <option value="Fully Furnished" <?php echo $furnishing_status === 'Fully Furnished' ? 'selected' : ''; ?>>Fully Furnished</option>
                            <option value="Partially Furnished" <?php echo $furnishing_status === 'Partially Furnished' ? 'selected' : ''; ?>>Partially Furnished</option>
                            <option value="Unfurnished" <?php echo $furnishing_status === 'Unfurnished' ? 'selected' : ''; ?>>Unfurnished</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Amenities</label>
                        <div  style="margin-top: 10px;">
                            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                <input type="checkbox" name="has_private_bathroom" style="width: auto; margin-right: 8px;"
                                    <?php echo $has_private_bathroom ? 'checked' : ''; ?>>
                                <span>Private Bathroom</span>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <input type="checkbox" name="has_air_conditioning" style="width: auto; margin-right: 8px;"
                                    <?php echo $has_air_conditioning ? 'checked' : ''; ?>>
                                <span>Air Conditioning</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="square_footage">Square Footage</label>
                        <input type="number" class="form-control" id="square_footage" name="square_footage" 
                            value="<?php echo htmlspecialchars($square_footage); ?>" step="0.01">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="additional_amenities">Additional Amenities</label>
                        <textarea class="form-control" id="additional_amenities" name="additional_amenities" 
                                  rows="3"><?php echo htmlspecialchars($additional_amenities); ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Room
                    </button>
                    <a href="manage_hostels.php?id=<?php echo $hostel_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>