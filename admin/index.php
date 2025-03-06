<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit;
}

// Include database connection
require_once('../includes/db_connection.php');

// Get the admin ID from session
$admin_id = $_SESSION['admin_id'];

// Get admin role and assigned hostel
$admin_query = "SELECT Role, AssignedHostelID FROM admins WHERE AdminID = ?";
$admin_stmt = $conn->prepare($admin_query);
$admin_stmt->bind_param("i", $admin_id);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$admin_info = $admin_result->fetch_assoc();

$is_super_admin = ($admin_info['Role'] === 'super_admin');
$assigned_hostel_id = $admin_info['AssignedHostelID'];

// Initialize stats variables
$total_bookings = 0;
$total_hostels = 0;
$total_users = 0;
$pending_bookings = 0;
$available_rooms = 0;

// Different queries based on admin role
if ($is_super_admin) {
    // Super admin sees stats for all hostels
    
    // Total bookings
    $bookings_query = "SELECT COUNT(*) as total_bookings FROM bookings";
    $bookings_result = $conn->query($bookings_query);
    $total_bookings = $bookings_result->fetch_assoc()['total_bookings'];
    
    // Total hostels
    $hostels_query = "SELECT COUNT(*) as total_hostels FROM hostels";
    $hostels_result = $conn->query($hostels_query);
    $total_hostels = $hostels_result->fetch_assoc()['total_hostels'];
    
    // Total users
    $users_query = "SELECT COUNT(*) as total_users FROM users";
    $users_result = $conn->query($users_query);
    $total_users = $users_result->fetch_assoc()['total_users'];
    
    // Pending bookings
    $pending_bookings_query = "SELECT COUNT(*) as pending_bookings FROM bookings WHERE BookingStatus = 'Pending'";
    $pending_bookings_result = $conn->query($pending_bookings_query);
    $pending_bookings = $pending_bookings_result->fetch_assoc()['pending_bookings'];
    
    // Available rooms
    $available_rooms_query = "SELECT COUNT(*) as available_rooms FROM rooms WHERE AvailabilityStatus = 'Available'";
    $available_rooms_result = $conn->query($available_rooms_query);
    $available_rooms = $available_rooms_result->fetch_assoc()['available_rooms'];
} else {
    // Hostel admin sees stats only for their assigned hostel
    if ($assigned_hostel_id) {
        // Total bookings for this hostel
        $bookings_query = "SELECT COUNT(*) as total_bookings FROM bookings WHERE HostelID = ?";
        $bookings_stmt = $conn->prepare($bookings_query);
        $bookings_stmt->bind_param("i", $assigned_hostel_id);
        $bookings_stmt->execute();
        $bookings_result = $bookings_stmt->get_result();
        $total_bookings = $bookings_result->fetch_assoc()['total_bookings'];
        
        // Get hostel name
        $hostel_query = "SELECT Name FROM hostels WHERE HostelID = ?";
        $hostel_stmt = $conn->prepare($hostel_query);
        $hostel_stmt->bind_param("i", $assigned_hostel_id);
        $hostel_stmt->execute();
        $hostel_result = $hostel_stmt->get_result();
        $hostel_name = $hostel_result->fetch_assoc()['Name'];
        
        // Pending bookings for this hostel
        $pending_bookings_query = "SELECT COUNT(*) as pending_bookings FROM bookings WHERE HostelID = ? AND BookingStatus = 'Pending'";
        $pending_bookings_stmt = $conn->prepare($pending_bookings_query);
        $pending_bookings_stmt->bind_param("i", $assigned_hostel_id);
        $pending_bookings_stmt->execute();
        $pending_bookings_result = $pending_bookings_stmt->get_result();
        $pending_bookings = $pending_bookings_result->fetch_assoc()['pending_bookings'];
        
        // Available rooms in this hostel
        $available_rooms_query = "SELECT COUNT(*) as available_rooms FROM rooms WHERE HostelID = ? AND AvailabilityStatus = 'Available'";
        $available_rooms_stmt = $conn->prepare($available_rooms_query);
        $available_rooms_stmt->bind_param("i", $assigned_hostel_id);
        $available_rooms_stmt->execute();
        $available_rooms_result = $available_rooms_stmt->get_result();
        $available_rooms = $available_rooms_result->fetch_assoc()['available_rooms'];
        
        // Get total rooms for this hostel (from hostels table)
        $total_rooms_query = "SELECT TotalRooms FROM hostels WHERE HostelID = ?";
        $total_rooms_stmt = $conn->prepare($total_rooms_query);
        $total_rooms_stmt->bind_param("i", $assigned_hostel_id);
        $total_rooms_stmt->execute();
        $total_rooms_result = $total_rooms_stmt->get_result();
        $total_rooms = $total_rooms_result->fetch_assoc()['TotalRooms'];
        
        // Get total registered users who have bookings in this hostel
        $users_query = "SELECT COUNT(DISTINCT UserID) as total_users FROM bookings WHERE HostelID = ?";
        $users_stmt = $conn->prepare($users_query);
        $users_stmt->bind_param("i", $assigned_hostel_id);
        $users_stmt->execute();
        $users_result = $users_stmt->get_result();
        $total_users = $users_result->fetch_assoc()['total_users'];
    }
}

