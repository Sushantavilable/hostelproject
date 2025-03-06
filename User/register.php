<?php
session_start();
include('../includes/db_connection.php');

// AJAX validation endpoint
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch($_GET['action']) {
        case 'validate_username':
            validateUsername($conn);
            break;
        case 'validate_email':
            validateEmail($conn);
            break;
        case 'validate_phone':
            validatePhoneNumber();
            break;
    }
    exit;
}

function validateUsername($conn) {
    $username = trim($_GET['username']);
    $response = ['valid' => true, 'message' => ''];

    // Username length check
    if (strlen($username) < 3 || strlen($username) > 20) {
        $response = ['valid' => false, 'message' => 'Username must be 3-20 characters long'];
    }

    // Username character validation (alphanumeric and underscore)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $response = ['valid' => false, 'message' => 'Username can only contain letters, numbers, and underscores'];
    }

    // Check for existing username
    $stmt = $conn->prepare("SELECT Username FROM users WHERE Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response = ['valid' => false, 'message' => 'Username already exists'];
    }

    echo json_encode($response);
}

function validateEmail($conn) {
    $email = trim($_GET['email']);
    $response = ['valid' => true, 'message' => ''];

    // Basic email format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = ['valid' => false, 'message' => 'Invalid email format'];
    }

    // Check for existing email
    $stmt = $conn->prepare("SELECT Email FROM users WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response = ['valid' => false, 'message' => 'Email already registered'];
    }

    echo json_encode($response);
}

function validatePhoneNumber() {
    $phone = trim($_GET['phone']);
    $response = ['valid' => true, 'message' => ''];

    // Validate phone number (North American format)
    $phoneRegex = '/^(?:\+?1[-.\s]?)?(?:\(\d{3}\)[-.\s]?|\d{3}[-.\s]?)\d{3}[-.\s]?\d{4}$/';
    if (!preg_match($phoneRegex, $phone)) {
        $response = ['valid' => false, 'message' => 'Invalid phone number format'];
    }

    echo json_encode($response);
}

// Server-side registration processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $fullname = trim($_POST['fullname']);
    $phonenumber = trim($_POST['phonenumber']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Comprehensive server-side validation
    $errors = [];

    // Full name validation
    if (empty($fullname) || strlen($fullname) < 2 || strlen($fullname) > 50) {
        $errors[] = "Invalid full name";
    }

    // Username validation
    if (strlen($username) < 3 || strlen($username) > 20 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Invalid username format";
    }

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Phone validation
    $phoneRegex = '/^(?:\+?1[-.\s]?)?(?:\(\d{3}\)[-.\s]?|\d{3}[-.\s]?)\d{3}[-.\s]?\d{4}$/';
    if (!preg_match($phoneRegex, $phonenumber)) {
        $errors[] = "Invalid phone number format";
    }

    // Password validation
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }

    // Check for existing username or email
    $stmt = $conn->prepare("SELECT * FROM users WHERE Username = ? OR Email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Username or email already exists";
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insert new user
        $insertQuery = "INSERT INTO users (Username, PasswordHash, Email, FullName, PhoneNumber) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("sssss", $username, $passwordHash, $email, $fullname, $phonenumber);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Registration successful! You can now login.";
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }

    // Store errors in session if registration fails
    $_SESSION['errors'] = $errors;
    header("Location: register.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../assets/css/login-register.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .error-message {
            color: red;
            font-size: 0.8em;
            margin-top: 5px;
        }
        .form-group {
            position: relative;
        }
        .valid-message {
            color: green;
            font-size: 0.8em;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <form id="registrationForm" action="register.php" method="POST" class="form">
                <h2>Register</h2>

                <?php
                if (isset($_SESSION['errors'])) {
                    echo "<div class='server-errors'>";
                    foreach ($_SESSION['errors'] as $error) {
                        echo "<p style='color: red;'>" . htmlspecialchars($error) . "</p>";
                    }
                    echo "</div>";
                    unset($_SESSION['errors']);
                }
                ?>

                <div class="form-group">
                    <label for="fullname">Full Name:</label>
                    <input type="text" id="fullname" name="fullname" required>
                    <div id="fullname-error" class="error-message"></div>
                </div>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                    <div id="username-error" class="error-message"></div>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                    <div id="email-error" class="error-message"></div>
                </div>
                <div class="form-group">
                    <label for="phonenumber">Phone Number:</label>
                    <input type="tel" id="phonenumber" name="phonenumber" required>
                    <div id="phone-error" class="error-message"></div>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                    <div id="password-error" class="error-message"></div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <div id="confirm-password-error" class="error-message"></div>
                </div>
                <button type="submit" class="btn">Register</button>
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </form>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Debounce function
        function debounce(func, timeout = 300){
            let timer;
            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => { func.apply(this, args); }, timeout);
            };
        }

        // Full name validation
        $('#fullname').on('input', function() {
            const fullname = $(this).val();
            const errorDiv = $('#fullname-error');
            
            errorDiv.text('');
            
            if (fullname.length < 2 || fullname.length > 50) {
                errorDiv.text('Full name must be 2-50 characters long');
            }
        });

        // Username validation
        $('#username').on('input', debounce(function() {
            const username = $(this).val();
            const errorDiv = $('#username-error');
            
            errorDiv.text('');
            
            if (username.length < 3 || username.length > 20) {
                errorDiv.text('Username must be 3-20 characters long');
                return;
            }
            
            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                errorDiv.text('Username can only contain letters, numbers, and underscores');
                return;
            }

            // Server-side validation
            $.get('register.php', { action: 'validate_username', username: username }, function(response) {
                if (!response.valid) {
                    errorDiv.text(response.message);
                }
            }, 'json');
        }));

        // Email validation
        $('#email').on('input', debounce(function() {
            const email = $(this).val();
            const errorDiv = $('#email-error');
            
            errorDiv.text('');
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                errorDiv.text('Invalid email format');
                return;
            }

            // Server-side validation
            $.get('register.php', { action: 'validate_email', email: email }, function(response) {
                if (!response.valid) {
                    errorDiv.text(response.message);
                }
            }, 'json');
        }));

        // Validation for phone number
        $('#phonenumber').on('input', debounce(function() {
            const phone = $(this).val();
            const errorDiv = $('#phone-error');
            
            errorDiv.text('');
            
            const phoneRegex = /^(?:\+?1[-.\s]?)?(?:\(\d{3}\)[-.\s]?|\d{3}[-.\s]?)\d{3}[-.\s]?\d{4}$/;
            if (!phoneRegex.test(phone)) {
                errorDiv.text('Invalid phone number format');
            }
        }));

        // Password validation
        $('#password').on('input', function() {
            const password = $(this).val();
            const errorDiv = $('#password-error');
            
            errorDiv.text('');
            
            if (password.length < 8) {
                errorDiv.text('Password must be at least 8 characters long');
            }
        });

        // Confirm password validation
        $('#confirm_password').on('input', function() {
            const password = $('#password').val();
            const confirmPassword = $(this).val();
            const errorDiv = $('#confirm-password-error');
            
            errorDiv.text('');
            
            if (password !== confirmPassword) {
                errorDiv.text('Passwords do not match');
            }
        });
    });
    </script>
</body>
</html>