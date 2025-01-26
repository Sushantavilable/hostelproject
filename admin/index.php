<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit;
}

// Include database connection
require_once('../includes/db_connection.php');

// Query to get total bookings
$bookings_query = "SELECT COUNT(*) as total_bookings FROM bookings";
$bookings_result = $conn->query($bookings_query);
$total_bookings = $bookings_result->fetch_assoc()['total_bookings'];

// Query to get total hostels
$hostels_query = "SELECT COUNT(*) as total_hostels FROM hostels";
$hostels_result = $conn->query($hostels_query);
$total_hostels = $hostels_result->fetch_assoc()['total_hostels'];

// Query to get total users
$users_query = "SELECT COUNT(*) as total_users FROM users";
$users_result = $conn->query($users_query);
$total_users = $users_result->fetch_assoc()['total_users'];

// Query to get pending bookings
$pending_bookings_query = "SELECT COUNT(*) as pending_bookings FROM bookings WHERE BookingStatus = 'Pending'";
$pending_bookings_result = $conn->query($pending_bookings_query);
$pending_bookings = $pending_bookings_result->fetch_assoc()['pending_bookings'];

// Query to get available rooms
$available_rooms_query = "SELECT COUNT(*) as available_rooms FROM rooms WHERE AvailabilityStatus = 'Available'";
$available_rooms_result = $conn->query($available_rooms_query);
$available_rooms = $available_rooms_result->fetch_assoc()['available_rooms'];

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Booking Dashboard</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <div class="main">
            <?php include 'navbar.php'; ?>

            <div class="content">
                <h2>Hostel Management Dashboard</h2>
                <div class="dashboard-card">
                    <div class="card">
                        <a href="users.php" style="color: black; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='black'">
                            <h6 class="title">Total Registered Users</h6>
                            <h6 class="amount"><?php echo $total_users; ?></h6>
                        </a>
                    </div>
                    <div class="card">
                        <a href="view_hostels.php" style="color: black; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='black'">
                            <h6 class="title">Total Added Hostels</h6>
                            <h6 class="amount"><?php echo $total_hostels; ?></h6>
                        </a>
                    </div>
                    <div class="card">
                        <a href="view_bookings.php" style="color: black; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='black'">
                            <h6 class="title">Total Bookings</h6>
                            <h6 class="amount"><?php echo $total_bookings; ?></h6>
                        </a>
                    </div>
                    <div class="card">
                        <h6 class="title">Available Rooms</h6>
                        <h6 class="amount"><?php echo $available_rooms; ?></h6>

                    </div>
                    <div class="card">
                        <h6 class="title">Pending Approvals</h6>
                        <h6 class="amount"><?php echo $pending_bookings; ?></h6>

                    </div>

                </div>
            </div>
        </div>
    </div>
</body>

</html>