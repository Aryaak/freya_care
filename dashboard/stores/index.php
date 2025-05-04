<?php require_once '../../layouts/head.php'; ?>

<?php
require_once '../../config/Database.php';

$database = new Database();
$pdo = $database->getConnection();

// Fetch all stores with user names
$stmt = $pdo->query("
    SELECT stores.*, users.name as user_name 
    FROM stores 
    JOIN users ON stores.user_id = users.id
");
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once '../../layouts/navbar.php'; ?>

<section class="container mt-4">
<h2>Stores</h2>

<table class="table table-striped">
    <thead>
        <tr>
            <th>User</th>
            <th>Name</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($stores as $store): ?>
        <tr>
            <td><?= htmlspecialchars($store['user_name']) ?></td>
            <td><?= htmlspecialchars($store['name']) ?></td>
            <td><?= htmlspecialchars($store['status']) ?></td>
            <td>
                <a href="<?= getDomainUrl() . '/dashboard/stores/update.php?id=' . $store['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                <a href="<?= getDomainUrl() . '/dashboard/stores/update.php?id=' . $store['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php if ($store['status'] == 'reject'): ?>
        <tr>
            <td>
                <p class="m-0 text-danger fw-bold">Reject Reason:</p>
                <p class="m-0"><?= $store['reject_reason'] ?></p>
            </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
    </tbody>
</table>
</section>

<script>
    new DataTable('table');
</script>


<?php require_once '../../layouts/tail.php'; ?>