<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit;
}

require_once('../includes/db_connection.php');

if (isset($_GET['delete_image'])) {
    $imageId = intval($_GET['delete_image']);

    // Get image path before deletion
    $pathQuery = "SELECT ImagePath FROM hostel_images WHERE ImageID = ? AND HostelID = ?";
    $pathStmt = $conn->prepare($pathQuery);
    $pathStmt->bind_param("ii", $imageId, $hostelId);
    $pathStmt->execute();
    $imagePath = $pathStmt->get_result()->fetch_assoc()['ImagePath'];

    // Delete from database
    $deleteQuery = "DELETE FROM hostel_images WHERE ImageID = ? AND HostelID = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("ii", $imageId, $hostelId);

    if ($deleteStmt->execute()) {
        // Delete physical file
        unlink("../" . $imagePath);
        header("Location: edit_hostel.php?id=" . $hostelId);
        exit;
    }
}
$hostelId = intval($_GET['id']);

// Fetch existing hostel data
$query = "SELECT * FROM hostels WHERE HostelID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $hostelId);
$stmt->execute();
$result = $stmt->get_result();
$hostel = $result->fetch_assoc();

$imageQuery = "SELECT * FROM hostel_images WHERE HostelID = ?";
$imageStmt = $conn->prepare($imageQuery);
$imageStmt->bind_param("i", $hostelId);
$imageStmt->execute();
$images = $imageStmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $description = trim($_POST['description']);
    $contactNumber = trim($_POST['contact_number']);

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
        // Handle image uploads
        if (!empty($_FILES['new_images']['name'][0])) {
            $uploadDir = '../uploads/hostels/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $imageQuery = "INSERT INTO hostel_images (HostelID, ImagePath, IsPrimaryImage) VALUES (?, ?, ?)";
            $imageStmt = $conn->prepare($imageQuery);

            foreach ($_FILES['new_images']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['new_images']['error'][$key] == 0) {
                    $fileName = uniqid() . '_' . basename($_FILES['new_images']['name'][$key]);
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


        $_SESSION['success'] = "Hostel updated successfully!";
        header("Location: manage_hostels.php?id=" . $hostelId);
        exit;
    } else {
        $_SESSION['error'] = "Failed to update hostel.";
    }
}

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
    </style>
</head>

<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <div class="main">
            <?php include 'navbar.php'; ?>

            <h2>Edit Hostel</h2>

            <form method="POST" class="horizontal-form" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Hostel Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($hostel['Name']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="text" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($hostel['ContactNumber']); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="address">Address <span class="required">*</span></label>
                        <input type="text" id="address" name="address" required value="<?php echo htmlspecialchars($hostel['Address']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="city">City <span class="required">*</span></label>
                        <input type="text" id="city" name="city" required value="<?php echo htmlspecialchars($hostel['City']); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($hostel['Description']); ?></textarea>
                    </div>
                </div>
                <div class="form-group full-width">
                    <label for="new_images">Add More Images</label>
                    <input type="file" id="new_images" name="new_images[]" multiple accept="image/*">
                </div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Current Images</label>
                        <div class="image-gallery">
                            <?php while ($image = $images->fetch_assoc()): ?>
                                <div class="image-item">
                                    <img src="../<?php echo $image['ImagePath']; ?>" alt="Hostel Image">
                                    <a href="#"
                                        class="delete-image"
                                        data-image-id="<?php echo $image['ImageID']; ?>"
                                        data-hostel-id="<?php echo $hostelId; ?>">
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
                    <a href="manage_hostels.php?id=<?php echo $hostelId; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const imageGallery = document.querySelector('.image-gallery');

        imageGallery.addEventListener('click', function(e) {
            if (e.target.closest('.delete-image')) {
                e.preventDefault();
                const deleteLink = e.target.closest('.delete-image');
                const imageItem = deleteLink.closest('.image-item');
                const imageId = deleteLink.dataset.imageId;
                const hostelId = deleteLink.dataset.hostelId;

                if (confirm('Delete this image?')) {
                    fetch(`delete_hostel_image.php?image_id=${imageId}&hostel_id=${hostelId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                imageItem.remove();
                                document.getElementById('new_images').disabled = false;
                            }
                        });
                }
            }
        });
    });
</script>

</html>