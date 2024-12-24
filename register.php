<?php
session_start();
include('../includes/db_connection.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $fullname = trim($_POST['fullname']);
    $phonenumber = trim($_POST['phonenumber']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Check if password and confirm password match
    if ($password !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match!";
        header("Location: register.php");
        exit;
    }

    // Check if username or email already exists
    $checkQuery = "SELECT * FROM users WHERE Username = ? OR Email = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Username or Email already exists!";
        header("Location: register.php");
        exit;
    }

    // Hash the password before storing it
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Insert new user into the users table with fullname and phone number
    $insertQuery = "INSERT INTO users (Username, PasswordHash, Email, FullName, PhoneNumber) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("sssss", $username, $passwordHash, $email, $fullname, $phonenumber);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Registration successful! You can now login.";
        header("Location: login.php");
        exit;
    } else {
        $_SESSION['error'] = "Something went wrong. Please try again.";
        header("Location: register.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../assets/css/login-register.css">
</head>

<body>
    <div class="wrapper">
        <div class="container">
            <form action="register.php" method="POST" class="form">
                <h2>Register</h2>

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
                    <label for="fullname">Full Name:</label>
                    <input type="text" id="fullname" name="fullname" required>
                </div>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phonenumber">Phone Number:</label>
                    <input type="tel" id="phonenumber" name="phonenumber" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn">Register</button>
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </form>
        </div>
    </div>

</body>

</html>