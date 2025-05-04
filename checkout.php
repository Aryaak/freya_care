<?php
require_once('config/Database.php');
require_once('layouts/head.php');

// Redirect if not logged in
if (!isset($_COOKIE['user_id'])) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$pdo = $database->getConnection();

$user_id = $_COOKIE['user_id'];
$errors = [];
$success_messages = [];

// Get selected items from URL
$selected_items = [];
if (isset($_GET['selected'])) {
    $selected_items = explode(',', $_GET['selected']);
    $selected_items = array_map('intval', $selected_items); // Sanitize
}

// Process checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Get only the selected items
        $sql = "SELECT cd.id, cd.item_id, cd.qty, i.price, i.store_id, s.name as store_name
                FROM cart_details cd
                JOIN carts c ON cd.cart_id = c.id
                JOIN items i ON cd.item_id = i.id
                JOIN stores s ON i.store_id = s.id
                WHERE c.user_id = ?";
        
        if (!empty($selected_items)) {
            $placeholders = implode(',', array_fill(0, count($selected_items), '?'));
            $sql .= " AND cd.id IN ($placeholders)";
            $params = array_merge([$user_id], $selected_items);
        } else {
            $params = [$user_id];
        }
        
        $sql .= " ORDER BY i.store_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cart_items)) {
            throw new Exception("Your cart is empty");
        }

        // Validate payment method
        $payment_method = $_POST['payment_method'] ?? '';
        if (!in_array($payment_method, ['debit', 'credit', 'cod'])) {
            throw new Exception("Invalid payment method");
        }

        // Group items by store and calculate totals
        $stores = [];
        foreach ($cart_items as $item) {
            $store_id = $item['store_id'];
            if (!isset($stores[$store_id])) {
                $stores[$store_id] = [
                    'name' => $item['store_name'],
                    'items' => [],
                    'total' => 0
                ];
            }
            $item_total = $item['price'] * $item['qty'];
            $stores[$store_id]['items'][] = [
                'item_id' => $item['item_id'],
                'qty' => $item['qty'],
                'price' => $item['price'],
                'subtotal' => $item_total
            ];
            $stores[$store_id]['total'] += $item_total;
        }

        // Create separate order for each store
        $order_ids = [];
        foreach ($stores as $store_id => $store_data) {
            $stmt = $pdo->prepare("
                INSERT INTO orders 
                (user_id, store_id, payment, status, total_amount)
                VALUES (?, ?, ?, 'process', ?)
            ");
            $stmt->execute([
                $user_id, 
                $store_id, 
                $payment_method,
                $store_data['total']
            ]);
            $order_id = $pdo->lastInsertId();
            $order_ids[] = $order_id;

            // Add order details
            $stmt = $pdo->prepare("
                INSERT INTO order_details 
                (order_id, item_id, qty, price, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($store_data['items'] as $item) {
                $stmt->execute([
                    $order_id, 
                    $item['item_id'], 
                    $item['qty'],
                    $item['price'],
                    $item['subtotal']
                ]);
            }

            $success_messages[] = "Order #$order_id created for {$store_data['name']} (Total: Rp " . 
                                number_format($store_data['total'], 0, '.', '.') . ")";
        }

        // Clear selected items from cart
        $sql = "DELETE cd FROM cart_details cd
                JOIN carts c ON cd.cart_id = c.id
                WHERE c.user_id = ?";
        
        if (!empty($selected_items)) {
            $placeholders = implode(',', array_fill(0, count($selected_items), '?'));
            $sql .= " AND cd.id IN ($placeholders)";
            $params = array_merge([$user_id], $selected_items);
        } else {
            $params = [$user_id];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $pdo->commit();
        $cart_items = []; // Clear cart items from display
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = "Checkout failed: " . $e->getMessage();
    }
}

// Fetch cart items to display
try {
    $sql = "SELECT cd.id, i.id as item_id, i.name, i.price, i.image, 
                   i.store_id, s.name as store_name, cd.qty
            FROM cart_details cd
            JOIN carts c ON cd.cart_id = c.id
            JOIN items i ON cd.item_id = i.id
            JOIN stores s ON i.store_id = s.id
            WHERE c.user_id = ?";
    
    if (!empty($selected_items)) {
        $placeholders = implode(',', array_fill(0, count($selected_items), '?'));
        $sql .= " AND cd.id IN ($placeholders)";
        $params = array_merge([$user_id], $selected_items);
    } else {
        $params = [$user_id];
    }
    
    $sql .= " ORDER BY i.store_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group items by store for display
    $stores = [];
    $grand_total = 0;
    
    foreach ($cart_items as $item) {
        $store_id = $item['store_id'];
        if (!isset($stores[$store_id])) {
            $stores[$store_id] = [
                'name' => $item['store_name'],
                'items' => [],
                'subtotal' => 0
            ];
        }
        $item_total = $item['price'] * $item['qty'];
        $stores[$store_id]['items'][] = $item;
        $stores[$store_id]['subtotal'] += $item_total;
        $grand_total += $item_total;
    }
} catch (PDOException $e) {
    $errors[] = "Error fetching cart items: " . $e->getMessage();
}

require_once "layouts/header.php";
?>

<main role="main">
    <div class="container mt-5">
        <h2>Checkout</h2>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
        
        <?php foreach ($success_messages as $message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endforeach; ?>

        <?php if (!empty($stores)): ?>
        <form method="POST" action="checkout.php">
            <!-- Hidden field to maintain selected items -->
            <?php foreach ($selected_items as $item_id): ?>
                <input type="hidden" name="selected_items[]" value="<?= $item_id ?>">
            <?php endforeach; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <?php foreach ($stores as $store_id => $store): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="bx bx-store"></i> <?= htmlspecialchars($store['name']) ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php foreach ($store['items'] as $item): ?>
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <?php if(!empty($item['image'])): ?>
                                                <img src="<?= htmlspecialchars($item['image']) ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($item['name']) ?>">
                                            <?php else: ?>
                                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="height: 80px;">
                                                    <span class="text-white">No image</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><?= htmlspecialchars($item['name']) ?></h6>
                                            <p class="mb-0">Rp <?= number_format($item['price'], 0, '.', '.') ?> x <?= $item['qty'] ?></p>
                                        </div>
                                        <div class="col-md-3 text-end">
                                            <strong>Rp <?= number_format($item['price'] * $item['qty'], 0, '.', '.') ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3 text-end">
                                <h5>Store Subtotal: Rp <?= number_format($store['subtotal'], 0, '.', '.') ?></h5>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>Shipping Information</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Fetch user info
                            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                            $stmt->execute([$user_id]);
                            $user = $stmt->fetch();
                            ?>
                            <p>
                                <strong>Name:</strong> <?= htmlspecialchars($user['name'] ?? '') ?><br>
                                <strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? '') ?><br>
                                <strong>Address:</strong> <br>
                                <span><?= htmlspecialchars($user['address'] ?? '') ?></span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4 sticky-top" style="top: 20px;">
                        <div class="card-header">
                            <h5>Payment Method</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="debit" value="debit" checked>
                                    <label class="form-check-label" for="debit">
                                        <i class="bx bx-credit-card me-2"></i> Debit Card
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="credit" value="credit">
                                    <label class="form-check-label" for="credit">
                                        <i class="bx bx-credit-card-front me-2"></i> Credit Card
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod">
                                    <label class="form-check-label" for="cod">
                                    <i class='bx bxs-credit-card-front'></i> COD
                                    </label>
                                </div>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6>Order Summary</h6>
                                    <hr>
                                    <?php foreach ($stores as $store_id => $store): ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><?= htmlspecialchars($store['name']) ?></span>
                                        <span>Rp <?= number_format($store['subtotal'], 0, '.', '.') ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Shipping</span>
                                        <span>Rp 0</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total</span>
                                        <span>Rp <?= number_format($grand_total, 0, '.', '.') ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if($user['address']): ?>
                                <button type="submit" class="btn btn-primary w-100 btn-lg">
                                <i class="bx bx-check-circle me-2"></i> Complete Purchase
                            </button>
                            <?php else: ?>
                            <a href="profile.php" class="btn btn-outline-primary btn-sm w-100">Update Address to Continue</a>
                            <?php endif; ?>
                            
                            <a href="cart.php" class="btn btn-outline-secondary w-100 mt-2">
                                <i class="bx bx-arrow-back me-2"></i> Back to Cart
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <?php else: ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="index.php">Continue shopping</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once('layouts/tail.php'); ?>