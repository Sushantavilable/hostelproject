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

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Basic validation
    if (empty($username) || empty($email)) {
        $_SESSION['message'] = "Username and Email are required";
        $_SESSION['message_type'] = 'danger';
    } else {
        // Check if username already exists for other users
        $check_username = "SELECT UserID FROM users WHERE Username = ? AND UserID != ?";
        $stmt = $conn->prepare($check_username);
        $stmt->bind_param("si", $username, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['message'] = "Username already exists!";
            $_SESSION['message_type'] = 'danger';
        } else {
            // Check if email already exists for other users
            $check_email = "SELECT UserID FROM users WHERE Email = ? AND UserID != ?";
            $stmt = $conn->prepare($check_email);
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $_SESSION['message'] = "Email already exists!";
                $_SESSION['message_type'] = 'danger';
            } else {
                // Update user
                $update_query = "UPDATE users SET Username = ?, FullName = ?, Email = ?, PhoneNumber = ? WHERE UserID = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("ssssi", $username, $fullname, $email, $phone, $user_id);

                if ($stmt->execute()) {
                    $_SESSION['message'] = "User updated successfully!";
                    $_SESSION['message_type'] = 'success';
                    header("Location: user_details.php?id=" . $user_id);
                    exit;
                } else {
                    $_SESSION['message'] = "Error updating user: " . $conn->error;
                    $_SESSION['message_type'] = 'danger';
                }
            }
        }
    }
}

// Fetch current user data
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
    <title>Edit User</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .edit-user-form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-actions {
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            margin-right: 10px;
            border-radius: 4px;
            text-decoration: none;
            color: #fff;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn-primary {
            background-color: #007bff;
        }

        .btn-secondary {
            background-color: #6c757d;
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

            <div class="edit-user-form">
                <h3>Edit User</h3>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username*</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['Username']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['FullName']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email*</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['PhoneNumber']); ?>">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update User</button>
                        <a href="user_details.php?id=<?php echo $user_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>