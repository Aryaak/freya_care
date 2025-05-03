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

$categoryObj = new Category($pdo);

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$category = $categoryObj->getById($id);

if (!$category) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';

    if ($name !== '') {
        $categoryObj->update($id, $name);
        header("Location: index.php");
        exit;
    }
}
?>

<section class="container mt-4">
    <h2>Edit Category</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Category Name</label>
            <input type="text" class="form-control" id="name" name="name"
                value="<?= htmlspecialchars($category['name']) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</section>

<?php require_once('../../layouts/tail.php') ?>
