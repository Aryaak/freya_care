<?php
require_once('../layouts/head.php');
require_once('../layouts/header.php');
require_once '../config/Database.php';
require_once '../config/Middleware.php';
require_once '../class/Store.php';
require_once '../class/Item.php';
require_once '../class/Order.php';

$database = new Database();
$pdo = $database->getConnection();

$middleware = new Middleware();
$middleware->requireAuth();

$user_id = $_COOKIE['user_id'];

$storeModel = new Store($pdo);
$itemModel = new Item($pdo);
$orderModel = new Order($pdo);

$store = $storeModel->getStoreByUserId($user_id);
$items = $itemModel->getItemsByUserId($user_id);
$orders = $store ? $orderModel->getOrdersByStoreId($store['id']) : [];
?>

<section class="container mt-4">
    <h2>Store</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if($store) : ?>
            <tr>
                <td><?= htmlspecialchars($store['name']) ?></td>
                <td><?= htmlspecialchars($store['status']) ?></td>
                <td>
                    <?php if ($store['status'] == 'reject'): ?>
                    <a href="<?= getDomainUrl() . '/store/update.php?id=' . $store['id']?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="<?= getDomainUrl() . '/store/delete.php?id=' . $store['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($store['status'] == 'reject'): ?>
            <tr>
                <td colspan="3">
                    <p class="m-0 text-danger fw-bold">Reject Reason:</p>
                    <p class="m-0"><?= $store['reject_reason'] ?></p>
                </td>
            </tr>
            <?php endif; ?>
            <?php else: ?>
            <tr>
                <td colspan="3" class="text-center">
                    <a href="<?= getDomainUrl() . '/store/create.php' ?>" class="btn btn-primary my-3 w-75">Create New Store</a>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if($store && $store['status'] == 'accept'): ?>
    <ul class="nav nav-tabs" id="store-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="items-tab" data-bs-toggle="tab" data-bs-target="#items-tab-pane" type="button" role="tab" aria-controls="items-tab-pane" aria-selected="true">Items</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders-tab-pane" type="button" role="tab" aria-controls="orders-tab-pane" aria-selected="false">Orders</button>
        </li>
    </ul>

    <div class="tab-content" id="store-tab-content">
        <div class="tab-pane fade show active p-3" id="items-tab-pane" role="tabpanel" aria-labelledby="items-tab">
            <a href="<?= getDomainUrl() . '/store/items/create.php' ?>" class="btn btn-primary mb-3">Add New Item</a>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['category_name']) ?></td>
                        <td>
                            <?php if (!empty($item['image'])): ?>
                            <img src="../<?= htmlspecialchars($item['image']) ?>" width="50" class="img-thumbnail">
                            <?php else: ?>
                            <span class="text-muted">No image</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= htmlspecialchars($item['description']) ?></td>
                        <td><?= number_format($item['price'], 0, '.', '.') ?></td>
                        <td>
                            <a href="<?= getDomainUrl() . '/store/items/update.php?id=' . $item['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="<?= getDomainUrl() . '/store/items/delete.php?id=' . $item['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="tab-pane fade p-3" id="orders-tab-pane" role="tabpanel" aria-labelledby="orders-tab">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Address</th>
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
                        <td><?= htmlspecialchars($order['user_name']) ?></td>
                        <td><?= htmlspecialchars($order['user_address']) ?></td>
                        <td>Rp. <?= number_format($order['total_amount'], 0, '.', '.') ?></td>
                        <td><?= htmlspecialchars($order['payment']) ?></td>
                        <td><?= htmlspecialchars($order['status']) ?></td>
                        <td>
                            <a href="<?= getDomainUrl() . '/orders/detail.php?id=' . $order['id'] ?>" class="btn btn-sm btn-primary">Detail</a>
                            <?php if($order['status'] == 'deliver'): ?>
                            <a href="<?= getDomainUrl() . '/orders/done.php?id=' . $order['id']?>" class="btn btn-sm btn-success">Done</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif ?>
</section>

<?php require_once('../layouts/tail.php'); ?>
