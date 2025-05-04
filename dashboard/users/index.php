<?php require_once('../../layouts/head.php') ?>

<?php require_once('../../layouts/navbar.php') ?>

<?php
require_once '../../config/Database.php';
require_once '../../config/Middleware.php';

$database = new Database();
$pdo = $database->getConnection();

$middleware = new Middleware();
$middleware->requireAuth();


// Fetch all users
$stmt = $pdo->query("SELECT * FROM users WHERE role != 'administrator'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="container my-4">
<h2>Users</h2>

<table class="table table-striped">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['name']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td>
                <a href="<?= getDomainUrl() . '/dashboard/users/delete.php?id=' . $user['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</section>

<script>
    new DataTable('table');
</script>


<?php require_once('../../layouts/tail.php') ?>