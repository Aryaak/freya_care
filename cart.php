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

<main role="main">
    <div class="container mt-5">
        <h2>Your Shopping Cart</h2>

        <?php if(empty($cart_items)): ?>
        <div class="alert alert-info">
            Your cart is empty. <a href="index.php">Continue shopping</a>
        </div>
        <?php else: ?>
        <div class="row">
            <div class="col-md-8 cart-items-container">
                <div class="list-group">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="list-group-item cart-item" data-id="<?= $item['id'] ?>">
                        <div class="row">
                            <div class="col-md-3">
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
                                <p class="text-muted"><?= htmlspecialchars($item['store_name']) ?></p>
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
                                    <input type="number" class="form-control qty-input text-center"
                                        value="<?= $item['qty'] ?>" min="1">
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
                        <a href="checkout.php" class="btn btn-primary w-100 mt-3 checkout-btn">
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
    // Update quantity via AJAX
    document.querySelectorAll('.cart-item').forEach(function (itemEl) {
        const cartId = itemEl.dataset.id;
        const qtyInput = itemEl.querySelector('.qty-input');
        const minusBtn = itemEl.querySelector('.qty-minus');
        const plusBtn = itemEl.querySelector('.qty-plus');
        const subtotalEl = itemEl.querySelector('.subtotal-amount');
        const itemPrice = parseInt(itemEl.querySelector('.item-price').dataset.price);

        function updateQty(newQty) {
            fetch('cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'update_qty',
                    cart_detail_id: cartId,
                    qty: newQty
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    subtotalEl.textContent = new Intl.NumberFormat('id-ID').format(itemPrice * newQty);
                    document.querySelector('.cart-total').textContent = data.total;
                    document.querySelector('.cart-count').textContent = data.item_count;
                    document.querySelector('.total-items').textContent = data.total_items;
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

    // Remove item
    document.querySelectorAll('.remove-item').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const cartItem = this.closest('.cart-item');
            const cartId = cartItem.dataset.id;

            fetch('cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'remove_item',
                    cart_detail_id: cartId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    cartItem.remove();
                    document.querySelector('.cart-total').textContent = data.total;
                    document.querySelector('.cart-count').textContent = data.item_count;
                    document.querySelector('.total-items').textContent = data.total_items;

                    // Optionally show "cart is empty" message
                    if (document.querySelectorAll('.cart-item').length === 0) {
                        document.querySelector('.cart-items-container').innerHTML = `
                            <div class="alert alert-info">Your cart is empty. <a href="index.php">Continue shopping</a></div>
                        `;
                        document.querySelector('.card').remove();
                    }
                } else {
                    alert(data.error || 'Failed to remove item.');
                }
            });
        });
    });
});
</script>

<?php require_once('layouts/tail.php'); ?>
