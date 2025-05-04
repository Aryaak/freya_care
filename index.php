<?php 
require_once 'layouts/head.php';
require_once 'config/Database.php';
require_once 'config/Middleware.php';
require_once 'class/Cart.php';
require_once 'class/Item.php';
require_once 'class/User.php';

$database = new Database();
$pdo = $database->getConnection();

$middleware = new Middleware();
$middleware->customerOnly();

$user = new User($pdo);
$cart = new Cart($pdo, $_COOKIE['user_id'] ?? null);
$item = new Item($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!$user->isLoggedIn()) {
        header("Location: login.php");
        exit;
    }

    $item_id = $_POST['item_id'];
    $user_id = $user->getUserId();

    try {
        $cart->addItem($item_id);
        $_SESSION['success'] = "Item added to cart successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding item to cart: " . $e->getMessage();
    }

    header("Location: cart.php");
    exit;
}

// Fetch items with filters
$filters = [
    'name' => $_GET['name'] ?? '',
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
    'category' => $_GET['category'] ?? ''
];
$items = $item->getItems($filters);
?>

<?php require_once "layouts/header.php" ?>

<main role="main">
    <!-- Alerts Section -->
    <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Hero Carousel -->
    <!-- Hero Carousel -->
    <div id="pharmacyCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#pharmacyCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#pharmacyCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#pharmacyCarousel" data-bs-slide-to="2"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="https://images.unsplash.com/photo-1587854692152-cbe660dbde88?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80"
                    class="d-block w-100" alt="Pharmacy Products" style="height: 500px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded">
                    <h2>Your Trusted Pharmacy</h2>
                    <p>Quality medicines and healthcare products for your family</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="https://images.unsplash.com/photo-1579684385127-1ef15d508118?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80"
                    class="d-block w-100" alt="Healthcare Products" style="height: 500px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded">
                    <h2>Healthcare Essentials</h2>
                    <p>Everything you need for your daily healthcare routine</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="https://images.unsplash.com/photo-1505751172876-fa1923c5c528?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80"
                    class="d-block w-100" alt="Special Offers" style="height: 500px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded">
                    <h2>Special Offers</h2>
                    <p>Get 20% off on selected products this week</p>
                    <a href="#products" class="btn btn-primary">Shop Now</a>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#pharmacyCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#pharmacyCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>

    <!-- Featured Categories -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Shop by Category</h2>
            <div class="row g-4">
                <?php
                $categories = $pdo->query("SELECT id, name FROM categories LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($categories as $category): 
                ?>
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <h5><?= htmlspecialchars($category['name']) ?></h5>
                            <a href="?category=<?= $category['id'] ?>" class="stretched-link"></a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Search and Filter Section -->
    <section class="py-5">
        <div class="container">
            <form method="GET" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-12">
                        <label for="name" class="form-label">Search Products</label>
                        <div class="input-group">
                            <input type="text" name="name" id="name" class="form-control"
                                placeholder="Search medicines and products..."
                                value="<?= htmlspecialchars($_GET['name'] ?? '') ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class='bx bx-search'></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="min_price" class="form-label">Min Price</label>
                        <input type="number" name="min_price" id="min_price" class="form-control" placeholder="Min"
                            value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="max_price" class="form-label">Max Price</label>
                        <input type="number" name="max_price" id="max_price" class="form-control" placeholder="Max"
                            value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="category" class="form-label">Category</label>
                        <select name="category" id="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php
                            $catStmt = $pdo->query("SELECT id, name FROM categories");
                            while ($cat = $catStmt->fetch(PDO::FETCH_ASSOC)) {
                                $selected = (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : '';
                                echo "<option value='{$cat['id']}' $selected>" . htmlspecialchars($cat['name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Products Section -->
    <section class="py-5" id="products">
        <div class="container">
            <h2 class="text-center mb-5">Our Products</h2>
            <?php if(empty($items)): ?>
            <div class="text-center py-5">
                <div class="alert alert-info">
                    <h3>No products found</h3>
                    <p>Please try different search criteria or browse our categories.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($items as $item): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <?php if(!empty($item['image'])): ?>
                        <img src="<?= htmlspecialchars($item['image']) ?>" class="card-img-top"
                            alt="<?= htmlspecialchars($item['name']) ?>" style="height: 250px; object-fit: contain;">
                        <?php else: ?>
                        <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center"
                            style="height: 250px;">
                            <span class="text-white">No Image Available</span>
                        </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?= htmlspecialchars($item['name']) ?></h5>
                                <span class="badge bg-primary"><?= htmlspecialchars($item['category_name']) ?></span>
                            </div>
                            <p class="card-text text-muted small">
                                <?= htmlspecialchars(substr($item['description'] ?? 'No description available', 0, 100)) ?>...
                            </p>
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="text-muted small">
                                        <i class='bx bxs-store-alt'></i>
                                        <?= htmlspecialchars($item['store_name']) ?>
                                    </div>
                                    <div class="h5 text-primary mb-0">
                                        Rp. <?= number_format($item['price'], 0, '.', '.') ?>
                                    </div>
                                </div>
                                <form method="POST" action="">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    <button type="submit" name="add_to_cart" class="btn btn-primary w-100">
                                        <i class='bx bxs-cart-add me-2'></i>
                                        Add to Cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Pharmacy Services -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                            style="width: 80px; height: 80px;">
                            <i class='bx bx-package text-primary' style="font-size: 2rem;"></i>
                        </div>
                        <h4>Fast Delivery</h4>
                        <p class="text-muted">Get your medicines delivered to your doorstep within 24 hours</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                            style="width: 80px; height: 80px;">
                            <i class='bx bx-shield-alt text-primary' style="font-size: 2rem;"></i>
                        </div>
                        <h4>Quality Guaranteed</h4>
                        <p class="text-muted">All our products are sourced from trusted manufacturers</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                            style="width: 80px; height: 80px;">
                            <i class='bx bx-support text-primary' style="font-size: 2rem;"></i>
                        </div>
                        <h4>24/7 Support</h4>
                        <p class="text-muted">Our pharmacists are available round the clock for consultations</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<footer class="bg-dark text-white pt-5 pb-4">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5>About Our Pharmacy</h5>
                <p>Your trusted partner in health and wellness since 2005. We provide quality medicines and healthcare
                    products with professional advice.</p>
                <div class="social-icons">
                    <a href="#" class="text-white me-2"><i class='bx bxl-facebook'></i></a>
                    <a href="#" class="text-white me-2"><i class='bx bxl-instagram'></i></a>
                    <a href="#" class="text-white me-2"><i class='bx bxl-twitter'></i></a>
                </div>
            </div>
            <div class="col-md-2 mb-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-white">Home</a></li>
                    <li><a href="#products" class="text-white">Products</a></li>
                    <li><a href="#" class="text-white">About Us</a></li>
                    <li><a href="#" class="text-white">Contact</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h5>Customer Service</h5>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-white">FAQs</a></li>
                    <li><a href="#" class="text-white">Shipping Policy</a></li>
                    <li><a href="#" class="text-white">Return Policy</a></li>
                    <li><a href="#" class="text-white">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h5>Contact Us</h5>
                <ul class="list-unstyled">
                    <li><i class='bx bx-map me-2'></i> 123 Health St, Medical City</li>
                    <li><i class='bx bx-phone me-2'></i> (123) 456-7890</li>
                    <li><i class='bx bx-envelope me-2'></i> info@pharmacystore.com</li>
                </ul>
            </div>
        </div>
        <hr class="my-4">
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0">&copy; 2023 Pharmacy Store. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="mb-0">We accept:
                    <i class='bx bxl-visa mx-1'></i>
                    <i class='bx bxl-mastercard mx-1'></i>
                    <i class='bx bxl-paypal mx-1'></i>
                </p>
            </div>
        </div>
    </div>
</footer>

<?php require_once('layouts/tail.php') ?>