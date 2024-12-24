<?php
session_start();
include('../includes/db_connection.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $identifier = trim($_POST['username']); // This can be either username or email
    $password = trim($_POST['password']);

    // Check in the users table
    $userQuery = "SELECT * FROM users WHERE Username = ? OR Email = ? LIMIT 1";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $userResult = $stmt->get_result();

    if ($userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc();
        if (password_verify($password, $user['PasswordHash'])) {
            // Successful user login
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['username'] = $user['Username'];
            header("Location: index.php");
            exit;
        }
    }

    // Check in the admins table with plain text password
    $adminQuery = "SELECT * FROM admins WHERE (Username = ? OR Email = ?) AND Password = ? LIMIT 1";
    $stmt = $conn->prepare($adminQuery);
    $stmt->bind_param("sss", $identifier, $identifier, $password);
    $stmt->execute();
    $adminResult = $stmt->get_result();

    if ($adminResult->num_rows > 0) {
        $admin = $adminResult->fetch_assoc();
        // Successful admin login
        $_SESSION['admin_id'] = $admin['AdminID'];
        $_SESSION['username'] = $admin['Username'];
        header("Location: ../admin/index.php");
        exit;
    }

    // If no match found
    $_SESSION['error'] = "Invalid username/email or password!";
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../assets/css/login-register.css">
</head>

<body>
    <div class="wrapper">
        <div class="container">
            <form action="login.php" method="POST" class="form">
                <h2>Login</h2>
                <?php
                if (isset($_SESSION['error'])) {
                    echo "<p style='color: red;'>" . $_SESSION['error'] . "</p>";
                    unset($_SESSION['error']);
                }
                ?>
                <div class="form-group">
                    <label for="username">Username or Email:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Login</button>
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </form>
        </div>
    </div>

</body>

</html>