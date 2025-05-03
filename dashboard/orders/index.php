<?php require_once '../../layouts/head.php'; ?>

<?php
require_once '../../config/database.php';

// Fetch all orders with user names
$stmt = $pdo->query("
    SELECT orders.*, stores.name AS store_name, users.name as user_name 
    FROM orders 
    JOIN users ON orders.user_id = users.id
    JOIN stores ON orders.store_id = stores.id
    ORDER BY id DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once '../../layouts/navbar.php'; ?>


<section class="container my-4">
    <h2>Orders</h2>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Store</th>
                <th>Total Amount</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td>#<?= htmlspecialchars($order['id']) ?></td>
                <td><?= htmlspecialchars($order['user_name']) ?></td>
                <td><?= htmlspecialchars($order['store_name']) ?></td>
                <td>Rp. <?= htmlspecialchars(number_format($order['total_amount'], 0, '.', '.')) ?></td>
                <td><?= htmlspecialchars($order['payment']) ?></td>
                <td><?= htmlspecialchars($order['status']) ?></td>
                <td>
                    <a href="<?= getDomainUrl() . '/dashboard/orders/detail.php?id=' . $order['id'] ?>"
                        class="btn btn-sm btn-primary">Detail</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</section>

<?php require_once '../../layouts/tail.php'; ?>