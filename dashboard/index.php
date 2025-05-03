<?php require_once('../layouts/head.php') ?>

<?php require_once('../layouts/navbar.php') ?>

<?php
require_once '../config/Middleware.php';

// Check if the user is an admin
$middleware = new Middleware();
$middleware->requireAdmin();
?>

<section class="container mt-4">
    <h1>Welcome Administrator!</h1>
</section>

<?php require_once('../layouts/tail.php') ?>
