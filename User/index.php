

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
        <?php include 'header.php'; include('../includes/db_connection.php'); ?>

        <section class="home">
            <div class="content">
                <div class="single-banner">
                    <img src="images/image1.jpg" alt="">
                    <div class="text">
                        <h1>Find Your Perfect Hostel</h1>
                        <p>Discover comfortable and affordable accommodation for your next adventure.</p>
                        <div class="flex">
                            <button> <a href="hostels.php" class="primary-btn">FIND HOSTELS</a>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rooms">
            <div class="container top">
                <div class="heading">
                    <h1>EXPLORE</h1>
                    <h2>Find Hostels</h2>
                </div>

                <div class="content mtop">
                    <div class="rooms-grid">
                        <?php
                        // First get all hostels
                        $query = "SELECT HostelID, Name, Description, ContactNumber, Address, City 
                    FROM hostels 
                    LIMIT 3";
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
                                    <div class="primary-btn ">
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
                <a href="hostels.php" class="primary-btn">VIEW MORE HOSTELS</a>
            </div>
        </section>


        <?php include 'footer.php'; ?>
    </body>

    </html>


 <section class="rooms">
        <div class="container top">
            <div class="heading">
                <h2>No Hostels Available</h2>
                <p>We couldn't find any hostels at the moment.</p>
            </div>
        </div>
    </section>
