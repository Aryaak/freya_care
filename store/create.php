<?php require_once('../layouts/head.php') ?>

<?php require_once('../layouts/navbar.php') ?>

<?php
require_once '../config/Database.php';
require_once '../config/Middleware.php';

$database = new Database();
$pdo = $database->getConnection();

$middleware = new Middleware();
$middleware->requireAuth();
// Fetch users for dropdown
$users = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $name = $_POST['name'];
    
    $stmt = $pdo->prepare("INSERT INTO stores (user_id, name) VALUES (?, ?)");
    $stmt->execute([$user_id, $name]);
    
    header("Location: index.php");
    exit;
}
?>

<section class="container mt-4">
<h2>Add New Store</h2>
<form method="POST">
    <input type="hidden" name="user_id" value="<?= $_COOKIE['user_id'] ?>">

    <div class="mb-3">
        <label for="name" class="form-label">Store Name</label>
        <input type="text" class="form-control" id="name" name="name" required>
    </div>

    <button type="submit" class="btn btn-primary">Submit</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
</form>
</section>

<?php require_once('../layouts/tail.php') ?>