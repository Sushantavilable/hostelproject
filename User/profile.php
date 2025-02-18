<?php
session_start();
include('../includes/db_connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to view this page!";
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch current user data - Changed 'ID' to 'UserID'
$query = "SELECT Username, Email, FullName, PhoneNumber FROM users WHERE UserID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "User not found!";
    header("Location: login.php");
    exit;
}

$user = $result->fetch_assoc();

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phonenumber = trim($_POST['phonenumber']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];
    
    // Check if email already exists for another user - Changed 'ID' to 'UserID'
    if ($email !== $user['Email']) {
        $checkQuery = "SELECT * FROM users WHERE Email = ? AND UserID != ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $emailResult = $stmt->get_result();
        
        if ($emailResult->num_rows > 0) {
            $_SESSION['error'] = "Email already exists for another user!";
            header("Location: profile.php");
            exit;
        }
    }
    
    // If user wants to change password
    if (!empty($current_password) && !empty($new_password)) {
        // Verify current password - Changed 'ID' to 'UserID'
        $passwordQuery = "SELECT PasswordHash FROM users WHERE UserID = ?";
        $stmt = $conn->prepare($passwordQuery);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $passwordResult = $stmt->get_result();
        $userData = $passwordResult->fetch_assoc();
        
        if (!password_verify($current_password, $userData['PasswordHash'])) {
            $_SESSION['error'] = "Current password is incorrect!";
            header("Location: profile.php");
            exit;
        }
        
        // Check if new password and confirm password match
        if ($new_password !== $confirm_new_password) {
            $_SESSION['error'] = "New passwords do not match!";
            header("Location: profile.php");
            exit;
        }
        
        // Hash the new password
        $passwordHash = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Update user profile with new password - Changed 'ID' to 'UserID'
        $updateQuery = "UPDATE users SET FullName = ?, Email = ?, PhoneNumber = ?, PasswordHash = ? WHERE UserID = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssssi", $fullname, $email, $phonenumber, $passwordHash, $user_id);
    } else {
        // Update user profile without changing password - Changed 'ID' to 'UserID'
        $updateQuery = "UPDATE users SET FullName = ?, Email = ?, PhoneNumber = ? WHERE UserID = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssi", $fullname, $email, $phonenumber, $user_id);
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Profile updated successfully!";
        
        // Update the user data after successful update
        $user['FullName'] = $fullname;
        $user['Email'] = $email;
        $user['PhoneNumber'] = $phonenumber;
    } else {
        $_SESSION['error'] = "Something went wrong. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="../assets/css/login-register.css">
</head>

<body>
    <div class="wrapper">
        <div class="container">
            <form
action="profile.php" method="POST" class="form">
                <h2>My Profile</h2>

                <?php
                if (isset($_SESSION['error'])) {
                    echo "<p style='color: red;'>" . $_SESSION['error'] . "</p>";
                    unset($_SESSION['error']);
                }
                if (isset($_SESSION['success'])) {
                    echo "<p style='color: green;'>" . $_SESSION['success'] . "</p>";
                    unset($_SESSION['success']);
                }
                ?>

                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['Username']); ?>" disabled>
                    <small>Username cannot be changed</small>
                </div>
                <div class="form-group">
                    <label for="fullname">Full Name:</label>
                    <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['FullName']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phonenumber">Phone Number:</label>
                    <input type="tel" id="phonenumber" name="phonenumber" value="<?php echo htmlspecialchars($user['PhoneNumber']); ?>" required>
                </div>
                
                <p style="color: black;text-align:left;margin-bottom:7px;">Change Password (leave blank to keep current password)</p>
                <div class="form-group">
                    <label for="current_password">Current Password:</label>
                    <input type="password" id="current_password" name="current_password">
                </div>
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password">
                </div>
                <div class="form-group">
                    <label for="confirm_new_password">Confirm New Password:</label>
                    <input type="password" id="confirm_new_password" name="confirm_new_password">
                </div>
                
                <button type="submit" class="btn">Update Profile</button>
                <p><a href="index.php">Back</a></p>
            </form>
        </div>
    </div>

</body>

</html>
