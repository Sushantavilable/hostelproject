<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit;
}

// Include database connection
require_once('../includes/db_connection.php');

// Check admin role for access control
$admin_id = $_SESSION['admin_id'];
$role_query = "SELECT Role, AssignedHostelID FROM admins WHERE AdminID = ?";
$stmt = $conn->prepare($role_query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$role_result = $stmt->get_result();
$admin_data = $role_result->fetch_assoc();
$stmt->close();

// Fetch hostels based on admin role
if ($admin_data['Role'] === 'super_admin') {
    // Super admin can see all hostels
    $query = "SELECT * FROM hostels ORDER BY CreatedAt DESC";
    $result = $conn->query($query);
} else {
    // Hostel admin can only see their assigned hostel
    $query = "SELECT * FROM hostels WHERE HostelID = ? ORDER BY CreatedAt DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $admin_data['AssignedHostelID']);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Hostels</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <div class="main">
            <?php include 'navbar.php'; ?>
            <h2>Hostels</h2>
            <?php if ($admin_data['Role'] === 'super_admin'): ?>
            <a href="add_hostel.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Hostel
            </a>
            <?php endif; ?>
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
            <div style="overflow-x:auto;">
                <table id="posts">
                    <thead>
                        <tr>
                            <th>Hostel ID</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>City</th>
                            <th>Contact Number</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['HostelID']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['Name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['Address']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['City']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['ContactNumber']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['CreatedAt']) . "</td>";
                                echo "<td>
                                    <a href='manage_hostels.php?id=" . $row['HostelID'] . "' class='btn btn-primary' style='background-color: blue;'>
                                         <i class='fa-solid fa-bars-progress'></i> Manage
                                    </a>";
                                if ($admin_data['Role'] === 'super_admin') {
                                    echo "<a href='delete_hostel.php?action=delete&id=" . $row['HostelID'] . "' class='btn btn-secondary' style='background-color: #dc3545;' onclick='return confirm(\"Are you sure you want to delete this hostel?\");'>
                                        <i class='fas fa-trash'></i> Delete
                                    </a>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>No hostels found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    // Close the database connection
    $conn->close();
    ?>
</body>

</html>