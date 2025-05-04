<?php
session_start();

if (!isset($_COOKIE['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once('config/database.php'); // Database connection
require_once('class/Cart.php'); // Include the Cart class

$user_id = $_COOKIE['user_id'];
$database = new Database();
$pdo = $database->getConnection();

$cart = new Cart($pdo, $user_id);

// Handle AJAX requests first (must come before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];

    try {
        if ($_POST['action'] === 'update_qty') {
            $cart_detail_id = (int)$_POST['cart_detail_id'];
            $new_qty = (int)$_POST['qty'];
            $summary = $cart->updateQuantity($cart_detail_id, $new_qty);
            
            $response['success'] = true;
            $response['message'] = "Quantity updated";
            $response['total'] = number_format($summary['total'], 0, '.', '.');
            $response['item_count'] = $summary['item_count'];
            $response['total_items'] = $summary['total_items'];
        } 
        elseif ($_POST['action'] === 'remove_item') {
            $cart_detail_id = (int)$_POST['cart_detail_id'];
            $summary = $cart->removeItem($cart_detail_id);
            
            $response['success'] = true;
            $response['message'] = "Item removed";
            $response['total'] = $summary['total'] ? number_format($summary['total'], 0, '.', '.') : 0;
            $response['item_count'] = $summary['item_count'];
            $response['total_items'] = $summary['total_items'];
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
        http_response_code(400);
    }

    echo json_encode($response);
    exit;
}

// Fetch cart items for normal page load
try {
    $cart_items = $cart->getCartItems();
    $summary = $cart->getCartSummary();

    $total = $summary['total'] ?? 0;
    $item_count = $summary['item_count'] ?? 0;
    $total_items = $summary['total_items'] ?? 0;
} catch (Exception $e) {
    die("Error fetching cart items: " . $e->getMessage());
}

require_once('layouts/head.php');
require_once('layouts/header.php');
?>

<!-- Previous PHP code remains the same until the cart items display -->

<main role="main">
    <div class="container mt-5">
        <h2>Your Shopping Cart</h2>

        <?php if(empty($cart_items)): ?>
        <div class="alert alert-info">
            Your cart is empty. <a href="index.php">Continue shopping</a>
        </div>
        <?php else: 
        // Group items by store
        $grouped_items = [];
        foreach ($cart_items as $item) {
            $store_id = $item['store_id'];
            if (!isset($grouped_items[$store_id])) {
                $grouped_items[$store_id] = [
                    'store_name' => $item['store_name'],
                    'items' => []
                ];
            }
            $grouped_items[$store_id]['items'][] = $item;
        }
        ?>

        <div class="row">
            <div class="col-md-8 cart-items-container">
                <div class="d-flex justify-content-between mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="select-all-items" checked>
                        <label class="form-check-label" for="select-all-items">
                            Select All
                        </label>
                    </div>
                </div>

                <?php foreach ($grouped_items as $store_id => $store_group): ?>
                <div class="store-group mb-4" data-store-id="<?= $store_id ?>">
                    <div
                        class="store-header d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                        <div class="form-check">
                            <input class="form-check-input store-checkbox" type="checkbox" id="store-<?= $store_id ?>"
                                data-store-id="<?= $store_id ?>" checked>
                            <label class="form-check-label fw-bold" for="store-<?= $store_id ?>">
                                <?= htmlspecialchars($store_group['store_name']) ?>
                            </label>
                        </div>
                    </div>

                    <div class="list-group">
                        <?php foreach ($store_group['items'] as $item): ?>
                        <div class="list-group-item cart-item" data-id="<?= $item['id'] ?>">
                            <div class="row">
                                <div class="col-md-1">
                                    <input type="checkbox" class="form-check-input item-checkbox" checked
                                        data-id="<?= $item['id'] ?>" data-price="<?= $item['price'] ?>"
                                        data-qty="<?= $item['qty'] ?>" data-store="<?= $item['store_id'] ?>"
                                        data-subtotal="<?= $item['price'] * $item['qty'] ?>">
                                </div>
                                <div class="col-md-2">
                                    <?php if(!empty($item['image'])): ?>
                                    <img src="<?= htmlspecialchars($item['image']) ?>" class="img-fluid rounded"
                                        alt="<?= htmlspecialchars($item['name']) ?>">
                                    <?php else: ?>
                                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center"
                                        style="height: 100px;">
                                        <span class="text-white">No image</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h5><?= htmlspecialchars($item['name']) ?></h5>
                                    <p class="text-primary item-price" data-price="<?= $item['price'] ?>">
                                        Rp <?= number_format($item['price'], 0, '.', '.') ?>
                                    </p>
                                    <p class="item-subtotal">
                                        Subtotal: Rp <span
                                            class="subtotal-amount"><?= number_format($item['price'] * $item['qty'], 0, '.', '.') ?></span>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group mb-2">
                                        <button class="btn btn-outline-secondary qty-minus" type="button">-</button>
                                        <input type="number" id="qty-<?= $item['id'] ?>"
                                            class="form-control qty-input text-center" value="<?= $item['qty'] ?>"
                                            min="1">
                                        <button class="btn btn-outline-secondary qty-plus" type="button">+</button>
                                    </div>
                                    <button class="btn btn-outline-danger w-100 remove-item">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Order summary remains the same -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Items (<span class="cart-count"><?= $item_count ?></span>)</span>
                            <span><span class="total-items"><?= $total_items ?></span> products</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total</span>
                            <span>Rp <span class="cart-total"><?= number_format($total, 0, '.', '.') ?></span></span>
                        </div>
                        <a href="checkout.php" class="btn btn-primary w-100 mt-3 checkout-btn" id="proceed-to-checkout">
                            Proceed to Checkout
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        const storeCheckboxes = document.querySelectorAll('.store-checkbox');
        const selectAllCheckbox = document.getElementById('select-all-items');
        const checkoutBtn = document.getElementById('proceed-to-checkout');
        const cartTotal = document.querySelector('.cart-total');
        const cartCount = document.querySelector('.cart-count');
        const totalItems = document.querySelector('.total-items');

        // Function to update selected items and totals
        function updateSelectedItems() {
            let selectedItems = [];
            let newTotal = 0;
            let itemCount = 0;
            let productCount = 0;

            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    const id = parseFloat(checkbox.dataset.id);
                    const price = parseFloat(checkbox.dataset.price);
                    const qty = parseInt(document.getElementById(`qty-${id}`).value);
                    const subtotal = price * qty;

                    selectedItems.push(id);
                    newTotal += subtotal;
                    itemCount++;
                    productCount += qty;
                }
            });

            // Update UI
            cartCount.textContent = itemCount;
            totalItems.textContent = productCount;
            cartTotal.textContent = new Intl.NumberFormat('id-ID').format(newTotal);
            checkoutBtn.href = `checkout.php?selected=${selectedItems.join(',')}`;
            checkoutBtn.disabled = itemCount === 0;
            checkoutBtn.classList.toggle('disabled', itemCount === 0);

            // Update store checkboxes state
            updateStoreCheckboxes();
            updateSelectAllCheckbox();
        }

        // Function to update store checkboxes based on item selection
        function updateStoreCheckboxes() {
            storeCheckboxes.forEach(storeCheckbox => {
                const storeId = storeCheckbox.dataset.storeId;
                const storeGroup = document.querySelector(`.store-group[data-store-id="${storeId}"]`);
                const storeItems = storeGroup.querySelectorAll('.item-checkbox');
                let allChecked = true;
                let anyChecked = false;

                storeItems.forEach(item => {
                    if (!item.checked) allChecked = false;
                    if (item.checked) anyChecked = true;
                });

                storeCheckbox.checked = allChecked;
                storeCheckbox.indeterminate = anyChecked && !allChecked;
            });
        }


        // Function to update "Select All" checkbox
        function updateSelectAllCheckbox() {
            const allItems = document.querySelectorAll('.item-checkbox');
            let allChecked = true;
            let anyChecked = false;

            allItems.forEach(item => {
                if (!item.checked) allChecked = false;
                if (item.checked) anyChecked = true;
            });

            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = anyChecked && !allChecked;
        }

        // Select all items
        selectAllCheckbox.addEventListener('change', function () {
            const isChecked = this.checked;
            checkboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            updateSelectedItems();
        });

        // Select all items in a store
        storeCheckboxes.forEach(storeCheckbox => {
            storeCheckbox.addEventListener('change', function () {
                const storeId = this.dataset.storeId;
                const isChecked = this.checked;
                const storeGroup = this.closest('.store-group');
                const storeItems = storeGroup.querySelectorAll('.item-checkbox');

                storeItems.forEach(item => {
                    item.checked = isChecked;
                });

                updateSelectedItems();
            });
        });
        // Update when individual items change
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedItems);
        });

        // Initial update
        updateSelectedItems();

        // Quantity update and remove item functions remain the same as before
        document.querySelectorAll('.cart-item').forEach(function (itemEl) {
            const cartId = itemEl.dataset.id;
            const qtyInput = itemEl.querySelector('.qty-input');
            const minusBtn = itemEl.querySelector('.qty-minus');
            const plusBtn = itemEl.querySelector('.qty-plus');
            const subtotalEl = itemEl.querySelector('.subtotal-amount');
            const itemPrice = parseInt(itemEl.querySelector('.item-price').dataset.price);
            const checkbox = itemEl.querySelector('.item-checkbox');

            function updateQty(newQty) {
                fetch('cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'update_qty',
                            cart_detail_id: cartId,
                            qty: newQty
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            subtotalEl.textContent = new Intl.NumberFormat('id-ID').format(
                                itemPrice * newQty);
                            checkbox.dataset.qty = newQty;
                            updateSelectedItems();
                        } else {
                            alert(data.error || 'Failed to update quantity.');
                        }
                    });
            }

            minusBtn.addEventListener('click', () => {
                let qty = parseInt(qtyInput.value);
                if (qty > 1) {
                    qtyInput.value = --qty;
                    updateQty(qty);
                }
            });

            plusBtn.addEventListener('click', () => {
                let qty = parseInt(qtyInput.value);
                qtyInput.value = ++qty;
                updateQty(qty);
            });

            qtyInput.addEventListener('change', () => {
                let qty = parseInt(qtyInput.value);
                if (qty >= 1) {
                    updateQty(qty);
                } else {
                    qtyInput.value = 1;
                    updateQty(1);
                }
            });
        });

        document.querySelectorAll('.remove-item').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const cartItem = this.closest('.cart-item');
        const cartId = cartItem.dataset.id;
        const storeId = cartItem.querySelector('.item-checkbox').dataset.store;
        const storeGroup = document.querySelector(`.store-group[data-store-id="${storeId}"]`);

        fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'remove_item',
                    cart_detail_id: cartId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Remove the item
                    cartItem.remove();
                    
                    // Check if this was the last item in the store group
                    const remainingItems = storeGroup.querySelectorAll('.cart-item');
                    if (remainingItems.length === 0) {
                        // Remove the store group
                        storeGroup.remove();
                        
                        // Also check if this was the last store group
                        const remainingStores = document.querySelectorAll('.store-group');
                        if (remainingStores.length === 0) {
                            // Show empty cart message
                            document.querySelector('.cart-items-container').innerHTML = `
                                <div class="alert alert-info">Your cart is empty. <a href="index.php">Continue shopping</a></div>
                            `;
                            document.querySelector('.card').remove();
                        }
                    }
                    
                    // Update the UI
                    updateSelectedItems();
                } else {
                    alert(data.error || 'Failed to remove item.');
                }
            });
    });
});
    });
</script>

<?php require_once('layouts/tail.php'); ?>