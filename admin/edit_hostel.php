<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit;
}

require_once('../includes/db_connection.php');

// Define hostelId early to use throughout the file
$hostelId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validate hostelId exists
if ($hostelId <= 0) {
    $_SESSION['error'] = "Invalid hostel ID";
    header("Location: manage_hostels.php");
    exit;
}

// Handle image deletion requests via GET
if (isset($_GET['delete_image'])) {
    $imageId = intval($_GET['delete_image']);

    // Get image path before deletion
    $pathQuery = "SELECT ImagePath FROM hostel_images WHERE ImageID = ? AND HostelID = ?";
    $pathStmt = $conn->prepare($pathQuery);
    $pathStmt->bind_param("ii", $imageId, $hostelId);
    $pathStmt->execute();
    $result = $pathStmt->get_result();
    
    if ($result->num_rows > 0) {
        $imagePath = $result->fetch_assoc()['ImagePath'];
    
        // Delete from database
        $deleteQuery = "DELETE FROM hostel_images WHERE ImageID = ? AND HostelID = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("ii", $imageId, $hostelId);
    
        if ($deleteStmt->execute()) {
            // Delete physical file
            $fullPath = "../" . $imagePath;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            header("Location: edit_hostel.php?id=" . $hostelId);
            exit;
        }
    }
}

// Fetch existing hostel data
$query = "SELECT * FROM hostels WHERE HostelID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $hostelId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Hostel not found";
    header("Location: manage_hostels.php");
    exit;
}

$hostel = $result->fetch_assoc();

