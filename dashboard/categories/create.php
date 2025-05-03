<?php require_once('../../layouts/head.php') ?>
<?php require_once('../../layouts/navbar.php') ?>

<?php
require_once '../../config/Database.php';
require_once '../../config/Middleware.php';
require_once '../../class/Category.php';

$database = new Database();
$pdo = $database->getConnection();

$middleware = new Middleware();
$middleware->requireAuth();

$category = new Category($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    
    if ($name !== '') {
        $category->create($name);
        header("Location: index.php");
        exit;
    }
}
?>

<section class="container mt-4">
    <h2>Add New Category</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Category Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</section>

<?php require_once('../../layouts/tail.php') ?>
