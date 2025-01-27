<?php
session_start();
require_once('../includes/db_connection.php');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$imageId = intval($_GET['image_id']);
$hostelId = intval($_GET['hostel_id']);

// Get image path before deletion
$pathQuery = "SELECT ImagePath FROM hostel_images WHERE ImageID = ? AND HostelID = ?";
$pathStmt = $conn->prepare($pathQuery);
$pathStmt->bind_param("ii", $imageId, $hostelId);
$pathStmt->execute();
$result = $pathStmt->get_result();
$image = $result->fetch_assoc();

if ($image) {
    // Delete from database
    $deleteQuery = "DELETE FROM hostel_images WHERE ImageID = ? AND HostelID = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("ii", $imageId, $hostelId);
    
    if ($deleteStmt->execute()) {
        // Delete physical file
        unlink("../" . $image['ImagePath']);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
