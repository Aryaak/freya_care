<?php require_once('layouts/head.php') ?>
<?php
require_once 'layouts/head.php';
require_once 'config/Database.php';
require_once 'class/Cart.php';
require_once 'class/Item.php';
require_once 'class/User.php';

$database = new Database();
$pdo = $database->getConnection();

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
        $cart->addItem( $item_id);
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

    <section class="jumbotron text-center">

        <section class="jumbotron text-center">
            <div class="container py-5">
                <h1>Album example</h1>
                <p class="lead text-muted">Something short and leading about the collection below—its contents, the
                    creator,
                    etc. Make it short and sweet, but not too short so folks don’t simply skip over it entirely.</p>
                <p>
                    <a href="#" class="btn btn-primary my-2">Main call to action</a>
                    <a href="#" class="btn btn-secondary my-2">Secondary action</a>
                </p>
            </div>
        </section>

        <form method="GET" class="container mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-12">
                    <label for="name" class="form-label">Search</label>
                    <input type="text" name="name" id="name" class="form-control"
                        value="<?= htmlspecialchars($_GET['name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label for="min_price" class="form-label">Min Price</label>
                    <input type="number" name="min_price" id="min_price" class="form-control"
                        value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label for="max_price" class="form-label">Max Price</label>
                    <input type="number" name="max_price" id="max_price" class="form-control"
                        value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label for="category" class="form-label">Category</label>
                    <select name="category" id="category" class="form-select">
                        <option value="">-- All Categories --</option>
                        <?php
                // Fetch categories for dropdown
                $catStmt = $pdo->query("SELECT id, name FROM categories");
                while ($cat = $catStmt->fetch(PDO::FETCH_ASSOC)) {
                    $selected = (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : '';
                    echo "<option value='{$cat['id']}' $selected>" . htmlspecialchars($cat['name']) . "</option>";
                }
                ?>
                    </select>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                </div>
            </div>
        </form>


        <div class="album py-5 bg-light" id="products">
            <div class="container">
                <?php if(empty($items)): ?>
                <div class="text-center py-5">
                    <h3>No products available at the moment</h3>
                    <p>Please check back later or contact us for more information.</p>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($items as $item): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <?php if(!empty($item['image'])): ?>
                            <img src="<?= htmlspecialchars($item['image']) ?>" class="card-img-top"
                                alt="<?= htmlspecialchars($item['name']) ?>" style="height: 225px; object-fit: cover;">
                            <?php else: ?>
                            <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center"
                                style="height: 225px;">
                                <span class="text-white">No Image Available</span>
                            </div>
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($item['name']) ?></h5>
                                <p class="card-text">
                                    <span
                                        class="badge bg-primary"><?= htmlspecialchars($item['category_name']) ?></span>
                                </p>
                                <p class="card-text">
                                    <?= htmlspecialchars(substr($item['description'] ?? 'No description available', 0, 100)) ?>...
                                </p>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="btn-group">
                                            <div class="text-primary fw-bold d-flex align-items-center">
                                                <i class='bx bxs-store-alt'></i>
                                                <small class="m-0 ms-1"><?= $item['store_name'] ?></small>
                                            </div>
                                            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'administrator'): ?>
                                            <a href="admin/items/edit.php?id=<?= $item['id'] ?>"
                                                class="btn btn-sm btn-outline-secondary">Edit</a>
                                            <?php endif; ?>
                                        </div>
                                        <strong class="text-primary">Rp.
                                            <?= number_format($item['price'], 0, '.', '.') ?></strong>
                                    </div>

                                    <form method="POST" action="">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <button type="submit" name="add_to_cart" class="btn btn-primary mt-3 w-100">
                                            <i class='bx bxs-cart-add me-2'></i>
                                            <span>Add to cart</span>
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
        </div>

</main>

<?php require_once('layouts/tail.php') ?>