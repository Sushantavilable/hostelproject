<?php
// Include database connection
require_once('../includes/db_connection.php');
// Query to fetch hostels with their primary images
$query = "SELECT h.HostelID, h.Name, h.Description, h.ContactNumber, h.Address, h.City,
            COALESCE(hi.ImagePath, 'images/hostels/default-hostel.jpg') as ImagePath 
            FROM hostels h 
            LEFT JOIN hostel_images hi ON h.HostelID = hi.HostelID 
            WHERE hi.IsPrimaryImage = 1 OR hi.ImagePath IS NULL";
$result = mysqli_query($conn, $query);

// Check if there are any hostels
if (mysqli_num_rows($result) > 0):
?>

    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Hostel Finders</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="../assets/css/main.css">

    </head>

    <body>
        <?php include 'header.php'; ?>

        <section class="rooms">
            <div class="container top">
                <div class="heading">
                    <h1>FIND YOUR HOSTELS</h1>
                    <h2>List of Hostels</h2>
                </div>

                <div class="content mtop">
                    <div class="rooms-grid">
                        <?php
                        // First get all hostels
                        $query = "SELECT HostelID, Name, Description, ContactNumber, Address, City FROM hostels";
                        $result = mysqli_query($conn, $query);

                        while ($hostel = mysqli_fetch_assoc($result)) {
                            // Get the primary image for this hostel
                            $imageQuery = "SELECT ImagePath FROM hostel_images WHERE HostelID = ? AND IsPrimaryImage = 1";
                            $stmt = $conn->prepare($imageQuery);
                            $stmt->bind_param("i", $hostel['HostelID']);
                            $stmt->execute();
                            $imageResult = $stmt->get_result();
                            $image = $imageResult->fetch_assoc();

                            // Set image path
                            $imagePath = $image ? '../' . $image['ImagePath'] : '../images/hostels/default-hostel.jpg';
                        ?>
                            <div class="items">
                                <div class="image">
                                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($hostel['Name']); ?>">
                                </div>
                                <div class="text">
                                    <h2><?php echo htmlspecialchars($hostel['Name']); ?></h2>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hostel['Address']); ?>, <?php echo htmlspecialchars($hostel['City']); ?></p>
                                    <p><?php echo htmlspecialchars(substr($hostel['Description'], 0, 100)); ?></p>
                                    <div class="button flex">
                                        <a href="hostel_details.php?id=<?php echo $hostel['HostelID']; ?>" class="primary-btn">VIEW DETAILS</a>
                                    </div>
                                </div>
                            </div>
                        <?php
                            $stmt->close();
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="more-button">
            </div>
        </section>


        <?php include 'footer.php'; ?>
    </body>

    </html>

<?php
else:
    // Display a message if no hostels are found
?>
    <section class="rooms">
        <div class="container top">
            <div class="heading">
                <h2>No Hostels Available</h2>
                <p>We couldn't find any hostels at the moment.</p>
            </div>
        </div>
    </section>
<?php
endif;

// Close the database connection
mysqli_close($conn);
?>