// Recent bookings (for both admin types)
$recent_bookings_query = $is_super_admin 
    ? "SELECT b.*, u.FullName, h.Name as HostelName, r.RoomNumber 
       FROM bookings b 
       JOIN users u ON b.UserID = u.UserID 
       JOIN hostels h ON b.HostelID = h.HostelID 
       JOIN rooms r ON b.RoomID = r.RoomID 
       ORDER BY b.CreatedAt DESC LIMIT 5"
    : "SELECT b.*, u.FullName, h.Name as HostelName, r.RoomNumber 
       FROM bookings b 
       JOIN users u ON b.UserID = u.UserID 
       JOIN hostels h ON b.HostelID = h.HostelID 
       JOIN rooms r ON b.RoomID = r.RoomID 
       WHERE b.HostelID = ? 
       ORDER BY b.CreatedAt DESC LIMIT 5";

if ($is_super_admin) {
    $recent_bookings_result = $conn->query($recent_bookings_query);
} else {
    $recent_bookings_stmt = $conn->prepare($recent_bookings_query);
    $recent_bookings_stmt->bind_param("i", $assigned_hostel_id);
    $recent_bookings_stmt->execute();
    $recent_bookings_result = $recent_bookings_stmt->get_result();
}

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
                <h2><?php echo $is_super_admin ? 'Hostel Management Dashboard' : $hostel_name . ' Dashboard'; ?></h2>
                
                <div class="dashboard-card">
                    <?php if($is_super_admin): ?>
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
                    <?php else: ?>
                    <div class="card">
                        <a href="users.php" style="color: black; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='black'">
                            <h6 class="title">Total Registered Users</h6>
                            <h6 class="amount"><?php echo $total_users; ?></h6>
                        </a>
                    </div>
                    <div class="card">
                        <a href="#" style="color: black; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='black'">
                            <h6 class="title">Total Rooms</h6>
                            <h6 class="amount"><?php echo $total_rooms; ?></h6>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <a href="view_bookings.php" style="color: black; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='black'">
                            <h6 class="title">Total Bookings</h6>
                            <h6 class="amount"><?php echo $total_bookings; ?></h6>
                        </a>
                    </div>
                    <div class="card">
                        <a href="#" style="color: black; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='black'">
                            <h6 class="title">Available Rooms</h6>
                            <h6 class="amount"><?php echo $available_rooms; ?></h6>
                        </a>
                    </div>
                    <div class="card">
                        <a href="view_bookings.php?status=pending" style="color: black; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='black'">
                            <h6 class="title">Pending Approvals</h6>
                            <h6 class="amount"><?php echo $pending_bookings; ?></h6>
                        </a>
                    </div>
                </div>

</body>

</html>