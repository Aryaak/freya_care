<?php
// Include middleware using a relative path for better security
require_once __DIR__ . '/../config/Middleware.php';

// Check if the user is an admin
$middleware = new Middleware();
$middleware->administratorOnly();

// Get user data safely
$userName = htmlspecialchars($_COOKIE['user_name'] ?? 'Guest');
$userRole = $_COOKIE['user_role'] ?? '';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= getDomainUrl()?>/dashboard">Dashboard</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= getDomainUrl()?>/dashboard/categories">Categories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= getDomainUrl()?>/dashboard/users">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= getDomainUrl()?>/dashboard/stores">Stores</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= getDomainUrl()?>/dashboard/orders">Orders</a>
                </li>
            </ul>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                    id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="me-2"><?= $userName ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" aria-labelledby="dropdownUser">
                    <?php if($userRole == 'customer') : ?>
                        <li><a class="dropdown-item" href="<?= getDomainUrl()?>/profile.php">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="<?= getDomainUrl()?>/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>