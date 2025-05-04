<?php 
// Include middleware using a relative path for better security
require_once __DIR__ . '/../config/Middleware.php';

// Check if the user is an customer
$middleware = new Middleware();

$middleware->customerOnly();
?>

<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container d-flex justify-content-between">
            <a class="navbar-brand" href="index.php">Freya Care</a>

            <div>
                <?php if(isset($_COOKIE['user_id'])): ?>
                <?php if($_COOKIE['user_role'] === 'administrator'): ?>
                <?php endif; ?>
                <div class="dropdown d-flex align-items-center">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                        id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="me-2"><?= htmlspecialchars($_COOKIE['user_name'] ?? 'Guest') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" aria-labelledby="dropdownUser">
                        <li><a class="dropdown-item" href="<?= getDomainUrl() . '/profile.php' ?>">Profile</a></li>
                        <li><a class="dropdown-item" href="<?= getDomainUrl() . '/orders' ?>">Orders</a></li>
                        <li><a class="dropdown-item" href="<?= getDomainUrl() . '/store' ?>">Store</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="<?= getDomainUrl() . '/logout.php' ?>">Logout</a></li>
                    </ul>
                    <a href="cart.php" class="btn btn-outline-light ms-3">
                        <i class='bx bxs-cart-alt'></i>
                        <?php if(isset($_SESSION['user_id'])): ?>
                        <?php 
                        // Get cart item count
                        $stmt = $pdo->prepare("
                            SELECT SUM(cd.qty) as total_items
                            FROM cart_details cd
                            JOIN carts c ON cd.cart_id = c.id
                            WHERE c.user_id = ?
                        ");
                        $stmt->execute([$_SESSION['user_id']]);
                        $cart_count = $stmt->fetchColumn();
                        ?>
                        <?php if($cart_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $cart_count ?>
                        </span>
                        <?php endif; ?>
                        <?php endif; ?>
                    </a>
                </div>
                <?php else: ?>
                <a href="login.php" class="btn btn-primary">Login</a>
                <a href="register.php" class="btn btn-outline-light ms-2">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>