<?php require_once('../layouts/head.php') ?>

<?php require_once('../layouts/header.php') ?>

<?php
require_once '../config/Database.php';
require_once '../config/Middleware.php';

$database = new Database();
$pdo = $database->getConnection();

$middleware = new Middleware();
$middleware->requireAuth();

// Fetch all orders with user names
$stmt = $pdo->prepare("
    SELECT 
    orders.*,
    users.name as user_name,
    users.address as user_address,
    stores.name as store_name
    FROM orders 
    JOIN users ON orders.user_id = users.id
    JOIN stores ON orders.store_id = stores.id
    WHERE orders.user_id = ?
    ORDER BY id DESC
");

$stmt->execute([$_COOKIE['user_id']]);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="container my-4">
    <h2>Orders</h2>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Address</th>
                <th>Store</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td>#<?= htmlspecialchars($order['id']) ?></td>
                <td><?= htmlspecialchars($order['user_address']) ?></td>
                <td><?= htmlspecialchars($order['store_name']) ?></td>
                <td>Rp. <?= htmlspecialchars(number_format($order['total_amount'], 0, '.', '.')) ?></td>
                <td><?= htmlspecialchars($order['payment']) ?></td>
                <td><?= htmlspecialchars($order['status']) ?></td>
                <td>
                    <a href="<?= getDomainUrl() . '/orders/detail.php?id=' . $order['id'] ?>" class="btn btn-sm btn-primary">Detail</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<script>
    new DataTable('table');
</script>

<?php require_once('../layouts/tail.php') ?>