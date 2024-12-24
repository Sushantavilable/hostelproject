<?php
// Ensure session is started at the top of the file
session_start();
?>
<header>
    <div class="content flex_space">
        <div class="logo">
            <h1 style="color: black; font-weight:900;">Hostel Finders</h1>
        </div>
        <div class="navlinks">
            <ul id="menulist">
                <li><a href="index.php">home</a></li>
                <li><a href="hostels.php">find hostels</a></li>
                <li><a href="aboutus.php">about</a></li>
                <li><a href="contact.php">contact</a></li>

                <?php if (isset($_SESSION['username'])): ?>
                    <li><a href="my_bookings.php">my booking</a></li>

                    <li class="user-dropdown dropdown">
                        <a href="#" class="dropdown-toggle">
                            <i class="fas fa-user"></i>
                            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
                            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li><a href="login.php"><button class="primary-btn">LOG IN</button></a></li>
                <?php endif; ?>
            </ul>
            <span class="fa fa-bars" onclick="menutoggle()"></span>
        </div>
    </div>
</header>

<style>
    /* Dropdown Styles */
    .dropdown {
        position: relative;
    }

    .dropdown-toggle {
        display: flex;
        align-items: center;
        gap: 4px;
        cursor: pointer;
    }

    .dropdown-toggle i {
        margin: 0 4px;
    }

    .dropdown-menu {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        background: white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        border-radius: 4px;
        min-width: 100px;
        z-index: 1000;
        padding: 4px 0;
    }

    .dropdown-menu li {
        padding: 4px 6px;
    }

    .dropdown-menu li:hover {
        background: #f5f5f5;
    }

    .dropdown-menu a {
        font-size: 14px;    /* Smaller font size */
        color: #333;
        text-decoration: none;
        display: flex;
        align-items: center;
        padding-left: 4px;  /* Added small left padding */
    }

    .dropdown-menu a i {
        margin-right: 6px;
        font-size: 14px;
    }

    .dropdown:hover .dropdown-menu {
        display: block;
    }
</style>