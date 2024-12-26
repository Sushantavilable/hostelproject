<div class="dashboard-navbar">
    <form action="#">
        <!-- <div class="nav-search">
            <input type="text" class="search-query" placeholder="Search...">
            <button class="btn" type="button">Search</button>
        </div> -->
    </form>
    <div class="navbar-content">
        <ul class="main-nav">
            <li class="user-link dropdown">
                <a href="#" class="dropdown-toggle">
                    <i class="fa-solid fa-user"></i>
                    <span><?php echo $_SESSION['username']; ?></span>
                    <i class="fas fa-chevron-down"></i>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="setting.php"><i class="fas fa-user-circle"></i> Settings</a></li>
                    <li><a href="../user/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>
</div>
<style>
.dropdown {
    position: relative;
}

.dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    border-radius: 4px;
    min-width: 160px;
}

.dropdown-menu li {
    padding: 8px 16px;
}

.dropdown-menu li:hover {
    background: #f5f5f5;
}

.dropdown-menu a {
    color: #333;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
}

.dropdown:hover .dropdown-menu {
    display: block;
}
</style>

