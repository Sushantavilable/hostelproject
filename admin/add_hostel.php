<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit;
}

// Include database connection
require_once('../includes/db_connection.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $description = trim($_POST['description']);
    $contactNumber = trim($_POST['contact_number']);

    // Validate required fields
    $errors = [];
    if (empty($name)) $errors[] = "Hostel name is required.";
    if (empty($address)) $errors[] = "Address is required.";
    if (empty($city)) $errors[] = "City is required.";

    // If no errors, proceed with insertion
    if (empty($errors)) {
        try {
            // Begin transaction
            $conn->begin_transaction();

            // Insert hostel details
            $query = "INSERT INTO hostels (Name, Address, City, Description, ContactNumber, AdminID) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "sssssi",
                $name,
                $address,
                $city,
                $description,
                $contactNumber,
                $_SESSION['admin_id']
            );

            if ($stmt->execute()) {
                $hostelId = $conn->insert_id;

                // Handle file uploads
                if (!empty($_FILES['hostel_images']['name'][0])) {
                    $uploadDir = '../uploads/hostels/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                
                    $imageQuery = "INSERT INTO hostel_images (HostelID, ImagePath, IsPrimaryImage) VALUES (?, ?, ?)";
                    $imageStmt = $conn->prepare($imageQuery);
                
                    foreach ($_FILES['hostel_images']['tmp_name'] as $key => $tmpName) {
                        if ($_FILES['hostel_images']['error'][$key] == 0) {
                            $fileName = uniqid() . '_' . basename($_FILES['hostel_images']['name'][$key]);
                            $uploadPath = $uploadDir . $fileName;
                            
                            if (move_uploaded_file($tmpName, $uploadPath)) {
                                $isPrimary = ($key === 0) ? 1 : 0;
                                $relativePath = str_replace('../', '', $uploadPath);
                                
                                $imageStmt->bind_param("isi", $hostelId, $relativePath, $isPrimary);
                                $imageStmt->execute();
                            }
                        }
                    }
                    $imageStmt->close();
                }
                // Commit transaction
                $conn->commit();

                // Set success message
                $_SESSION['message'] = "Hostel added successfully!";
                header("Location: view_hostels.php");
                exit;
            } else {
                throw new Exception("Failed to insert hostel: " . $stmt->error);
            }
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            $_SESSION['error'] = $e->getMessage();
        }
    } else {
        // Store errors in session
        $_SESSION['error'] = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Hostel</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <div class="main">
            <?php include 'navbar.php'; ?>

            <h2>Add Hostel</h2>

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

            <form action="" method="POST" enctype="multipart/form-data" class="horizontal-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Hostel Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="text" id="contact_number" name="contact_number" value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="address">Address <span class="required">*</span></label>
                        <input type="text" id="address" name="address" required value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="city">City <span class="required">*</span></label>
                        <input type="text" id="city" name="city" required value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="hostel_images">Hostel Images (Multiple)</label>
                        <input type="file" id="hostel_images" name="hostel_images[]" multiple accept="image/*">
                        <small>You can select multiple images at once</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Hostel
                    </button>
                    <a href="view_hostels.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
<?php
// Close the database connection
$conn->close();
?>