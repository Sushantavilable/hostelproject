<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit;
}

// Include database connection
require_once('../includes/db_connection.php');

// Fetch current admin details
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT Username, Email, Password FROM admins WHERE AdminID = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$success_message = '';
$error_messages = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate current password
    if ($current_password !== $admin['Password']) {
        $error_messages[] = "Current password is incorrect.";
    }

    // Validate new password
    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            $error_messages[] = "New passwords do not match.";
        }
        if (strlen($new_password) < 4) {
            $error_messages[] = "New password must be at least 4 characters long.";
        }
    }

    // If no errors, update admin details
    if (empty($error_messages)) {
        try {
            // Prepare update query
            $update_query = "UPDATE admins SET Username = ?, Email = ?";
            $params = [$new_username, $new_email];
            $param_types = "ss";

            // Add password update if new password provided
            if (!empty($new_password)) {
                $update_query .= ", Password = ?";
                $params[] = $new_password;
                $param_types .= "s";
            }

            $update_query .= " WHERE AdminID = ?";
            $params[] = $admin_id;
            $param_types .= "i";

            $stmt = $conn->prepare($update_query);
            
            // Dynamically bind parameters based on types
            $stmt->bind_param($param_types, ...$params);
            $stmt->execute();

            // Set success message
            $success_message = "Settings updated successfully!";

            // Update admin array with new values
            $admin['Username'] = $new_username;
            $admin['Email'] = $new_email;
            if (!empty($new_password)) {
                $admin['Password'] = $new_password;
            }
        } catch (Exception $e) {
            $error_messages[] = "Update failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
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
                <h2>Admin Settings</h2>
                
                <?php 
                // Display success message
                if (!empty($success_message)) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>';
                }

                // Display error messages
                if (!empty($error_messages)) {
                    echo '<div class="alert alert-danger">';
                    foreach ($error_messages as $error) {
                        echo '<p>' . htmlspecialchars($error) . '</p>';
                    }
                    echo '</div>';
                }
                ?>

                <form class="admin-settings-form" method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($admin['Username']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($admin['Email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password (optional)</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Update Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>