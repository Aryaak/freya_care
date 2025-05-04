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

// Process checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Get selected items from POST data
        $selected_items = $_POST['selected_items'] ?? [];
        if (empty($selected_items)) {
            throw new Exception("Please select at least one item to checkout");
        }

        // Convert selected items to integers for safety
        $selected_items = array_map('intval', $selected_items);
        $placeholders = implode(',', array_fill(0, count($selected_items), '?'));

        // 1. Get selected cart items grouped by store
        $stmt = $pdo->prepare("
            SELECT 
                cd.id, 
                cd.item_id, 
                cd.qty, 
                i.price,
                i.store_id,
                s.name as store_name
            FROM cart_details cd
            JOIN carts c ON cd.cart_id = c.id
            JOIN items i ON cd.item_id = i.id
            JOIN stores s ON i.store_id = s.id
            WHERE c.user_id = ? AND cd.id IN ($placeholders)
            ORDER BY i.store_id
        ");
        $stmt->execute(array_merge([$user_id], $selected_items));
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cart_items)) {
            throw new Exception("No valid items selected for checkout");
        }

        // 2. Validate payment method
        $payment_method = $_POST['payment_method'] ?? '';
        if (!in_array($payment_method, ['debit', 'credit', 'cod'])) {
            throw new Exception("Invalid payment method");
        }

        // 3. Group items by store and calculate totals
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
                'subtotal' => $item_total,
                'cart_detail_id' => $item['id'] // Store cart detail ID for deletion later
            ];
            $stores[$store_id]['total'] += $item_total;
        }

        // 4. Create separate order for each store
        $order_ids = [];
        foreach ($stores as $store_id => $store_data) {
            // Create order for this store with total amount
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

            // Add order details with price and subtotal
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

        // 5. Remove only the checked items from cart
        $stmt = $pdo->prepare("
            DELETE FROM cart_details 
            WHERE id IN ($placeholders) AND cart_id = (SELECT id FROM carts WHERE user_id = ?)
        ");
        $stmt->execute(array_merge($selected_items, [$user_id]));

        $pdo->commit();
        
        // Refresh cart items after successful checkout
        $stmt = $pdo->prepare("
            SELECT 
                i.id as item_id,
                i.name,
                i.price,
                i.image,
                i.store_id,
                s.name as store_name,
                cd.qty,
                cd.id as cart_detail_id
            FROM cart_details cd
            JOIN carts c ON cd.cart_id = c.id
            JOIN items i ON cd.item_id = i.id
            JOIN stores s ON i.store_id = s.id
            WHERE c.user_id = ?
            ORDER BY i.store_id
        ");
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = "Checkout failed: " . $e->getMessage();
    }
}

// Fetch cart items to display
try {
    $stmt = $pdo->prepare("
        SELECT 
            i.id as item_id,
            i.name,
            i.price,
            i.image,
            i.store_id,
            s.name as store_name,
            cd.qty,
            cd.id as cart_detail_id
        FROM cart_details cd
        JOIN carts c ON cd.cart_id = c.id
        JOIN items i ON cd.item_id = i.id
        JOIN stores s ON i.store_id = s.id
        WHERE c.user_id = ?
        ORDER BY i.store_id
    ");
    $stmt->execute([$user_id]);
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
?>

<?php require_once "layouts/header.php" ?>

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
            <div class="row">
                <div class="col-md-8">
                    <?php foreach ($stores as $store_id => $store): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bx bx-store"></i> <?= htmlspecialchars($store['name']) ?>
                                </h5>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input select-all" 
                                        id="select-all-<?= $store_id ?>" data-store="<?= $store_id ?>">
                                    <label class="form-check-label" for="select-all-<?= $store_id ?>">
                                        Select all
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php foreach ($store['items'] as $item): ?>
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-1">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input item-checkbox" 
                                                    name="selected_items[]" value="<?= $item['cart_detail_id'] ?>"
                                                    id="item-<?= $item['cart_detail_id'] ?>" 
                                                    data-store="<?= $store_id ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <?php if(!empty($item['image'])): ?>
                                                <img src="<?= htmlspecialchars($item['image']) ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($item['name']) ?>">
                                            <?php else: ?>
                                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="height: 80px;">
                                                    <span class="text-white">No image</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-5">
                                            <h6><?= htmlspecialchars($item['name']) ?></h6>
                                            <p class="mb-0">Rp <?= number_format($item['price'], 0, '.', '.') ?> x <?= $item['qty'] ?></p>
                                        </div>
                                        <div class="col-md-4 text-end">
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
                                <span>
                                <?= htmlspecialchars($user['address'] ?? '') ?>
                                </span>
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
                                    <div id="selected-items-summary">
                                        <!-- This will be updated by JavaScript -->
                                        <p class="text-muted">No items selected</p>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Shipping</span>
                                        <span>Rp 0</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total</span>
                                        <span id="checkout-total">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if($user['address']): ?>
                                <button type="submit" class="btn btn-primary w-100 btn-lg" id="checkout-button" disabled>
                                    <i class="bx bx-check-circle me-2"></i> Purchase
                                </button>
                            <?php else: ?>
                                <a href="profile.php" class="btn btn-outline-primary btn-sm w-100">Update Address</a>
                            <?php endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle "Select all" checkboxes
    document.querySelectorAll('.select-all').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const storeId = this.dataset.store;
            const itemCheckboxes = document.querySelectorAll(`.item-checkbox[data-store="${storeId}"]`);
            
            itemCheckboxes.forEach(item => {
                item.checked = this.checked;
            });
            
            updateCheckoutSummary();
        });
    });
    
    // Handle individual item checkboxes
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateCheckoutSummary);
    });
    
    // Update the checkout summary based on selected items
    function updateCheckoutSummary() {
        const selectedItems = Array.from(document.querySelectorAll('.item-checkbox:checked'));
        const checkoutButton = document.getElementById('checkout-button');
        const summaryContainer = document.getElementById('selected-items-summary');
        const totalContainer = document.getElementById('checkout-total');
        
        if (selectedItems.length === 0) {
            summaryContainer.innerHTML = '<p class="text-muted">No items selected</p>';
            totalContainer.textContent = 'Rp 0';
            if (checkoutButton) checkoutButton.disabled = true;
            return;
        }
        
        // Calculate total
        let total = 0;
        const storeTotals = {};
        
        selectedItems.forEach(item => {
            const storeId = item.dataset.store;
            const itemRow = item.closest('.list-group-item');
            const priceText = itemRow.querySelector('strong').textContent;
            const price = parseInt(priceText.replace(/[^\d]/g, '')) || 0;
            
            total += price;
            
            if (!storeTotals[storeId]) {
                storeTotals[storeId] = 0;
            }
            storeTotals[storeId] += price;
        });
        
        // Update summary HTML
        let summaryHTML = '';
        for (const [storeId, storeTotal] of Object.entries(storeTotals)) {
            const storeName = document.querySelector(`.select-all[data-store="${storeId}"]`)
                .closest('.card-header').querySelector('h5').textContent.trim();
            
            summaryHTML += `
                <div class="d-flex justify-content-between mb-2">
                    <span>${storeName}</span>
                    <span>Rp ${storeTotal.toLocaleString('id-ID')}</span>
                </div>
            `;
        }
        
        summaryContainer.innerHTML = summaryHTML;
        totalContainer.textContent = `Rp ${total.toLocaleString('id-ID')}`;
        if (checkoutButton) checkoutButton.disabled = false;
    }
    
    // Initialize summary on page load
    updateCheckoutSummary();
});
</script>

<?php require_once('layouts/tail.php'); ?>