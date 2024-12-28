<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">

</head>

<body>
    <?php include 'header.php'; ?>
    <section class="about top">
        <style>
            .contact-form {
                max-width: 400px;
                margin: 40px auto;
                padding: 30px;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                background: #fff;
            }

            .form-group {
                margin-bottom: 10px;
            }

            .form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
                color: #333;
                font-size: 14px;
            }

            .form-group input,
            .form-group textarea {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }

            .form-group textarea {
                height: 80px;
                resize: vertical;
            }

            .submit-btn {
                width: 100%;
                background-color: #2c3e50;
                color: white;
                padding: 10px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                transition: background 0.3s ease;
            }

            .submit-btn:hover {
                background-color: #34495e;
            }
        </style>
        <div class="contact-form">
            <h2>Contact Us</h2>
            <form action="process_contact.php" method="POST">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" required>
                </div>

                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" required></textarea>
                </div>

                <button type="submit" class="submit-btn">Send Message</button>
            </form>
        </div>

    </section>
    <?php include 'footer.php'; ?>

</body>

</html>