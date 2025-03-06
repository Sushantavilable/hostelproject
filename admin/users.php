<?php 
session_start();  

// Check if admin is logged in 
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit; 
}

include('../includes/db_connection.php');

// Get admin details and their assigned hostel
$admin_id = $_SESSION['admin_id'];
$admin_query = "SELECT Role, AssignedHostelID FROM admins WHERE AdminID = $admin_id";
$admin_result = mysqli_query($conn, $admin_query);
$admin_data = mysqli_fetch_assoc($admin_result);

// If admin has an assigned hostel (hostel_admin) or if a specific hostel is selected
$hostel_id = null;
if (isset($_GET['hostel_id']) && $admin_data['Role'] == 'super_admin') {
    $hostel_id = $_GET['hostel_id'];
} elseif ($admin_data['Role'] == 'hostel_admin') {
    $hostel_id = $admin_data['AssignedHostelID'];
}

// Get all hostels for dropdown if super admin
$hostels = [];
if ($admin_data['Role'] == 'super_admin') {
    $hostels_query = "SELECT HostelID, Name FROM hostels";
    $hostels_result = mysqli_query($conn, $hostels_query);
    while ($hostel = mysqli_fetch_assoc($hostels_result)) {
        $hostels[] = $hostel;
    }
}

// Fetch users based on hostel
if ($hostel_id) {
    // Get users who have bookings at this hostel
    $query = "SELECT DISTINCT u.UserID, u.Username, u.FullName, u.Email, u.PhoneNumber, u.CreatedAt 
              FROM users u
              INNER JOIN bookings b ON u.UserID = b.UserID
              WHERE b.HostelID = $hostel_id
              ORDER BY u.CreatedAt DESC";
} else if ($admin_data['Role'] == 'super_admin') {
    // Super admin without filter sees all users
    $query = "SELECT UserID, Username, FullName, Email, PhoneNumber, CreatedAt 
              FROM users
              ORDER BY CreatedAt DESC";
} else {
    // Edge case: hostel admin with no assigned hostel
    $query = "SELECT UserID, Username, FullName, Email, PhoneNumber, CreatedAt 
              FROM users 
              WHERE 1=0"; // Empty result set
}

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Users</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <div class="main">
            <?php include 'navbar.php'; ?>
            
            <h2>
                <?php 
                if ($hostel_id) {
                    $hostel_name_query = "SELECT Name FROM hostels WHERE HostelID = $hostel_id";
                    $hostel_name_result = mysqli_query($conn, $hostel_name_query);
                    $hostel_name = mysqli_fetch_assoc($hostel_name_result)['Name'];
                    echo "Users for " . htmlspecialchars($hostel_name); 
                } else {
                    echo "All Users";
                }
                ?>
            </h2>
            
            <?php if ($admin_data['Role'] == 'super_admin' && count($hostels) > 0): ?>
            <div class="filter-section">
                <form method="get" action="">
                    <select name="hostel_id" onchange="this.form.submit()">
                        <option value="">All Hostels</option>
                        <?php foreach ($hostels as $hostel): ?>
                            <option value="<?php echo $hostel['HostelID']; ?>" <?php echo ($hostel_id == $hostel['HostelID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($hostel['Name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php
                        echo $_SESSION['message'];
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>
            
            <div style="overflow-x:auto;">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <table id="posts">
                        <thead>
                            <tr>
                                <th>SN</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone Number</th>
                                <th>Created At</th>
                                <?php if ($hostel_id): ?>
                                <th>Booking Status</th>
                                <?php endif; ?>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sn = 1;
                            while ($row = mysqli_fetch_assoc($result)) {
                                // Get the booking status for this user at this hostel (only if a hostel is selected)
                                $booking_status = "";
                                if ($hostel_id) {
                                    $booking_query = "SELECT BookingStatus FROM bookings 
                                                     WHERE UserID = {$row['UserID']} 
                                                     AND HostelID = $hostel_id
                                                     ORDER BY CreatedAt DESC LIMIT 1";
                                    $booking_result = mysqli_query($conn, $booking_query);
                                    if (mysqli_num_rows($booking_result) > 0) {
                                        $booking_data = mysqli_fetch_assoc($booking_result);
                                        $booking_status = $booking_data['BookingStatus'];
                                    } else {
                                        $booking_status = "N/A";
                                    }
                                }
                            ?>
                                <tr>
                                    <td><?php echo $sn++; ?></td>
                                    <td><?php echo htmlspecialchars($row['Username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['FullName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['PhoneNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($row['CreatedAt']); ?></td>
                                    <?php if ($hostel_id): ?>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($booking_status); ?>">
                                            <?php echo $booking_status; ?>
                                        </span>
                                    </td>
                                    <?php endif; ?>
                                    <td>
                                        <a href="user_details.php?id=<?php echo $row['UserID']; ?><?php echo $hostel_id ? '&hostel_id='.$hostel_id : ''; ?>">View</a>
                                        <?php if ($admin_data['Role'] == 'super_admin'): ?>
                                            <a href="delete_user.php?id=<?php echo $row['UserID']; ?>" 
                                               onclick="return confirm('Are you sure you want to delete this user? This will remove all their bookings as well.');">Delete</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-records">
                        <p>No users found for this hostel</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>