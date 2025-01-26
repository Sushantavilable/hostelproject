<!-- Step 1: add_room.php -->
<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit;
}

// Check if hostel ID is provided from the manage hostel page
if (!isset($_GET['hostelId'])) {
    $_SESSION['error'] = "Please select a specific hostel to add rooms.";
    header("Location: view_hostels.php");
    exit;
}

// Store the hostel ID in the session
$_SESSION['new_hostel_id'] = intval($_GET['hostelId']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate room quantities
    $singleRooms = max(0, (int)$_POST['single_rooms']);
    $doubleRooms = max(0, (int)$_POST['double_rooms']);
    $dormRooms = max(0, (int)$_POST['dorm_rooms']);

    // Ensure at least one room type is selected
    if ($singleRooms + $doubleRooms + $dormRooms == 0) {
        $_SESSION['error'] = "Please select at least one room type.";
    } else {
        // Store room quantities in session
        $_SESSION['room_quantities'] = [
            'Single' => $singleRooms,
            'Double' => $doubleRooms,
            'Dorm' => $dormRooms
        ];

        // Redirect to step 2
        header("Location: add_room_2.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Rooms - Step 1</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <div class="main">
            <?php include 'navbar.php'; ?>

            <h2>Add Rooms - Step 1</h2>

            <?php
            // Display error messages
            if (isset($_SESSION['error'])) {
                echo "<div class='alert alert-danger'>" . htmlspecialchars($_SESSION['error']) . "</div>";
                unset($_SESSION['error']);
            }
            ?>

            <form action="" method="POST" class="horizontal-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="single_rooms">Number of Single Rooms</label>
                        <input type="number" id="single_rooms" name="single_rooms" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="double_rooms">Number of Double Rooms</label>
                        <input type="number" id="double_rooms" name="double_rooms" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="dorm_rooms">Number of Dorm Rooms</label>
                        <input type="number" id="dorm_rooms" name="dorm_rooms" min="0" value="0">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Next: Room Details
                    </button>
                    <a href="manage_hostels.php?id=<?php echo intval($_GET['hostelId']); ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>