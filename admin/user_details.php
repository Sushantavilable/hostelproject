<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit;
}

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid user ID";
    $_SESSION['message_type'] = 'danger';
    header("Location: users.php");
    exit;
}

include('../includes/db_connection.php');

$user_id = (int)$_GET['id'];

// Fetch user details
$query = "SELECT * FROM users WHERE UserID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "User not found";
    $_SESSION['message_type'] = 'danger';
    header("Location: users.php");
    exit;
}

$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .user-details {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }

        .user-details h3 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .detail-label {
            width: 150px;
            font-weight: bold;
            color: #555;
        }

        .detail-value {
            flex: 1;
            color: #333;
        }

        .actions {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }

        .btn {
            padding: 8px 15px;
            margin-right: 10px;
            border-radius: 4px;
            text-decoration: none;
            color: #fff;
            display: inline-block;
        }

        .btn-back {
            background-color: #6c757d;
        }

        .btn-edit {
            background-color: #007bff;
        }

        .btn-delete {
            background-color: #dc3545;
        }

        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <div class="main">
            <?php include 'navbar.php'; ?>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php 
                        echo $_SESSION['message']; 
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="user-details">
                <h3>User Details</h3>
                
                <div class="detail-row">
                    <div class="detail-label">Username:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['Username']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Full Name:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['FullName']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['Email']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Phone Number:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['PhoneNumber']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Created At:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['CreatedAt']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                        <?php 
                            $status = isset($user['Status']) ? $user['Status'] : 'Active';
                            echo htmlspecialchars($status); 
                        ?>
                    </div>
                </div>

                <div class="actions">
                    <a href="users.php" class="btn btn-back">Back to Users</a>
                    <a href="edit_user.php?id=<?php echo $user['UserID']; ?>" class="btn btn-edit">Edit User</a>
                    <a href="delete_user.php?id=<?php echo $user['UserID']; ?>" 
                       onclick="return confirm('Are you sure you want to delete this user?');" 
                       class="btn btn-delete">Delete User</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>