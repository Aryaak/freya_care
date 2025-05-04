<?php
require_once '../../layouts/head.php';
require_once '../../config/Database.php';
require_once '../../config/Middleware.php';

$database = new Database();
$pdo = $database->getConnection();

$middleware = new Middleware();
$middleware->requireAuth();

$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    die("Order ID is missing.");
}

// Fetch order details
$stmt = $pdo->prepare("
    SELECT orders.*, stores.user_id AS store_user_id, users.name AS user_name, users.address AS user_address
    FROM orders
    JOIN users ON orders.user_id = users.id
    JOIN stores ON orders.store_id = stores.id
    WHERE orders.id = ?
    ORDER BY id DESC
");

$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found or access denied.");
}

// Optionally, fetch order items if you have an order_items table
$itemStmt = $pdo->prepare("
    SELECT od.*, i.name, i.price
    FROM order_details od
    JOIN items i ON od.item_id = i.id
    WHERE od.order_id = ?
");

$itemStmt->execute([$orderId]);
$orderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once '../../layouts/navbar.php'; ?>

<section class="container my-4">
    <h2>Order #<?= htmlspecialchars($order['id']) ?> Details</h2>
    <p><strong>Name:</strong> <?= htmlspecialchars($order['user_name']) ?></p>
    <p><strong>Address:</strong> <?= htmlspecialchars($order['user_address']) ?></p>
    <p><strong>Total:</strong> Rp. <?= number_format($order['total_amount'], 0, '.', '.') ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
    <p><strong>Payment:</strong> <?= htmlspecialchars($order['payment']) ?></p>

    <h4 class="mt-4">Items</h4>
    <table class="table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orderItems as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= htmlspecialchars($item['qty']) ?></td>
                <td>Rp. <?= number_format($item['price'], 0, '.', '.') ?></td>
                <td>Rp. <?= number_format($item['subtotal'], 0, '.', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php require_once '../../layouts/tail.php'; ?>