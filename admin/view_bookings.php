<?php
session_start();
require_once('../includes/db_connection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit;
}

// Handle booking status change
if (isset($_GET['action']) && isset($_GET['booking_id'])) {
    $action = $_GET['action'];
    $booking_id = intval($_GET['booking_id']);

    switch ($action) {
        case 'approve':
            $sql = "UPDATE bookings SET BookingStatus = 'Confirmed' WHERE BookingID = ?";
            break;
        case 'cancel':
            $sql = "UPDATE bookings SET BookingStatus = 'Cancelled' WHERE BookingID = ?";
            break;
        case 'delete':
            $sql = "DELETE FROM bookings WHERE BookingID = ?";
            break;
        default:
            $sql = null;
    }

    if ($sql) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        // Redirect to avoid form resubmission
        header("Location: view_bookings.php");
        exit;
    }
}

// Get admin role and assigned hostel
$admin_id = $_SESSION['admin_id'];
$admin_query = "SELECT Role, AssignedHostelID FROM admins WHERE AdminID = ?";
$admin_stmt = $conn->prepare($admin_query);
$admin_stmt->bind_param("i", $admin_id);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$admin_data = $admin_result->fetch_assoc();

// Fetch booking details with user and hostel names
// If hostel_admin, only show bookings for their assigned hostel
$sql = "SELECT 
            b.BookingID, 
            u.FullName AS UserName, 
            h.Name AS HostelName, 
            r.RoomNumber,
            b.CheckInDate, 
            b.CheckOutDate, 
            b.TotalPrice, 
            b.BookingStatus, 
            b.CreatedAt 
        FROM bookings b
        JOIN users u ON b.UserID = u.UserID
        JOIN rooms r ON b.RoomID = r.RoomID
        JOIN hostels h ON r.HostelID = h.HostelID ";

if ($admin_data['Role'] == 'hostel_admin' && $admin_data['AssignedHostelID'] !== null) {
    $sql .= "WHERE b.HostelID = ? ";
    $sql .= "ORDER BY b.CreatedAt DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_data['AssignedHostelID']);
} else {
    $sql .= "ORDER BY b.CreatedAt DESC";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

// Check for SQL errors
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bookings</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .badge-text {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        .badge-text.text-success { background-color: #d4edda; color: #28a745; }
        .badge-text.text-warning { background-color: #fff3cd; color: #ffc107; }
        .badge-text.text-danger { background-color: #f8d7da; color: #dc3545; }
        .action-btns { display: flex; gap: 5px; }
        .action-btns a { 
            padding: 5px 10px; 
            text-decoration: none; 
            border-radius: 3px; 
            font-size: 0.8em; 
        }
        .btn-view { background-color: #17a2b8; color: white; }
        .btn-approve { background-color: #28a745; color: white; }
        .btn-cancel { background-color: #dc3545; color: white; }
        table { width: 100%; border-collapse: collapse; }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <div class="main">
            <?php include 'navbar.php'; ?>
            <h2>Bookings</h2>
            <div style="overflow-x:auto;">
                <table id="posts">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>User Name</th>
                            <th>Hostel Name</th>
                            <th>Room Number</th>
                            <th>Check-In Date</th>
                            <th>Check-Out Date</th>
                            <th>Total Price</th>
                            <th>Booking Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                // Determine status badge class
                                $status_class = match($row['BookingStatus']) {
                                    'Confirmed' => 'text-success',
                                    'Pending' => 'text-warning',
                                    'Cancelled' => 'text-danger',
                                    default => ''
                                };
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['BookingID']); ?></td>
                            <td><?php echo htmlspecialchars($row['UserName']); ?></td>
                            <td><?php echo htmlspecialchars($row['HostelName']); ?></td>
                            <td><?php echo htmlspecialchars($row['RoomNumber']); ?></td>
                            <td><?php echo date('d M Y', strtotime($row['CheckInDate'])); ?></td>
                            <td><?php echo date('d M Y', strtotime($row['CheckOutDate'])); ?></td>
                            <td>Rs.<?php echo number_format($row['TotalPrice']); ?></td>
                            <td>
                                <span class="badge-text <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($row['BookingStatus']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y H:i', strtotime($row['CreatedAt'])); ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="booking_details.php?id=<?php echo $row['BookingID']; ?>" class="btn-view">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <!-- <?php if ($row['BookingStatus'] == 'Pending'): ?>
                                        <a href="?action=approve&booking_id=<?php echo $row['BookingID']; ?>" class="btn-approve" onclick="return confirm('Are you sure you want to approve this booking?');">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <a href="?action=cancel&booking_id=<?php echo $row['BookingID']; ?>" class="btn-cancel" onclick="return confirm('Are you sure you want to cancel this booking?');">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?> -->
                                </div>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='10' style='text-align:center;'>No bookings found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php 
// Close the database connection
$conn->close(); 
?>