$imageQuery = "SELECT * FROM hostel_images WHERE HostelID = ?";
$imageStmt = $conn->prepare($imageQuery);
$imageStmt->bind_param("i", $hostelId);
$imageStmt->execute();
$images = $imageStmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Generate and verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid form submission";
        header("Location: edit_hostel.php?id=" . $hostelId);
        exit;
    }
    
    // Validate inputs
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $description = trim($_POST['description']);
    $contactNumber = trim($_POST['contact_number']);
    
    // Additional validation
    if (empty($name) || empty($address) || empty($city)) {
        $_SESSION['error'] = "Required fields cannot be empty";
        header("Location: edit_hostel.php?id=" . $hostelId);
        exit;
    }
    
    if (strlen($name) > 100) {
        $_SESSION['error'] = "Hostel name is too long";
        header("Location: edit_hostel.php?id=" . $hostelId);
        exit;
    }
    
    if (!empty($contactNumber) && !preg_match("/^[0-9+\-\s()]{5,20}$/", $contactNumber)) {
        $_SESSION['error'] = "Invalid contact number format";
        header("Location: edit_hostel.php?id=" . $hostelId);
        exit;
    }

    // Update the hostel details in the database
    $updateQuery = "UPDATE hostels SET 
                    Name = ?, 
                    Address = ?, 
                    City = ?, 
                    Description = ?, 
                    ContactNumber = ? 
                    WHERE HostelID = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param(
        "sssssi",
        $name,
        $address,
        $city,
        $description,
        $contactNumber,
        $hostelId
    );

    if ($updateStmt->execute()) {
        $updateSuccess = true;
        
        // Handle image uploads
        if (!empty($_FILES['new_images']['name'][0])) {
            $uploadDir = '../uploads/hostels/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            $imageQuery = "INSERT INTO hostel_images (HostelID, ImagePath, IsPrimaryImage) VALUES (?, ?, ?)";
            $imageStmt = $conn->prepare($imageQuery);
            
            $uploadErrors = [];

            foreach ($_FILES['new_images']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['new_images']['error'][$key] == 0) {
                    $fileSize = $_FILES['new_images']['size'][$key];
                    $fileType = $_FILES['new_images']['type'][$key];
                    
                    // Validate file type and size
                    if (!in_array($fileType, $allowedTypes)) {
                        $uploadErrors[] = "File type not allowed: " . $_FILES['new_images']['name'][$key];
                        continue;
                    }
                    
                    if ($fileSize > $maxSize) {
                        $uploadErrors[] = "File too large: " . $_FILES['new_images']['name'][$key];
                        continue;
                    }
                    
                    $fileName = uniqid() . '_' . basename($_FILES['new_images']['name'][$key]);
                    $uploadPath = $uploadDir . $fileName;

                    if (move_uploaded_file($tmpName, $uploadPath)) {
                        // Check if there are existing images
                        $checkQuery = "SELECT COUNT(*) as count FROM hostel_images WHERE HostelID = ?";
                        $checkStmt = $conn->prepare($checkQuery);
                        $checkStmt->bind_param("i", $hostelId);
                        $checkStmt->execute();
                        $checkResult = $checkStmt->get_result()->fetch_assoc();
                        
                        // Set as primary if it's the first image
                        $isPrimary = ($checkResult['count'] == 0 && $key === 0) ? 1 : 0;
                        $relativePath = str_replace('../', '', $uploadPath);

                        $imageStmt->bind_param("isi", $hostelId, $relativePath, $isPrimary);
                        if (!$imageStmt->execute()) {
                            $uploadErrors[] = "Database error for: " . $_FILES['new_images']['name'][$key];
                            // Delete the uploaded file if DB insert fails
                            unlink($uploadPath);
                        }
                    } else {
                        $uploadErrors[] = "Failed to move uploaded file: " . $_FILES['new_images']['name'][$key];
                    }
                } else if ($_FILES['new_images']['error'][$key] != UPLOAD_ERR_NO_FILE) {
                    $uploadErrors[] = "Upload error for: " . $_FILES['new_images']['name'][$key];
                }
            }
            
            if (!empty($uploadErrors)) {
                $_SESSION['warning'] = "Hostel updated but some images had errors: " . implode(", ", $uploadErrors);
            }
            
            $imageStmt->close();
        }

        if (empty($_SESSION['warning'])) {
            $_SESSION['success'] = "Hostel updated successfully!";
        }
        header("Location: manage_hostels.php?id=" . $hostelId);
        exit;
    } else {
        $_SESSION['error'] = "Failed to update hostel: " . $conn->error;
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Hostel</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .image-item {
            position: relative;
            height: 150px;
        }

        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }

        .delete-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 0, 0, 0.7);
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .delete-image:hover {
            background: rgba(255, 0, 0, 0.9);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeeba;
        }

        .required {
            color: red;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <div class="main">
            <?php include 'navbar.php'; ?>

            <h2>Edit Hostel</h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['warning'])): ?>
                <div class="alert alert-warning">
                    <?php 
                        echo $_SESSION['warning']; 
                        unset($_SESSION['warning']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="horizontal-form" enctype="multipart/form-data" id="edit-hostel-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Hostel Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" required maxlength="100" value="<?php echo htmlspecialchars($hostel['Name']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="text" id="contact_number" name="contact_number" maxlength="20" pattern="[0-9+\-\s()]{5,20}" title="Enter a valid phone number" value="<?php echo htmlspecialchars($hostel['ContactNumber']); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="address">Address <span class="required">*</span></label>
                        <input type="text" id="address" name="address" required maxlength="200" value="<?php echo htmlspecialchars($hostel['Address']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="city">City <span class="required">*</span></label>
                        <input type="text" id="city" name="city" required maxlength="100" value="<?php echo htmlspecialchars($hostel['City']); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" maxlength="1000"><?php echo htmlspecialchars($hostel['Description']); ?></textarea>
                    </div>
                </div>
                <div class="form-group full-width">
                    <label for="new_images">Add More Images</label>
                    <input type="file" id="new_images" name="new_images[]" multiple accept="image/jpeg,image/png,image/gif,image/webp">
                    <small>Allowed types: JPG, PNG, GIF, WEBP. Max size: 5MB per image.</small>
                </div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Current Images</label>
                        <div class="image-gallery">
                            <?php while ($image = $images->fetch_assoc()): ?>
                                <div class="image-item">
                                    <img src="../<?php echo htmlspecialchars($image['ImagePath']); ?>" alt="Hostel Image">
                                    <a href="edit_hostel.php?id=<?php echo $hostelId; ?>&delete_image=<?php echo $image['ImageID']; ?>"
                                        class="delete-image"
                                        onclick="return confirm('Are you sure you want to delete this image?');">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Hostel
                    </button>
                    <a href="manage_hostels.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        const form = document.getElementById('edit-hostel-form');
        form.addEventListener('submit', function(e) {
            let hasErrors = false;
            const name = document.getElementById('name');
            const address = document.getElementById('address');
            const city = document.getElementById('city');
            const contactNumber = document.getElementById('contact_number');
            
            // Reset previous error styles
            [name, address, city, contactNumber].forEach(field => {
                field.style.borderColor = '';
            });
            
            // Validate required fields
            if (!name.value.trim()) {
                name.style.borderColor = 'red';
                hasErrors = true;
            }
            
            if (!address.value.trim()) {
                address.style.borderColor = 'red';
                hasErrors = true;
            }
            
            if (!city.value.trim()) {
                city.style.borderColor = 'red';
                hasErrors = true;
            }
            
            // Validate contact number format
            if (contactNumber.value.trim() && !/^[0-9+\-\s()]{5,20}$/.test(contactNumber.value.trim())) {
                contactNumber.style.borderColor = 'red';
                hasErrors = true;
            }
            
            // Validate image uploads
            const fileInput = document.getElementById('new_images');
            if (fileInput.files.length > 0) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                
                for (let i = 0; i < fileInput.files.length; i++) {
                    const file = fileInput.files[i];
                    
                    if (file.size > maxSize) {
                        alert(`File "${file.name}" exceeds the maximum size of 5MB`);
                        hasErrors = true;
                    }
                    
                    if (!allowedTypes.includes(file.type)) {
                        alert(`File "${file.name}" is not an allowed image type`);
                        hasErrors = true;
                    }
                }
            }
            
            if (hasErrors) {
                e.preventDefault();
                alert('Please fix the errors in the form before submitting.');
            }
        });
    });
    </script>
</body>
</html>