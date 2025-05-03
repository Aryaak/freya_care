<?php require_once('../layouts/head.php') ?>
<?php require_once('../layouts/header.php') ?>

<?php
require_once '../../config/Database.php';
require_once '../../config/Middleware.php';

$database = new Database();
$pdo = $database->getConnection();

$middleware = new Middleware();
$middleware->requireAuth();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM stores WHERE id = ?");
$stmt->execute([$id]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    header("Location: index.php");
    exit;
}

// Fetch users for dropdown
$users = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    
    $stmt = $pdo->prepare("UPDATE stores SET name = ?, status = 'pending', reject_reason = NULL WHERE id = ?");
    $stmt->execute([$name, $id]);
    
    header("Location: index.php");
    exit;
}
?>

<section class="container mt-4">
    <h2>Edit Store</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Store Name</label>
            <input type="text" class="form-control" id="name" name="name"
                value="<?= htmlspecialchars($store['name']) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</section>

<?php require_once('../layouts/tail.php') ?>