<?php
require_once('../../layouts/head.php');
require_once('../../layouts/navbar.php');
require_once '../../config/Database.php';
require_once '../../config/Middleware.php';
require_once '../../class/Category.php';

// Check if the user is an admin
$middleware = new Middleware();
$middleware->requireAdmin();

// Initialize the database connection
$database = new Database();
$pdo = $database->getConnection();

// Initialize the Category class
$category = new Category($pdo);

// Fetch all categories
$categories = $category->getAllCategories();
?>

<section class="container mt-4">
    <h2>Categories</h2>
    <a href="<?= getDomainUrl() . '/dashboard/categories/create.php' ?>" class="btn btn-primary mb-3">Add New Category</a>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $category): ?>
            <tr>
                <td><?= htmlspecialchars($category['name']) ?></td>
                <td>
                    <a href="<?= getDomainUrl() . '/dashboard/categories/update.php?id=' . $category['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="<?= getDomainUrl() . '/dashboard/categories/delete.php?id=' . $category['id']?>" class="btn btn-sm btn-danger"
                        onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php require_once('../../layouts/tail.php') ?>
